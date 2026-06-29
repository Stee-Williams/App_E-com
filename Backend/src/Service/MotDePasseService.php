<?php

namespace App\Service;

use App\Entity\Utilisateur;

class MotDePasseService
{
    public function hash(Utilisateur $utilisateur, string $motDePasse): string
    {
        return password_hash($motDePasse, PASSWORD_BCRYPT);
    }

    public function verify(Utilisateur $utilisateur, string $motDePasse): bool
    {
        $hash = $utilisateur->getPassword();

        return $hash !== null && password_verify($motDePasse, $hash);
    }
}
