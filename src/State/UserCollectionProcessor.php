<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\UserCollection;
use App\Repository\UserCollectionRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * @implements ProcessorInterface<UserCollection, UserCollection>
 */
final class UserCollectionProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<UserCollection, UserCollection> $persistProcessor
     */
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private UserCollectionRepository $userCollectionRepository,
    ) {
    }

    /**
     * @param UserCollection $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): UserCollection {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new BadRequestHttpException('User must be authenticated.');
        }

        $coin = $data->getCoin();
        if ($coin !== null && $this->userCollectionRepository->findByUserAndCoin($user, $coin) !== null) {
            throw new UnprocessableEntityHttpException('This coin is already in your collection.');
        }

        $data->setUser($user);

        /** @var UserCollection $result */
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        return $result;
    }
}
