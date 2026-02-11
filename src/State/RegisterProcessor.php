<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\RegisterInput;
use App\DTO\RegisterOutput;
use App\Entity\Profile;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<RegisterInput, RegisterOutput>
 */
final class RegisterProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @param RegisterInput $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): RegisterOutput {
        $email = trim($data->email);

        $existingUser = $this->userRepository->findByEmail($email);
        if ($existingUser !== null) {
            throw new ConflictHttpException('Email already registered.');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $data->password));

        $profile = new Profile();
        $profile->setUser($user);
        $user->setProfile($profile);

        $this->entityManager->persist($user);
        $this->entityManager->persist($profile);
        $this->entityManager->flush();

        $output = new RegisterOutput();
        $output->id = (string) $user->getId();
        $output->email = $user->getEmail();

        return $output;
    }
}
