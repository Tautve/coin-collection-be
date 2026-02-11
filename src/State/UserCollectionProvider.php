<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\UserCollectionRepository;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @implements ProviderInterface<\App\Entity\UserCollection>
 */
final class UserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private UserCollectionRepository $userCollectionRepository,
        private Security $security,
    ) {
    }

    /**
     * @return \App\Entity\UserCollection[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->userCollectionRepository->findAllWithCoins();
        }

        if ($user instanceof User) {
            return $this->userCollectionRepository->findByUser($user);
        }

        return [];
    }
}
