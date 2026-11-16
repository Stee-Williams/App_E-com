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

    /**
     * @return array{
     *     total: int,
     *     chiffreAffaires: float,
     *     commandesParMois: array<int, array{mois: string, total: int}>,
     *     statuts: array<string, int>
     * }
     */
    public function obtenirStatistiquesAdmin(): array
    {
        $connexion = $this->getEntityManager()->getConnection();
        $totaux = $connexion->executeQuery(
            'SELECT COUNT(id) AS total,
                COALESCE(SUM(CASE WHEN statut <> :annulee THEN total ELSE 0 END), 0) AS chiffre_affaires
             FROM commande',
            ['annulee' => Commande::STATUT_ANNULEE],
        )->fetchAssociative();

        $statuts = $connexion->executeQuery(
            'SELECT statut, COUNT(id) AS total FROM commande GROUP BY statut',
        )->fetchAllKeyValue();

        $commandesParMois = $connexion->executeQuery(
            "SELECT CASE WHEN date_creation IS NULL THEN 'inconnu' ELSE TO_CHAR(date_creation, 'YYYY-MM') END AS mois,
                COUNT(id) AS total
             FROM commande
             GROUP BY mois
             ORDER BY mois",
        )->fetchAllAssociative();

        return [
            'total' => (int) $totaux['total'],
            'chiffreAffaires' => round((float) $totaux['chiffre_affaires'], 2),
            'commandesParMois' => array_map(
                static fn (array $ligne): array => [
                    'mois' => (string) $ligne['mois'],
                    'total' => (int) $ligne['total'],
                ],
                $commandesParMois,
            ),
            'statuts' => array_map('intval', $statuts),
        ];
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

    public function creerRequeteListe(string $tri = 'recent', ?string $statut = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'u')->addSelect('u')
            ->leftJoin('c.lignes', 'l')->addSelect('l')
            ->leftJoin('l.produit', 'p')->addSelect('p');

        if ($statut !== null) {
            $qb->andWhere('c.statut = :statut')->setParameter('statut', $statut);
        }

        match ($tri) {
            'ancien' => $qb->orderBy('c.dateCreation', 'ASC'),
            'montant_asc' => $qb->orderBy('c.total', 'ASC'),
            'montant_desc' => $qb->orderBy('c.total', 'DESC'),
            'statut' => $qb->orderBy('c.statut', 'ASC')->addOrderBy('c.dateCreation', 'DESC'),
            default => $qb->orderBy('c.dateCreation', 'DESC'),
        };

        return $qb;
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

    public function findParSessionStripe(string $sessionId): ?Commande
    {
        return $this->findOneBy(['stripeSessionId' => $sessionId]);
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
