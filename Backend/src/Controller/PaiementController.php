<?php

namespace App\Controller;

use App\Service\PaiementService;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/payments')]
class PaiementController extends ApiController
{
    public function __construct(
        \App\Service\JsonSerializer $serializer,
        private readonly PaiementService $paiementService,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/status', name: 'api_payments_status', methods: ['GET'])]
    public function statut(Request $request): JsonResponse
    {
        $sessionId = trim((string) $request->query->get('session_id', ''));
        if ($sessionId === '') {
            return $this->jsonErreur('Identifiant de paiement requis.');
        }

        $statut = $this->paiementService->obtenirStatut($sessionId);
        if (!$statut) {
            return $this->jsonErreur('Paiement introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($statut);
    }

    #[Route('/cancel', name: 'api_payments_cancel', methods: ['POST'])]
    public function annuler(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $jeton = trim((string) ($data['jeton'] ?? ''));
        if ($jeton === '') {
            return $this->jsonErreur('Jeton de commande requis.');
        }

        try {
            $statut = $this->paiementService->annulerParJeton($jeton);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $this->logger->warning('Expiration de session Stripe impossible.', ['error' => $e->getMessage()]);

            return $this->jsonErreur('Le paiement est en cours de traitement. Réessayez dans quelques instants.', Response::HTTP_CONFLICT);
        } catch (\LogicException $e) {
            return $this->jsonErreur('Le paiement n’est pas configuré sur le serveur.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$statut) {
            return $this->jsonErreur('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        return $this->json($statut);
    }

    #[Route('/stripe/webhook', name: 'api_payments_stripe_webhook', methods: ['POST'])]
    public function webhook(Request $request): JsonResponse
    {
        try {
            $this->paiementService->traiterWebhook(
                $request->getContent(),
                (string) $request->headers->get('Stripe-Signature', ''),
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $e) {
            $this->logger->warning('Webhook Stripe rejeté.', ['error' => $e->getMessage()]);

            return $this->jsonErreur('Signature ou contenu Stripe invalide.', Response::HTTP_BAD_REQUEST);
        } catch (\LogicException $e) {
            return $this->jsonErreur('Le webhook Stripe n’est pas configuré.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json(['recu' => true]);
    }
}
