<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\UserCollection;
use App\Repository\UserCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<UserCollection, UserCollection>
 */
final class CollectionImageUploadProcessor implements ProcessorInterface
{
    public function __construct(
        private UserCollectionRepository $userCollectionRepository,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private string $uploadDir,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): UserCollection {
        $rawId = $uriVariables['id'] ?? null;

        if ($rawId instanceof Uuid) {
            $uuid = $rawId;
        } elseif (is_string($rawId) && Uuid::isValid($rawId)) {
            $uuid = Uuid::fromString($rawId);
        } else {
            throw new BadRequestHttpException('Invalid collection item ID.');
        }

        $collectionItem = $this->userCollectionRepository->find($uuid);
        if ($collectionItem === null) {
            throw new NotFoundHttpException('Collection item not found.');
        }

        $user = $this->security->getUser();
        if (
            !$user instanceof User
            || $collectionItem->getUser() === null
            || !$collectionItem->getUser()->getId()->equals($user->getId())
        ) {
            throw new AccessDeniedHttpException('You can only upload images to your own collection items.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new BadRequestHttpException('No request available.');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if ($file === null) {
            throw new BadRequestHttpException('No image file provided.');
        }

        if (!$file->isValid()) {
            throw new BadRequestHttpException('Invalid file upload.');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimes, true)) {
            throw new BadRequestHttpException(
                'Invalid file type. Allowed: jpeg, png, gif, webp'
            );
        }

        $extension = $file->guessExtension() ?? 'jpg';
        $filename = sprintf('%s-%s.%s', $uuid->toRfc4122(), time(), $extension);
        $subdir = 'collections';

        $targetDir = $this->uploadDir . '/' . $subdir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        $publicUrl = '/uploads/' . $subdir . '/' . $filename;
        $collectionItem->setCustomImageUrl($publicUrl);
        $this->entityManager->flush();

        return $collectionItem;
    }
}
