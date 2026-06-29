<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Produit> */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findVerrouillePourMiseAJour(int $id): ?Produit
    {
        return $this->createQueryBuilder('p')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    /**
     * @param array{
     *   q?: string|null,
     *   categorie?: int|null,
     *   prixMin?: float|null,
     *   prixMax?: float|null,
     *   promo?: bool,
     *   enStock?: bool,
     *   tri?: string,
     *   actifsSeulement?: bool
     * } $filtres
     */
    public function creerRequeteFiltree(array $filtres = []): QueryBuilder
    {
        $actifsSeulement = $filtres['actifsSeulement'] ?? true;

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c');

        if ($actifsSeulement) {
            $qb->andWhere('p.actif = true');
        }

        $recherche = isset($filtres['q']) ? trim((string) $filtres['q']) : '';
        if ($recherche !== '') {
            $termes = preg_split('/\s+/', mb_strtolower($recherche)) ?: [];
            foreach ($termes as $i => $terme) {
                if ($terme === '') {
                    continue;
                }
                $param = 'recherche' . $i;
                $qb->andWhere(
                    $qb->expr()->orX(
                        "LOWER(p.nom) LIKE :$param",
                        "LOWER(p.description) LIKE :$param",
                        "LOWER(c.nom) LIKE :$param"
                    )
                )->setParameter($param, '%' . $terme . '%');
            }
        }

        $categorieId = $filtres['categorie'] ?? null;
        if ($categorieId) {
            $qb->andWhere('p.categorie = :categorie')
                ->setParameter('categorie', $categorieId);
        }

        if (isset($filtres['prixMin']) && $filtres['prixMin'] !== null) {
            $qb->andWhere('COALESCE(p.prixPromo, p.prix) >= :prixMin')
                ->setParameter('prixMin', (string) $filtres['prixMin']);
        }

        if (isset($filtres['prixMax']) && $filtres['prixMax'] !== null) {
            $qb->andWhere('COALESCE(p.prixPromo, p.prix) <= :prixMax')
                ->setParameter('prixMax', (string) $filtres['prixMax']);
        }

        if (!empty($filtres['promo'])) {
            $qb->andWhere('p.prixPromo IS NOT NULL')
                ->andWhere('p.prixPromo < p.prix');
        }

        if (!empty($filtres['enStock'])) {
            $qb->andWhere('p.stock > 0');
        }

        $tri = $filtres['tri'] ?? 'recent';
        match ($tri) {
            'prix_asc' => $qb->orderBy('COALESCE(p.prixPromo, p.prix)', 'ASC'),
            'prix_desc' => $qb->orderBy('COALESCE(p.prixPromo, p.prix)', 'DESC'),
            'nom_asc' => $qb->orderBy('p.nom', 'ASC'),
            'nom_desc' => $qb->orderBy('p.nom', 'DESC'),
            default => $qb->orderBy('p.dateCreation', 'DESC'),
        };

        return $qb;
    }

    /**
     * @param array<string, mixed> $filtres
     *
     * @return Produit[]
     */
    public function findActifs(array $filtres = []): array
    {
        return $this->creerRequeteFiltree($filtres)->getQuery()->getResult();
    }

    /**
     * @return Produit[]
     */
    public function findSimilaires(Produit $produit, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->where('p.actif = true')
            ->andWhere('p.id != :id')
            ->andWhere('p.categorie = :categorie')
            ->setParameter('id', $produit->getId())
            ->setParameter('categorie', $produit->getCategorie())
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
