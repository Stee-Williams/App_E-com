<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class Paginateur
{
    public function depuisRequete(Request $request, int $defaut = 12, int $maximum = 50): array
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min($maximum, max(1, $request->query->getInt('limit', $defaut)));

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => ($page - 1) * $limit,
        ];
    }

    /**
     * @return array{items: array, total: int}
     */
    public function executer(QueryBuilder $qb, string $alias, int $page, int $limit): array
    {
        $countQb = clone $qb;
        $total = (int) $countQb
            ->select("COUNT(DISTINCT $alias.id)")
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => $total];
    }

    public function meta(int $total, int $page, int $limit): array
    {
        $pages = max(1, (int) ceil($total / max(1, $limit)));

        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => $pages,
        ];
    }
}
