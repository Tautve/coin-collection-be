<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Coin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Coin>
 */
class CoinRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Coin::class);
    }

    /**
     * @return Coin[]
     */
    public function findAllOrderedByYear(): array
    {
        /** @var Coin[] $result */
        $result = $this->createQueryBuilder('c')
            ->orderBy('c.year', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findByExternalId(string $externalId): ?Coin
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }
}
