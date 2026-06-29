<?php

namespace App\Repository;

use App\Entity\BonReduction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<BonReduction> */
class BonReductionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BonReduction::class);
    }

    public function findByCode(string $code): ?BonReduction
    {
        return $this->findOneBy(['code' => strtoupper($code)]);
    }

    public function creerRequeteListe(): QueryBuilder
    {
        return $this->createQueryBuilder('b')
            ->orderBy('b.id', 'DESC');
    }
}
