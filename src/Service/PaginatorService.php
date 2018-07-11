<?php

declare(strict_types = 1);

namespace Jmoati\PaginatorBundle\Service;

use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;

class PaginatorService
{
    /**
     * @param QueryBuilder $queryBuilder
     * @return int
     */
    protected function getCount(QueryBuilder $queryBuilder): int
    {
        $qb = clone $queryBuilder;
        $qb->select('COUNT(DISTINCT '.$qb->getRootAliases()[0].'.id)');

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $offset
     * @param int $limit
     * @return array
     */
    protected function getIds(QueryBuilder $queryBuilder, int $offset, int $limit): array
    {
        $qb = clone $queryBuilder;

        $qb
            ->select('DISTINCT '.$qb->getRootAliases()[0].'.id')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        /** @var OrderBy $orderBy */
        foreach($qb->getDQLPart('orderBy') as $orderBy) {
            foreach($orderBy->getParts() as $part) {
                $part = explode(" ", $part)[0];
                $qb->addSelect($part);
            }
        }

        return array_column($qb->getQuery()->getResult(), 'id');
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $ids
     * @return array
     */
    public function getItems(QueryBuilder $queryBuilder, array $ids): array
    {
        $qb = clone $queryBuilder;

        $qb
            ->andWhere($qb->getRootAliases()[0].'.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function paginate(QueryBuilder $queryBuilder, int $page = 1, int $limit = 10): array
    {
        $offset = ($page-1) * $limit;

        return [
            'total' => $this->getCount($queryBuilder),
            'items' => $this->getItems($queryBuilder, $this->getIds($queryBuilder, $offset, $limit)),
        ];
    }
}
