<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Utilisateur> */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function findByEmail(string $email): ?Utilisateur
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByJetonApi(string $jeton): ?Utilisateur
    {
        return $this->findOneBy(['jetonApi' => $jeton]);
    }

    public function findByJetonReinitialisation(string $jeton): ?Utilisateur
    {
        return $this->findOneBy(['jetonReinitialisation' => $jeton]);
    }

    public function creerRequeteListe(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC');
    }
}
