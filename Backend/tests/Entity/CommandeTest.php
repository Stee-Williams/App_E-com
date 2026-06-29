<?php

namespace App\Tests\Entity;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use PHPUnit\Framework\TestCase;

class CommandeTest extends TestCase
{
    public function testNomClientPourUtilisateurConnecte(): void
    {
        $utilisateur = new Utilisateur();
        $utilisateur->setPrenom('Marie');
        $utilisateur->setNom('Dupont');

        $commande = new Commande();
        $commande->setUtilisateur($utilisateur);

        self::assertSame('Marie Dupont', $commande->getNomClient());
    }

    public function testNomClientPourInvite(): void
    {
        $commande = new Commande();
        $commande->setPrenomInvite('Jean');
        $commande->setNomInvite('Martin');

        self::assertSame('Jean Martin', $commande->getNomClient());
    }
}
