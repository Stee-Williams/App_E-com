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

    /**
     * @param int[] $produitIds
     *
     * @return array<int, array{nombre: int, stockDisponible: int}>
     */
    public function trouverStocksParProduit(array $produitIds): array
    {
        if ($produitIds === []) {
            return [];
        }

        $resultats = $this->createQueryBuilder('v')
            ->select(
                'IDENTITY(v.produit) AS produitId',
                'COUNT(v.id) AS nombre',
                'SUM(CASE WHEN v.actif = true THEN v.stock ELSE 0 END) AS stockDisponible'
            )
            ->where('v.produit IN (:produitIds)')
            ->setParameter('produitIds', $produitIds)
            ->groupBy('v.produit')
            ->getQuery()
            ->getArrayResult();

        $stocks = [];
        foreach ($resultats as $resultat) {
            $stocks[(int) $resultat['produitId']] = [
                'nombre' => (int) $resultat['nombre'],
                'stockDisponible' => (int) $resultat['stockDisponible'],
            ];
        }

        return $stocks;
    }
}
