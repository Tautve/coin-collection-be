<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserCollectionRepository;
use App\State\CollectionImageUploadProcessor;
use App\State\UserCollectionProcessor;
use App\State\UserCollectionProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(
            provider: UserCollectionProvider::class,
        ),
        new Get(
            security: "is_granted('COLLECTION_VIEW', object)",
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: UserCollectionProcessor::class,
        ),
        new Patch(
            security: "is_granted('COLLECTION_EDIT', object)",
        ),
        new Delete(
            security: "is_granted('COLLECTION_DELETE', object)",
        ),
        new Post(
            uriTemplate: '/user_collections/{id}/image',
            inputFormats: ['multipart' => ['multipart/form-data']],
            security: "is_granted('ROLE_USER')",
            processor: CollectionImageUploadProcessor::class,
            deserialize: false,
            name: 'collection_image_upload',
        ),
    ],
    normalizationContext: ['groups' => ['collection:read']],
    denormalizationContext: ['groups' => ['collection:write']],
)]
#[ORM\Entity(repositoryClass: UserCollectionRepository::class)]
#[ORM\Table(name: 'user_collections')]
#[ORM\UniqueConstraint(name: 'user_coin_unique', columns: ['user_id', 'coin_id'])]
#[ORM\HasLifecycleCallbacks]
class UserCollection
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['collection:read'])]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['collection:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'userCollections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['collection:read', 'collection:write'])]
    private ?Coin $coin = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['collection:read', 'collection:write'])]
    private ?\DateTimeImmutable $acquiredDate = null;

    #[ORM\Column(name: '`condition`', length: 100, nullable: true)]
    #[Groups(['collection:read', 'collection:write'])]
    private ?string $condition = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['collection:read', 'collection:write'])]
    private ?string $notes = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['collection:read', 'collection:write'])]
    private ?string $customImageUrl = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['collection:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCoin(): ?Coin
    {
        return $this->coin;
    }

    public function setCoin(?Coin $coin): static
    {
        $this->coin = $coin;
        return $this;
    }

    public function getAcquiredDate(): ?\DateTimeImmutable
    {
        return $this->acquiredDate;
    }

    public function setAcquiredDate(?\DateTimeImmutable $acquiredDate): static
    {
        $this->acquiredDate = $acquiredDate;
        return $this;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    public function setCondition(?string $condition): static
    {
        $this->condition = $condition;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCustomImageUrl(): ?string
    {
        return $this->customImageUrl;
    }

    public function setCustomImageUrl(?string $customImageUrl): static
    {
        $this->customImageUrl = $customImageUrl;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
