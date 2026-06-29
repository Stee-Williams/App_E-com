<?php

namespace App\Repository;

use App\Entity\ElementListeSouhaits;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ElementListeSouhaits> */
class ElementListeSouhaitsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ElementListeSouhaits::class);
    }

    /** @return ElementListeSouhaits[] */
    public function findByUtilisateur(int $utilisateurId): array
    {
        return $this->creerRequeteParUtilisateur($utilisateurId)->getQuery()->getResult();
    }

    public function creerRequeteParUtilisateur(int $utilisateurId): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.produit', 'p')->addSelect('p')
            ->leftJoin('p.categorie', 'c')->addSelect('c')
            ->andWhere('e.utilisateur = :id')
            ->setParameter('id', $utilisateurId)
            ->orderBy('e.dateAjout', 'DESC');
    }
}
