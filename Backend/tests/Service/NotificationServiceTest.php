<?php

namespace App\Tests\Service;

use App\Entity\Commande;
use App\Entity\Notification;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Service\EmailService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class NotificationServiceTest extends TestCase
{
    public function testUneNouvelleCommandeNotifieLesAdministrateursUniquement(): void
    {
        $client = (new Utilisateur())->setRoles(['ROLE_USER']);
        $admin = (new Utilisateur())->setRoles(['ROLE_ADMIN']);
        $utilisateurs = $this->createMock(UtilisateurRepository::class);
        $utilisateurs->expects(self::once())
            ->method('findAll')
            ->willReturn([$client, $admin]);

        $notifications = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function (object $notification) use (&$notifications): void {
                $notifications[] = $notification;
            });
        $entityManager->expects(self::once())->method('flush');

        $commande = $this->createMock(Commande::class);
        $commande->method('getNumero')->willReturn('CMD-ABCD1234');
        $commande->method('getNomClient')->willReturn('Awa Diop');

        $service = new NotificationService(
            $entityManager,
            $utilisateurs,
            $this->createMock(EmailService::class),
        );

        $service->notifierNouvelleCommande($commande);

        self::assertCount(1, $notifications);
        self::assertInstanceOf(Notification::class, $notifications[0]);
        self::assertSame($admin, $notifications[0]->getUtilisateur());
        self::assertSame(Notification::TYPE_COMMANDE, $notifications[0]->getType());
        self::assertSame('Nouvelle commande', $notifications[0]->getTitre());
        self::assertSame('La commande CMD-ABCD1234 a été passée par Awa Diop.', $notifications[0]->getMessage());
        self::assertSame('/administration?onglet=commandes', $notifications[0]->getLien());
    }
}
