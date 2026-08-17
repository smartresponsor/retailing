<?php

declare(strict_types=1);

namespace App\Retailing\Entity\Retail;

use App\Objecting\EntityInterface\ObjectAuditedInterface;
use App\Objecting\EntityInterface\ObjectCodedInterface;
use App\Objecting\EntityInterface\ObjectStatefulInterface;
use App\Objecting\EntityTrait\Embeddable\ObjectAuditEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectCodeEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectStateEmbeddableTrait;
use App\Retailing\Enum\Retail\RetailKind;
use App\Retailing\Repository\Retail\RetailRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RetailRepository::class)]
#[ORM\Table(name: 'retail')]
#[ORM\Index(name: 'idx_retail_owner_kind', columns: ['owner_vendor_id', 'kind'])]
#[ORM\Index(name: 'idx_retail_category_kind', columns: ['category_id', 'kind'])]
final class RetailEntity implements ObjectAuditedInterface, ObjectCodedInterface, ObjectStatefulInterface
{
    use ObjectAuditEmbeddableTrait;
    use ObjectCodeEmbeddableTrait;
    use ObjectStateEmbeddableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(enumType: RetailKind::class, length: 16)]
    private RetailKind $kind = RetailKind::Task;

    #[ORM\Column(name: 'owner_vendor_id', type: 'string', length: 64, nullable: true)]
    private ?string $owner = null;

    #[ORM\Column(name: 'category_id', type: 'string', length: 64, nullable: true)]
    private ?string $categoryId = null;

    #[ORM\Column(name: 'catalog_code', type: 'string', length: 64, nullable: true)]
    private ?string $catalogCode = 'services';

    #[ORM\Column(type: 'string', length: 180)]
    private string $title = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'amount_minor', type: 'bigint', nullable: true)]
    private ?int $amountMinor = null;

    #[ORM\Column(type: 'string', length: 3, options: ['default' => 'USD'])]
    private string $currency = 'USD';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'location_profile', type: 'json', nullable: true)]
    private ?array $locationProfile = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'fulfillment_profile', type: 'json', nullable: true)]
    private ?array $fulfillmentProfile = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'pricing_profile', type: 'json', nullable: true)]
    private ?array $pricingProfile = null;

    public function __construct()
    {
        $this->initializeObjectCode();
        $this->initializeObjectState(true, true, 'draft');
        $this->initializeObjectAudit();
    }

    public function getId(): int { return $this->id; }
    public function getCode(): string { return $this->getObjectCode() ?? ''; }
    public function getKind(): RetailKind { return $this->kind; }

    public function setKind(RetailKind $kind): void
    {
        if ('draft' !== $this->getObjectStatus()) {
            throw new \DomainException('Published retail kind is immutable.');
        }

        $previousCatalogCode = $this->kind->catalogCode();
        $this->kind = $kind;
        if (null === $this->catalogCode || $previousCatalogCode === $this->catalogCode) {
            $this->catalogCode = $kind->catalogCode();
        }
        $this->touchModified();
    }

    public function getOwner(): ?string { return $this->owner; }
    public function setOwner(object|string|null $owner): void
    {
        if (is_object($owner)) {
            if (!method_exists($owner, 'getId')) {
                throw new \InvalidArgumentException('Retail owner object must expose getId().');
            }
            $owner = $owner->getId();
        }

        $normalized = null === $owner ? null : trim((string) $owner);
        $this->owner = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    public function getCategoryId(): ?string { return $this->categoryId; }
    public function setCategoryId(?string $categoryId): void
    {
        $normalized = null === $categoryId ? null : trim($categoryId);
        $this->categoryId = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    public function getCatalogCode(): ?string { return $this->catalogCode; }
    public function setCatalogCode(?string $catalogCode): void
    {
        $normalized = null === $catalogCode ? null : strtolower(trim($catalogCode));
        $this->catalogCode = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void
    {
        $normalized = trim($title);
        if ('' === $normalized) {
            throw new \InvalidArgumentException('Retail title is required.');
        }
        $this->title = $normalized;
        $this->touchModified();
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): void
    {
        $normalized = null === $description ? null : trim($description);
        $this->description = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    public function getAmountMinor(): ?int { return $this->amountMinor; }
    public function setAmountMinor(?int $amountMinor): void
    {
        if (null !== $amountMinor && $amountMinor < 0) {
            throw new \InvalidArgumentException('Retail amount cannot be negative.');
        }
        $this->amountMinor = $amountMinor;
        $this->touchModified();
    }

    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): void
    {
        $normalized = strtoupper(trim($currency));
        if (3 !== strlen($normalized)) {
            throw new \InvalidArgumentException('Retail currency must use a three-letter code.');
        }
        $this->currency = $normalized;
        $this->touchModified();
    }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): void
    {
        $normalized = null === $location ? null : trim($location);
        $this->location = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    /** @return array<string, mixed>|null */
    public function getLocationProfile(): ?array { return $this->locationProfile; }

    /** @param array<string, mixed>|null $profile */
    public function setLocationProfile(?array $profile): void
    {
        $this->locationProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touchModified();
    }

    /** @return array<string, mixed>|null */
    public function getFulfillmentProfile(): ?array { return $this->fulfillmentProfile; }

    /** @param array<string, mixed>|null $profile */
    public function setFulfillmentProfile(?array $profile): void
    {
        $this->fulfillmentProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touchModified();
    }

    /** @return array<string, mixed>|null */
    public function getPricingProfile(): ?array { return $this->pricingProfile; }

    /** @param array<string, mixed>|null $profile */
    public function setPricingProfile(?array $profile): void
    {
        $this->pricingProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touchModified();
    }

    public function publish(): void
    {
        if (
            null === $this->catalogCode
            || null === $this->categoryId
            || '' === $this->title
            || null === $this->owner
            || null === $this->fulfillmentProfile
            || null === $this->pricingProfile
            || ($this->requiresExactLocation() && null === $this->locationProfile)
        ) {
            throw new \DomainException('Retail owner, catalog, category, title, required location, fulfillment, and pricing are required before publication.');
        }
        $this->setObjectStatus('published');
        $this->touchModified();
    }

    private function requiresExactLocation(): bool
    {
        $mode = is_string($this->fulfillmentProfile['mode'] ?? null) ? $this->fulfillmentProfile['mode'] : '';

        return match ($this->kind) {
            RetailKind::Goods => in_array($mode, ['shipping', 'pickup'], true),
            RetailKind::Task => in_array($mode, ['onsite', 'hybrid'], true),
            default => false,
        };
    }

    public function __toString(): string { return $this->title; }
}
