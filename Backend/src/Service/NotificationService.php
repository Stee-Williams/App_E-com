<?php

namespace App\Service;

use App\Entity\BonReduction;
use App\Entity\Commande;
use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private const STATUTS_LIBELLES = [
        Commande::STATUT_EN_ATTENTE => 'en attente',
        Commande::STATUT_CONFIRMEE => 'confirmée',
        Commande::STATUT_EXPEDIEE => 'expédiée',
        Commande::STATUT_LIVREE => 'livrée',
        Commande::STATUT_ANNULEE => 'annulée',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly EmailService $emailService,
    ) {
    }

    public function notifierStatutCommande(Commande $commande, string $ancienStatut): void
    {
        if ($commande->getStatut() === $ancienStatut) {
            return;
        }

        $libelle = self::STATUTS_LIBELLES[$commande->getStatut()] ?? $commande->getStatut();
        $this->emailService->envoyerMiseAJourStatutCommande($commande, $libelle);
        $utilisateur = $commande->getUtilisateur();
        if (!$utilisateur) {
            return;
        }

        $notification = new Notification();
        $notification->setUtilisateur($utilisateur);
        $notification->setType(Notification::TYPE_COMMANDE);
        $notification->setTitre('Mise à jour de commande');
        $notification->setMessage(sprintf(
            'Votre commande %s est maintenant %s.',
            $commande->getNumero(),
            $libelle
        ));
        $notification->setLien('/commandes');

        $this->em->persist($notification);
        $this->em->flush();
    }

    public function notifierPromo(BonReduction $bon): void
    {
        $utilisateurs = $this->utilisateurRepository->findAll();

        foreach ($utilisateurs as $utilisateur) {
            if (in_array('ROLE_ADMIN', $utilisateur->getRoles(), true)) {
                continue;
            }

            $notification = new Notification();
            $notification->setUtilisateur($utilisateur);
            $notification->setType(Notification::TYPE_PROMO);
            $notification->setTitre('Nouveau code promo');
            $notification->setMessage(sprintf(
                'Profitez du code %s sur votre prochaine commande !',
                $bon->getCode()
            ));
            $notification->setLien('/panier');

            $this->em->persist($notification);
        }

        $this->em->flush();
    }
}
