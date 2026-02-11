<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\UserCollection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends Voter<string, UserCollection>
 */
class UserCollectionVoter extends Voter
{
    public const VIEW = 'COLLECTION_VIEW';
    public const EDIT = 'COLLECTION_EDIT';
    public const DELETE = 'COLLECTION_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof UserCollection;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var UserCollection $collection */
        $collection = $subject;

        // Super admins can do anything
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        // For regular users, check ownership
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::VIEW, self::EDIT, self::DELETE => $this->isOwner($collection, $user),
            default => false,
        };
    }

    private function isOwner(UserCollection $collection, User $user): bool
    {
        $owner = $collection->getUser();

        if ($owner === null) {
            return false;
        }

        return $owner->getId()->equals($user->getId());
    }
}
