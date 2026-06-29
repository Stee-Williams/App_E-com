<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $mailFromAddress,
        private readonly string $mailFromName,
        private readonly string $frontendUrl,
    ) {
    }

    public function envoyerBienvenue(Utilisateur $utilisateur): void
    {
        $this->envoyer(
            $utilisateur->getEmail(),
            'Bienvenue sur NovaShop',
            sprintf(
                "Bonjour %s,\n\nVotre compte NovaShop a bien été créé.\nVous pouvez vous connecter ici : %s/connexion\n\nÀ bientôt,\n%s",
                $utilisateur->getPrenom(),
                rtrim($this->frontendUrl, '/'),
                $this->mailFromName
            )
        );
    }

    public function envoyerReinitialisationMotDePasse(Utilisateur $utilisateur): void
    {
        $token = $utilisateur->getJetonReinitialisation();
        if (!$token) {
            return;
        }

        $lien = sprintf('%s/mot-de-passe-oublie?token=%s', rtrim($this->frontendUrl, '/'), urlencode($token));

        $this->envoyer(
            $utilisateur->getEmail(),
            'Réinitialisation de votre mot de passe NovaShop',
            sprintf(
                "Bonjour %s,\n\nPour réinitialiser votre mot de passe, utilisez ce lien :\n%s\n\nSi le lien ne fonctionne pas encore dans l'interface, utilisez ce jeton :\n%s\n\nCe lien expire dans 1 heure.\n\n%s",
                $utilisateur->getPrenom(),
                $lien,
                $token,
                $this->mailFromName
            )
        );
    }

    public function envoyerConfirmationCommande(Commande $commande): void
    {
        $email = $commande->getUtilisateur()?->getEmail() ?? $commande->getEmailInvite();
        if (!$email) {
            return;
        }

        $messageSuivi = $commande->getJetonSuivi()
            ? sprintf("Jeton de suivi : %s\nSuivi : %s/suivi-commande?jeton=%s", $commande->getJetonSuivi(), rtrim($this->frontendUrl, '/'), urlencode((string) $commande->getJetonSuivi()))
            : sprintf('Suivi : %s/commandes', rtrim($this->frontendUrl, '/'));

        $this->envoyer(
            $email,
            sprintf('Confirmation de commande %s', $commande->getNumero()),
            sprintf(
                "Bonjour %s,\n\nVotre commande %s est bien enregistrée.\nMontant total : %s FCFA\n\n%s\n\nMerci pour votre confiance,\n%s",
                $commande->getNomClient(),
                $commande->getNumero(),
                (string) $commande->getTotal(),
                $messageSuivi,
                $this->mailFromName
            )
        );
    }

    public function envoyerMiseAJourStatutCommande(Commande $commande, string $libelleStatut): void
    {
        $email = $commande->getUtilisateur()?->getEmail() ?? $commande->getEmailInvite();
        if (!$email) {
            return;
        }

        $this->envoyer(
            $email,
            sprintf('Mise à jour de commande %s', $commande->getNumero()),
            sprintf(
                "Bonjour %s,\n\nVotre commande %s est maintenant %s.\n\nConsultez vos commandes : %s/commandes\n\n%s",
                $commande->getNomClient(),
                $commande->getNumero(),
                $libelleStatut,
                rtrim($this->frontendUrl, '/'),
                $this->mailFromName
            )
        );
    }

    private function envoyer(string $to, string $subject, string $text): void
    {
        try {
            $email = (new Email())
                ->from(sprintf('%s <%s>', $this->mailFromName, $this->mailFromAddress))
                ->to($to)
                ->subject($subject)
                ->text($text);

            $this->mailer->send($email);
        } catch (TransportExceptionInterface|\Throwable $e) {
            $this->logger->error('Échec envoi email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
