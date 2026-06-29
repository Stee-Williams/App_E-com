<?php

namespace App\Repository;

use App\Entity\VarianteProduit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<VarianteProduit> */
class VarianteProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VarianteProduit::class);
    }

    public function findVerrouillePourMiseAJour(int $id): ?VarianteProduit
    {
        return $this->createQueryBuilder('v')
            ->where('v.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }
}
