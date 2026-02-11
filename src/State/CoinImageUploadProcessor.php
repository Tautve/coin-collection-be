<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Coin;
use App\Repository\CoinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

/**
 * @implements ProcessorInterface<Coin, Coin>
 */
final class CoinImageUploadProcessor implements ProcessorInterface
{
    public function __construct(
        private CoinRepository $coinRepository,
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private string $uploadDir,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): Coin {
        $rawCoinId = $uriVariables['id'] ?? null;

        if (!is_string($rawCoinId) || !Uuid::isValid($rawCoinId)) {
            throw new BadRequestHttpException('Invalid coin ID.');
        }

        $coin = $this->coinRepository->find(Uuid::fromString($rawCoinId));
        if ($coin === null) {
            throw new NotFoundHttpException('Coin not found.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            throw new BadRequestHttpException('No request available.');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('image');

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
        $filename = sprintf('%s-%s.%s', $rawCoinId, time(), $extension);
        $subdir = 'coins';

        $targetDir = $this->uploadDir . '/' . $subdir;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $filename);

        $publicUrl = '/uploads/' . $subdir . '/' . $filename;
        $coin->setImageUrl($publicUrl);
        $this->entityManager->flush();

        return $coin;
    }
}
