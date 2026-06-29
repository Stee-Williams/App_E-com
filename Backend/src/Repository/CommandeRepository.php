<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Commande> */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    /** @return Commande[] */
    public function findByUtilisateur(int $utilisateurId): array
    {
        return $this->creerRequeteParUtilisateur($utilisateurId)->getQuery()->getResult();
    }

    public function creerRequeteParUtilisateur(int $utilisateurId): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.lignes', 'l')->addSelect('l')
            ->leftJoin('l.produit', 'p')->addSelect('p')
            ->leftJoin('p.categorie', 'cat')->addSelect('cat')
            ->leftJoin('c.adresseLivraison', 'a')->addSelect('a')
            ->leftJoin('c.bonReduction', 'b')->addSelect('b')
            ->andWhere('c.utilisateur = :id')
            ->setParameter('id', $utilisateurId)
            ->orderBy('c.dateCreation', 'DESC');
    }

    public function creerRequeteListe(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'u')->addSelect('u')
            ->leftJoin('c.lignes', 'l')->addSelect('l')
            ->leftJoin('l.produit', 'p')->addSelect('p')
            ->orderBy('c.dateCreation', 'DESC');
    }

    public function findParJetonSuivi(string $jeton): ?Commande
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.lignes', 'l')->addSelect('l')
            ->leftJoin('l.produit', 'p')->addSelect('p')
            ->leftJoin('p.categorie', 'cat')->addSelect('cat')
            ->leftJoin('c.bonReduction', 'b')->addSelect('b')
            ->where('c.jetonSuivi = :jeton')
            ->setParameter('jeton', $jeton)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findParNumeroEtEmail(string $numero, string $email): ?Commande
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.lignes', 'l')->addSelect('l')
            ->leftJoin('l.produit', 'p')->addSelect('p')
            ->leftJoin('p.categorie', 'cat')->addSelect('cat')
            ->leftJoin('c.bonReduction', 'b')->addSelect('b')
            ->where('c.numero = :numero')
            ->andWhere('LOWER(c.emailInvite) = :email')
            ->setParameter('numero', $numero)
            ->setParameter('email', mb_strtolower($email))
            ->getQuery()
            ->getOneOrNullResult();
    }
}
