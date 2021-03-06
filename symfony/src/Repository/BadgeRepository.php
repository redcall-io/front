<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Badge;
use App\Entity\Category;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Badge|null find($id, $lockMode = null, $lockVersion = null)
 * @method Badge|null findOneBy(array $criteria, array $orderBy = null)
 * @method Badge[]    findAll()
 * @method Badge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BadgeRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Badge::class);
    }

    public function getSearchInBadgesQueryBuilder(?string $criteria) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('b')
                   ->leftJoin('b.category', 'c')
                   ->addOrderBy('b.visibility', 'DESC')
                   ->addOrderBy('b.synonym', 'ASC')
                   ->addOrderBy('(1000 - c.priority) * 1000 + 1000 - b.renderingPriority', 'DESC')
                   ->addOrderBy('b.name', 'ASC')
                   ->groupBy('b.id');

        if ($criteria) {
            $this->addSearchCriteria($qb, $criteria);
        }

        return $qb;
    }

    public function getVolunteerCountInBadgeList(array $ids) : array
    {
        $rows = $this->createQueryBuilder('b')
                     ->select('b.id, COUNT(v) AS count')
                     ->join('b.volunteers', 'v')
                     ->andWhere('b.id IN (:ids)')
                     ->setParameter('ids', $ids)
                     ->groupBy('b.id')
                     ->getQuery()
                     ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['id']] = $row['count'];
        }

        return $counts;
    }

    public function search(?string $criteria, int $limit) : array
    {
        return $this->getSearchInBadgesQueryBuilder($criteria)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    public function searchForCompletion(?string $criteria, int $limit) : array
    {
        return $this->getSearchInBadgesQueryBuilder($criteria)
                    ->andWhere('b.synonym IS NULL')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    public function searchNonVisibleUsableBadge(?string $criteria, int $limit = 0) : array
    {
        return $this->getSearchInBadgesQueryBuilder($criteria)
                    ->andWhere('b.synonym IS NULL')
                    ->andWhere('b.visibility = false')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    public function getNonVisibleUsableBadgesList(array $ids)
    {
        return $this->getSearchInBadgesQueryBuilder(null)
                    ->andWhere('b.synonym IS NULL')
                    ->andWhere('b.visibility = false')
                    ->andWhere('b.id IN (:ids)')
                    ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY)
                    ->getQuery()
                    ->getResult();
    }

    public function getPublicBadgesQueryBuilder() : QueryBuilder
    {
        return $this->getSearchInBadgesQueryBuilder(null)
                    ->andWhere('b.visibility = true');
    }

    public function getBadgesInCategoryQueryBuilder(Category $category) : QueryBuilder
    {
        return $this->createQueryBuilder('b')
                    ->andWhere('b.visibility = true')
                    ->andWhere('b.synonym IS NULL')
                    ->join('b.category', 'c')
                    ->andWhere('c.id = :category')
                    ->setParameter('category', $category);
    }

    private function addSearchCriteria(QueryBuilder $qb, string $criteria)
    {
        $qb
            ->andWhere(
                $qb->expr()->orX(
                    'b.name LIKE :criteria',
                    'b.description LIKE :criteria',
                    'c.name LIKE :criteria'
                )
            )
            ->setParameter('criteria', sprintf('%%%s%%', str_replace(' ', '%', $criteria)));
    }
}
