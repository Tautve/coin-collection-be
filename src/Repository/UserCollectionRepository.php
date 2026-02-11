<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Coin;
use App\Entity\User;
use App\Entity\UserCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCollection>
 */
class UserCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCollection::class);
    }

    /**
     * @return UserCollection[]
     */
    public function findByUser(User $user): array
    {
        /** @var UserCollection[] $result */
        $result = $this->createQueryBuilder('uc')
            ->andWhere('uc.user = :user')
            ->setParameter('user', $user->getId(), 'uuid')
            ->leftJoin('uc.coin', 'c')
            ->addSelect('c')
            ->orderBy('uc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findByUserAndCoin(User $user, Coin $coin): ?UserCollection
    {
        return $this->findOneBy(['user' => $user, 'coin' => $coin]);
    }

    public function findByCoin(Coin $coin): ?UserCollection
    {
        return $this->findOneBy(['coin' => $coin]);
    }

    /**
     * @return UserCollection[]
     */
    public function findAllWithCoins(): array
    {
        /** @var UserCollection[] $result */
        $result = $this->createQueryBuilder('uc')
            ->leftJoin('uc.coin', 'c')
            ->addSelect('c')
            ->leftJoin('uc.user', 'u')
            ->addSelect('u')
            ->orderBy('uc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }
}
