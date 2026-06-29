<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Notification> */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /** @return Notification[] */
    public function findParUtilisateur(Utilisateur $utilisateur, int $limit = 30): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->orderBy('n.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function compterNonLues(Utilisateur $utilisateur): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.utilisateur = :utilisateur')
            ->andWhere('n.lu = false')
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
