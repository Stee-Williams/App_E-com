<?php

namespace App\Service;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class PaiementService
{
    private ?StripeClient $stripeClient = null;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CommandeRepository $commandeRepository,
        private readonly CommandeService $commandeService,
        private readonly EmailService $emailService,
        private readonly string $secretKey,
        private readonly string $webhookSecret,
        private readonly string $mode,
        private readonly string $frontendUrl,
    ) {
    }

    public function estConfigure(): bool
    {
        $prefixe = $this->mode === 'live' ? 'sk_live_' : 'sk_test_';

        return in_array($this->mode, ['test', 'live'], true)
            && str_starts_with($this->secretKey, $prefixe)
            && !str_contains($this->secretKey, '...')
            && str_starts_with($this->webhookSecret, 'whsec_')
            && !str_contains($this->webhookSecret, '...');
    }

    public function creerSession(Commande $commande): string
    {
        if (!$this->estConfigure()) {
            throw new \LogicException('Le paiement Stripe n’est pas configuré en mode ' . $this->mode . '.');
        }

        $email = $commande->getUtilisateur()?->getEmail() ?? $commande->getEmailInvite();
        $parametres = [
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'xof',
                    'product_data' => ['name' => 'Commande ' . $commande->getNumero()],
                    'unit_amount' => (int) round((float) $commande->getTotal()),
                ],
                'quantity' => 1,
            ]],
            'client_reference_id' => (string) $commande->getId(),
            'metadata' => ['commande_id' => (string) $commande->getId()],
            'success_url' => rtrim($this->frontendUrl, '/') . '/paiement/retour?session_id={CHECKOUT_SESSION_ID}&jeton=' . urlencode((string) $commande->getJetonSuivi()),
            'cancel_url' => rtrim($this->frontendUrl, '/') . '/paiement/annule?jeton=' . urlencode((string) $commande->getJetonSuivi()),
            'expires_at' => time() + 1800,
        ];
        if ($email) {
            $parametres['customer_email'] = $email;
        }

        try {
            $session = $this->stripe()->checkout->sessions->create($parametres);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->commandeService->annulerCommande($commande);
            throw $e;
        }

        if (!$session->url) {
            $this->commandeService->annulerCommande($commande);
            throw new \UnexpectedValueException('Stripe n’a pas retourné de lien de paiement.');
        }

        $commande->setStripeSessionId($session->id);
        $this->em->flush();

        return $session->url;
    }

    public function obtenirStatut(string $sessionId): ?array
    {
        $commande = $this->commandeRepository->findParSessionStripe($sessionId);
        if (!$commande) {
            return null;
        }

        return [
            'numero' => $commande->getNumero(),
            'statut' => $commande->getStatut(),
            'statutPaiement' => $commande->getStatutPaiement(),
            'invite' => $commande->isInvite(),
        ];
    }

    public function annulerParJeton(string $jeton): ?array
    {
        $commande = $this->commandeRepository->findParJetonSuivi($jeton);
        if (!$commande) {
            return null;
        }

        if ($commande->getStatutPaiement() === Commande::PAIEMENT_PAYE) {
            return $this->obtenirStatutCommande($commande);
        }

        if ($commande->getStatutPaiement() === Commande::PAIEMENT_EN_ATTENTE && $commande->getStripeSessionId()) {
            if (!$this->estConfigure()) {
                throw new \LogicException('Le paiement Stripe n’est pas configuré.');
            }

            $session = $this->stripe()->checkout->sessions->retrieve($commande->getStripeSessionId());
            if ($session->payment_status === 'paid') {
                $this->traiterPaiementReussi($session);

                return $this->obtenirStatutCommande($commande);
            }
            if ($session->status === 'complete') {
                return $this->obtenirStatutCommande($commande);
            }
            if ($session->status === 'open') {
                $this->stripe()->checkout->sessions->expire($commande->getStripeSessionId());
            }
        }

        $this->commandeService->annulerCommande($commande);

        return $this->obtenirStatutCommande($commande);
    }

    public function traiterWebhook(string $payload, string $signature): void
    {
        if (!$this->estConfigure()) {
            throw new \LogicException('Le webhook Stripe n’est pas configuré.');
        }

        $event = Webhook::constructEvent($payload, $signature, $this->webhookSecret);
        $session = $event->data->object;
        if (!$session instanceof Session || !isset($session->id)) {
            return;
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $this->traiterPaiementReussi($session);
            return;
        }

        if (in_array($event->type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $commande = $this->trouverCommandeDuWebhook($session);
            if ($commande && $commande->getStatutPaiement() === Commande::PAIEMENT_EN_ATTENTE) {
                $this->commandeService->annulerCommande($commande);
            }
        }
    }

    private function traiterPaiementReussi(Session $session): void
    {
        if ($session->payment_status !== 'paid') {
            return;
        }

        $commande = $this->trouverCommandeDuWebhook($session);
        if (!$commande) {
            throw new \UnexpectedValueException('Commande associée à la session Stripe introuvable.');
        }

        $montantAttendu = (int) round((float) $commande->getTotal());
        if (strtolower((string) $session->currency) !== 'xof' || $session->amount_total !== $montantAttendu) {
            throw new \UnexpectedValueException('Le montant ou la devise du paiement Stripe ne correspond pas à la commande.');
        }

        $commandeConfirmee = $this->em->wrapInTransaction(function () use ($commande, $session): ?Commande {
            $this->em->refresh($commande, LockMode::PESSIMISTIC_WRITE);

            if ($commande->getStatutPaiement() === Commande::PAIEMENT_PAYE) {
                return null;
            }
            if ($commande->getStatutPaiement() !== Commande::PAIEMENT_EN_ATTENTE
                || $commande->getStatut() === Commande::STATUT_ANNULEE
            ) {
                throw new \UnexpectedValueException('La commande ne peut pas être confirmée dans son état actuel.');
            }

            $commande->setStripeSessionId($session->id);
            $commande->setStatutPaiement(Commande::PAIEMENT_PAYE);
            $commande->setStatut(Commande::STATUT_CONFIRMEE);
            $this->em->flush();

            return $commande;
        });

        if ($commandeConfirmee) {
            $this->emailService->envoyerConfirmationCommande($commandeConfirmee);
        }
    }

    private function trouverCommandeDuWebhook(Session $session): ?Commande
    {
        $commande = $this->commandeRepository->findParSessionStripe($session->id);
        if ($commande) {
            return $commande;
        }

        $commandeId = filter_var($session->metadata?->commande_id, FILTER_VALIDATE_INT);
        if (!$commandeId) {
            return null;
        }

        $commande = $this->em->find(Commande::class, $commandeId);
        if (!$commande || ($commande->getStripeSessionId() && $commande->getStripeSessionId() !== $session->id)) {
            return null;
        }

        return $commande;
    }

    /** @return array{numero: ?string, statut: string, statutPaiement: string, invite: bool} */
    private function obtenirStatutCommande(Commande $commande): array
    {
        return [
            'numero' => $commande->getNumero(),
            'statut' => $commande->getStatut(),
            'statutPaiement' => $commande->getStatutPaiement(),
            'invite' => $commande->isInvite(),
        ];
    }

    private function stripe(): StripeClient
    {
        return $this->stripeClient ??= new StripeClient($this->secretKey);
    }
}
