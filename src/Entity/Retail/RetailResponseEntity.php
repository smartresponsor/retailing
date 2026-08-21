<?php

declare(strict_types=1);

namespace App\Retailing\Entity\Retail;

use App\Retailing\Repository\Retail\RetailResponseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RetailResponseRepository::class)]
#[ORM\Table(name: 'retail_response')]
#[ORM\Index(name: 'idx_retail_response_retail_status', columns: ['retail_id', 'status'])]
#[ORM\Index(name: 'idx_retail_response_vendor_status', columns: ['vendor_id', 'status'])]
#[ORM\UniqueConstraint(name: 'uniq_retail_response_retail_vendor', columns: ['retail_id', 'vendor_id'])]
final class RetailResponseEntity
{
    private const DRAFT = 'draft';
    private const SUBMITTED = 'submitted';
    private const WITHDRAWN = 'withdrawn';
    private const REJECTED = 'rejected';
    private const ACCEPTED = 'accepted';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: RetailEntity::class)]
    #[ORM\JoinColumn(name: 'retail_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RetailEntity $retail;

    #[ORM\Column(name: 'vendor_id', type: 'string', length: 64)]
    private string $vendorId;

    #[ORM\Column(name: 'service_id', type: 'integer', nullable: true)]
    private ?int $serviceId = null;

    #[ORM\Column(type: 'string', length: 16)]
    private string $status = self::DRAFT;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'pricing_profile', type: 'json', nullable: true)]
    private ?array $pricingProfile = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'fulfillment_profile', type: 'json', nullable: true)]
    private ?array $fulfillmentProfile = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'availability_profile', type: 'json', nullable: true)]
    private ?array $availabilityProfile = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'location_profile', type: 'json', nullable: true)]
    private ?array $locationProfile = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'submitted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(name: 'accepted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    public function __construct(RetailEntity $retail, string $vendorId)
    {
        $normalizedVendorId = trim($vendorId);
        if ('' === $normalizedVendorId) {
            throw new \InvalidArgumentException('Retail response vendor is required.');
        }
        if ('access' !== $retail->getOwnerType() || !in_array($retail->getKind()->value, ['task', 'project'], true)) {
            throw new \DomainException('Retail responses can target only customer-owned task or project requests.');
        }

        $this->retail = $retail;
        $this->vendorId = $normalizedVendorId;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): int { return $this->id; }
    public function getRetail(): RetailEntity { return $this->retail; }
    public function getVendorId(): string { return $this->vendorId; }
    public function getServiceId(): ?int { return $this->serviceId; }
    public function getStatus(): string { return $this->status; }
    public function getDescription(): ?string { return $this->description; }

    /** @return array<string, mixed>|null */
    public function getPricingProfile(): ?array { return $this->pricingProfile; }

    /** @return array<string, mixed>|null */
    public function getFulfillmentProfile(): ?array { return $this->fulfillmentProfile; }

    /** @return array<string, mixed>|null */
    public function getAvailabilityProfile(): ?array { return $this->availabilityProfile; }

    /** @return array<string, mixed>|null */
    public function getLocationProfile(): ?array { return $this->locationProfile; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getSubmittedAt(): ?\DateTimeImmutable { return $this->submittedAt; }
    public function getAcceptedAt(): ?\DateTimeImmutable { return $this->acceptedAt; }

    public function setService(?RetailEntity $service): void
    {
        $this->assertEditable();
        if (null !== $service) {
            if ('service' !== $service->getKind()->value || 'vendor' !== $service->getOwnerType() || $service->getOwner() !== $this->vendorId) {
                throw new \DomainException('Retail response service must be a service owned by the responding vendor.');
            }
            if ($service->getCategoryId() !== $this->retail->getCategoryId()) {
                throw new \DomainException('Retail response service category must match the customer request.');
            }
            if ($service->getId() <= 0) {
                throw new \DomainException('Retail response service must be persisted.');
            }
        }
        $this->serviceId = $service?->getId();
        $this->touch();
    }

    public function setDescription(?string $description): void
    {
        $this->assertEditable();
        $normalized = null === $description ? null : trim($description);
        $this->description = '' === $normalized ? null : $normalized;
        $this->touch();
    }

    /** @param array<string, mixed> $profile */
    public function setPricingProfile(array $profile): void
    {
        $this->assertEditable();
        $model = strtolower(trim((string) ($profile['model'] ?? $profile['mode'] ?? '')));
        if (!in_array($model, ['fixed', 'estimate', 'range', 'hourly', 'negotiable', 'quote'], true)) {
            throw new \InvalidArgumentException('Retail response pricing model is invalid.');
        }
        $currency = strtoupper(trim((string) ($profile['currency'] ?? $this->retail->getCurrency())));
        if (3 !== strlen($currency) || $currency !== $this->retail->getCurrency()) {
            throw new \DomainException('Retail response currency must match the customer request currency.');
        }

        $amountMinor = $this->pricingAmount($profile['amountMinor'] ?? $profile['minimumAmountMinor'] ?? null, 'amountMinor');
        $maximumAmountMinor = $this->pricingAmount($profile['maximumAmountMinor'] ?? null, 'maximumAmountMinor');
        $hourlyAmountMinor = $this->pricingAmount($profile['hourlyAmountMinor'] ?? null, 'hourlyAmountMinor');

        if (in_array($model, ['fixed', 'quote'], true) && null === $amountMinor) {
            throw new \InvalidArgumentException('Fixed or quote response requires amountMinor.');
        }
        if ('range' === $model && (null === $amountMinor || null === $maximumAmountMinor || $maximumAmountMinor < $amountMinor)) {
            throw new \InvalidArgumentException('Range response requires ordered amountMinor and maximumAmountMinor.');
        }
        if ('estimate' === $model && null === $amountMinor) {
            throw new \InvalidArgumentException('Estimate response requires amountMinor.');
        }
        if ('hourly' === $model && null === $hourlyAmountMinor && null === $amountMinor) {
            throw new \InvalidArgumentException('Hourly response requires hourlyAmountMinor.');
        }

        $profile['model'] = $model;
        $profile['currency'] = $currency;
        unset($profile['mode'], $profile['minimumAmountMinor']);
        if (null !== $amountMinor) {
            $profile['amountMinor'] = $amountMinor;
        } else {
            unset($profile['amountMinor']);
        }
        if (null !== $maximumAmountMinor) {
            $profile['maximumAmountMinor'] = $maximumAmountMinor;
        } else {
            unset($profile['maximumAmountMinor']);
        }
        if (null !== $hourlyAmountMinor) {
            $profile['hourlyAmountMinor'] = $hourlyAmountMinor;
        } else {
            unset($profile['hourlyAmountMinor']);
        }

        $this->pricingProfile = $profile;
        $this->touch();
    }

    private function pricingAmount(mixed $value, string $field): ?int
    {
        if (null === $value || '' === trim((string) $value)) {
            return null;
        }
        if (!is_numeric($value) || (int) $value < 0) {
            throw new \InvalidArgumentException(sprintf('Retail response %s must be a non-negative integer amount.', $field));
        }

        return (int) $value;
    }

    /** @param array<string, mixed>|null $profile */
    public function setFulfillmentProfile(?array $profile): void
    {
        $this->assertEditable();
        $this->fulfillmentProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touch();
    }

    /** @param array<string, mixed>|null $profile */
    public function setAvailabilityProfile(?array $profile): void
    {
        $this->assertEditable();
        $this->availabilityProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touch();
    }

    /** @param array<string, mixed>|null $profile */
    public function setLocationProfile(?array $profile): void
    {
        $this->assertEditable();
        $this->locationProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touch();
    }

    public function submit(): void
    {
        if (self::DRAFT !== $this->status || null === $this->pricingProfile) {
            throw new \DomainException('Only a priced draft retail response can be submitted.');
        }
        $this->status = self::SUBMITTED;
        $this->submittedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function withdraw(): void
    {
        if (!in_array($this->status, [self::DRAFT, self::SUBMITTED], true)) {
            throw new \DomainException('Only draft or submitted retail responses can be withdrawn.');
        }
        $this->status = self::WITHDRAWN;
        $this->touch();
    }

    public function reject(): void
    {
        if (self::SUBMITTED !== $this->status) {
            throw new \DomainException('Only submitted retail responses can be rejected.');
        }
        $this->status = self::REJECTED;
        $this->touch();
    }

    public function accept(): void
    {
        if (self::SUBMITTED !== $this->status) {
            throw new \DomainException('Only submitted retail responses can be accepted.');
        }
        $this->status = self::ACCEPTED;
        $this->acceptedAt = new \DateTimeImmutable();
        $this->touch();
    }

    private function assertEditable(): void
    {
        if (self::DRAFT !== $this->status) {
            throw new \DomainException('Only draft retail responses are editable.');
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
