<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Avis> */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * @param int[] $produitIds
     *
     * @return array<int, float>
     */
    public function trouverMoyennesParProduit(array $produitIds): array
    {
        if ($produitIds === []) {
            return [];
        }

        $resultats = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.produit) AS produitId, AVG(a.note) AS moyenne')
            ->where('a.produit IN (:produitIds)')
            ->setParameter('produitIds', $produitIds)
            ->groupBy('a.produit')
            ->getQuery()
            ->getArrayResult();

        $moyennes = [];
        foreach ($resultats as $resultat) {
            $moyennes[(int) $resultat['produitId']] = round((float) $resultat['moyenne'], 1);
        }

        return $moyennes;
    }
}
