<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CoinRepository;
use App\State\CoinImageUploadProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;

#[ApiResource(
    operations: [
        new GetCollection(order: ['year' => 'DESC'], paginationItemsPerPage: 20),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        new Post(
            uriTemplate: '/coins/{id}/image',
            inputFormats: ['multipart' => ['multipart/form-data']],
            security: "is_granted('ROLE_USER')",
            processor: CoinImageUploadProcessor::class,
            deserialize: false,
            name: 'coin_image_upload',
        ),
    ],
    normalizationContext: ['groups' => ['coin:read']],
    denormalizationContext: ['groups' => ['coin:write']],
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'denomination' => 'partial', 'year' => 'exact'])]
#[ORM\Entity(repositoryClass: CoinRepository::class)]
#[ORM\Table(name: 'coins')]
#[ORM\HasLifecycleCallbacks]
class Coin
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[Groups(['coin:read', 'collection:read'])]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?int $year = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?string $denomination = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?string $metal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?string $weightGrams = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?string $diameterMm = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?int $mintage = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['coin:read', 'coin:write', 'collection:read'])]
    private ?string $externalId = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['coin:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['coin:read'])]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, UserCollection> */
    #[ORM\OneToMany(targetEntity: UserCollection::class, mappedBy: 'coin', orphanRemoval: true)]
    private Collection $userCollections;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->userCollections = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getDenomination(): ?string
    {
        return $this->denomination;
    }

    public function setDenomination(?string $denomination): static
    {
        $this->denomination = $denomination;
        return $this;
    }

    public function getMetal(): ?string
    {
        return $this->metal;
    }

    public function setMetal(?string $metal): static
    {
        $this->metal = $metal;
        return $this;
    }

    public function getWeightGrams(): ?float
    {
        return $this->weightGrams !== null ? (float) $this->weightGrams : null;
    }

    public function setWeightGrams(?float $weightGrams): static
    {
        $this->weightGrams = $weightGrams !== null ? (string) $weightGrams : null;
        return $this;
    }

    public function getDiameterMm(): ?float
    {
        return $this->diameterMm !== null ? (float) $this->diameterMm : null;
    }

    public function setDiameterMm(?float $diameterMm): static
    {
        $this->diameterMm = $diameterMm !== null ? (string) $diameterMm : null;
        return $this;
    }

    public function getMintage(): ?int
    {
        return $this->mintage;
    }

    public function setMintage(?int $mintage): static
    {
        $this->mintage = $mintage;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** @return Collection<int, UserCollection> */
    public function getUserCollections(): Collection
    {
        return $this->userCollections;
    }
}
