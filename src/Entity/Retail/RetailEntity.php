<?php

declare(strict_types=1);

namespace App\Retailing\Entity\Retail;

use App\Objecting\EntityInterface\ObjectAuditedInterface;
use App\Objecting\EntityInterface\ObjectCodedInterface;
use App\Objecting\EntityInterface\ObjectStatefulInterface;
use App\Objecting\EntityTrait\Embeddable\ObjectAuditEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectCodeEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectStateEmbeddableTrait;
use App\Retailing\Enum\RetailKind;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'retail_listing')]
#[ORM\Index(name: 'idx_retail_owner_scope_kind', columns: ['owner_type', 'owner_id', 'kind'])]
#[ORM\Index(name: 'idx_retail_category_kind', columns: ['category_id', 'kind'])]
/**
 * Persists a customer request or vendor offering together with commercial and fulfillment profiles.
 */
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

    #[ORM\Column(name: 'owner_type', type: 'string', length: 32, nullable: true)]
    private ?string $ownerType = null;

    #[ORM\Column(name: 'owner_id', type: 'string', length: 64, nullable: true)]
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

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'availability_profile', type: 'json', nullable: true)]
    private ?array $availabilityProfile = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'selection_profile', type: 'json', nullable: true)]
    private ?array $selectionProfile = null;

    #[ORM\Column(name: 'publication_starts_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $publicationStartsAt = null;

    #[ORM\Column(name: 'publication_ends_at', type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $publicationEndsAt = null;

    public function __construct()
    {
        $this->initializeObjectCode();
        $this->initializeObjectState(true, true, 'draft');
        $this->initializeObjectAudit();
    }

    public function getId(): int
    {
        return $this->id;
    }
    public function getCode(): string
    {
        return $this->getObjectCode() ?? '';
    }
    public function getKind(): RetailKind
    {
        return $this->kind;
    }

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

    public function getOwnerType(): ?string
    {
        return $this->ownerType;
    }
    public function setOwnerType(?string $ownerType): void
    {
        $normalized = null === $ownerType ? null : strtolower(trim($ownerType));
        if (null !== $normalized && !in_array($normalized, ['vendor', 'access'], true)) {
            throw new \InvalidArgumentException('Retail owner type must be vendor or access.');
        }
        $this->ownerType = $normalized;
        $this->touchModified();
    }

    public function getOwner(): ?string
    {
        return $this->owner;
    }
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

    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }
    public function setCategoryId(?string $categoryId): void
    {
        $normalized = null === $categoryId ? null : trim($categoryId);
        $this->categoryId = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    public function getCatalogCode(): ?string
    {
        return $this->catalogCode;
    }
    public function setCatalogCode(?string $catalogCode): void
    {
        $normalized = null === $catalogCode ? null : strtolower(trim($catalogCode));
        $this->catalogCode = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    public function getTitle(): string
    {
        return $this->title;
    }
    public function setTitle(string $title): void
    {
        $normalized = trim($title);
        if ('' === $normalized) {
            throw new \InvalidArgumentException('Retail title is required.');
        }
        $this->title = $normalized;
        $this->touchModified();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): void
    {
        $normalized = null === $description ? null : trim($description);
        $this->description = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    public function getAmountMinor(): ?int
    {
        return $this->amountMinor;
    }
    public function setAmountMinor(?int $amountMinor): void
    {
        if (null !== $amountMinor && $amountMinor < 0) {
            throw new \InvalidArgumentException('Retail amount cannot be negative.');
        }
        $this->amountMinor = $amountMinor;
        $this->touchModified();
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function setCurrency(string $currency): void
    {
        $normalized = strtoupper(trim($currency));
        if (3 !== strlen($normalized)) {
            throw new \InvalidArgumentException('Retail currency must use a three-letter code.');
        }
        $this->currency = $normalized;
        $this->touchModified();
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }
    public function setLocation(?string $location): void
    {
        $normalized = null === $location ? null : trim($location);
        $this->location = '' === $normalized ? null : $normalized;
        $this->touchModified();
    }

    /** @return array<string, mixed>|null */
    public function getLocationProfile(): ?array
    {
        return $this->locationProfile;
    }

    /** @param array<string, mixed>|null $profile */
    public function setLocationProfile(?array $profile): void
    {
        $this->locationProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touchModified();
    }

    /** @return array<string, mixed>|null */
    public function getFulfillmentProfile(): ?array
    {
        return $this->fulfillmentProfile;
    }

    /** @param array<string, mixed>|null $profile */
    public function setFulfillmentProfile(?array $profile): void
    {
        $this->fulfillmentProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touchModified();
    }

    /** @return array<string, mixed>|null */
    public function getPricingProfile(): ?array
    {
        return $this->pricingProfile;
    }

    /** @param array<string, mixed>|null $profile */
    public function setPricingProfile(?array $profile): void
    {
        $this->pricingProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touchModified();
    }

    /** @return array<string, mixed>|null */
    public function getAvailabilityProfile(): ?array
    {
        return $this->availabilityProfile;
    }

    /** @param array<string, mixed>|null $profile */
    public function setAvailabilityProfile(?array $profile): void
    {
        $this->availabilityProfile = null === $profile || [] === $profile ? null : $profile;
        $this->touchModified();
    }

    /** @return array<string, mixed>|null */
    public function getSelectionProfile(): ?array
    {
        return $this->selectionProfile;
    }

    /**
     * Accepts a submitted vendor response after validating request, service, pricing, and ownership invariants.
     */
    public function acceptResponse(RetailResponseEntity $response, ?RetailEntity $service = null): void
    {
        if ('submitted' !== $response->getStatus()) {
            throw new \DomainException('Only a submitted retail response can be accepted.');
        }
        if (null !== $this->selectionProfile) {
            throw new \DomainException('Customer request already has accepted commercial terms.');
        }

        $this->assertResponseSelection($response, $service);
        $response->accept();
        $this->projectAcceptedResponse($response, $service);
    }

    /**
     * Rebuilds the customer selection projection from a response that is already accepted.
     */
    public function synchronizeAcceptedResponse(RetailResponseEntity $response, ?RetailEntity $service = null): void
    {
        if ('accepted' !== $response->getStatus()) {
            throw new \DomainException('Only an accepted retail response can synchronize customer selection.');
        }

        $this->assertResponseSelection($response, $service);
        $this->projectAcceptedResponse($response, $service);
    }

    private function assertResponseSelection(RetailResponseEntity $response, ?RetailEntity $service): void
    {
        if ($response->getRetail() !== $this) {
            throw new \DomainException('Retail response does not belong to this customer request.');
        }
        if ($response->getId() <= 0) {
            throw new \DomainException('Retail response must be persisted before acceptance.');
        }
        if (RetailKind::Task !== $this->kind && RetailKind::Project !== $this->kind) {
            throw new \DomainException('Only task or project requests can accept vendor responses.');
        }
        if ('access' !== $this->ownerType || 'published' !== $this->getObjectStatus()) {
            throw new \DomainException('Only a published customer request can accept a vendor response.');
        }
        if (null !== $service) {
            $serviceId = $response->getServiceId();
            if (RetailKind::Service !== $service->getKind() || 'vendor' !== $service->getOwnerType() || 'published' !== $service->getObjectStatus()) {
                throw new \DomainException('Accepted retail response must reference a published vendor service.');
            }
            if ($service->getId() <= 0 || $serviceId !== $service->getId() || $response->getVendorId() !== $service->getOwner()) {
                throw new \DomainException('Accepted retail response service identity does not match the responding vendor.');
            }
            if ($this->categoryId !== $service->getCategoryId()) {
                throw new \DomainException('Accepted retail response service category must match the customer request.');
            }
            if ($this->currency !== $service->getCurrency()) {
                throw new \DomainException('Accepted retail response currency must match the customer request.');
            }
        }

        $this->assertAcceptedPricingProfile($response->getPricingProfile(), $service);
    }

    /** @param array<string, mixed>|null $pricingProfile */
    private function assertAcceptedPricingProfile(?array $pricingProfile, ?RetailEntity $service): void
    {
        if (null === $pricingProfile) {
            throw new \DomainException('Accepted retail response requires commercial pricing terms.');
        }
        $model = strtolower(trim((string) ($pricingProfile['model'] ?? '')));
        if (!in_array($model, ['fixed', 'quote', 'estimate', 'range', 'hourly', 'negotiable'], true)) {
            throw new \DomainException('Accepted retail response pricing model is invalid.');
        }
        $currency = strtoupper(trim((string) ($pricingProfile['currency'] ?? '')));
        if ($currency !== $this->currency) {
            throw new \DomainException('Accepted retail response currency must match the customer request.');
        }

        $amount = $this->nonNegativePricingAmount($pricingProfile, 'amountMinor');
        $minimum = $this->nonNegativePricingAmount($pricingProfile, 'minimumAmountMinor');
        $maximum = $this->nonNegativePricingAmount($pricingProfile, 'maximumAmountMinor');
        $hourly = $this->nonNegativePricingAmount($pricingProfile, 'hourlyAmountMinor');

        if (in_array($model, ['fixed', 'quote'], true) && null === $amount) {
            throw new \DomainException('Fixed or quote response requires amountMinor.');
        }
        if ('range' === $model && (null === $minimum || null === $maximum || $minimum > $maximum)) {
            throw new \DomainException('Range response requires ordered minimumAmountMinor and maximumAmountMinor.');
        }
        if ('estimate' === $model && null === $amount && (null === $minimum || null === $maximum || $minimum > $maximum)) {
            throw new \DomainException('Estimate response requires amountMinor or an ordered amount range.');
        }
        if ('hourly' === $model && null === $hourly && null === $amount) {
            throw new \DomainException('Hourly response requires hourlyAmountMinor.');
        }

        if (null !== $service) {
            $serviceMinimum = $service->getPricingProfile()['minimumProjectAmountMinor'] ?? null;
            $responseFloor = $amount ?? $minimum;
            if (is_numeric($serviceMinimum) && null !== $responseFloor && $responseFloor < (int) $serviceMinimum) {
                throw new \DomainException('Accepted retail response amount cannot be below the service minimum project amount.');
            }
        }
    }

    /** @param array<string, mixed> $pricingProfile */
    private function nonNegativePricingAmount(array $pricingProfile, string $key): ?int
    {
        if (!array_key_exists($key, $pricingProfile) || null === $pricingProfile[$key] || '' === $pricingProfile[$key]) {
            return null;
        }
        if (!is_numeric($pricingProfile[$key]) || (int) $pricingProfile[$key] < 0) {
            throw new \DomainException(sprintf('Accepted retail response %s must be non-negative.', $key));
        }

        return (int) $pricingProfile[$key];
    }

    private function projectAcceptedResponse(RetailResponseEntity $response, ?RetailEntity $service): void
    {
        $pricingProfile = $response->getPricingProfile() ?? [];
        $serviceId = $service?->getId() ?? $response->getServiceId();
        $this->selectionProfile = [
            'responseId' => (string) $response->getId(),
            'vendorId' => $response->getVendorId(),
            'pricingProfile' => $pricingProfile,
            'fulfillmentProfile' => $response->getFulfillmentProfile(),
            'availabilityProfile' => $response->getAvailabilityProfile(),
            'locationProfile' => $response->getLocationProfile(),
            'acceptedAt' => $response->getAcceptedAt()?->format(DATE_ATOM),
            'currency' => $this->currency,
        ];
        if (null !== $serviceId) {
            $this->selectionProfile['serviceId'] = (string) $serviceId;
        }
        if (is_numeric($pricingProfile['amountMinor'] ?? null)) {
            $this->selectionProfile['agreedAmountMinor'] = (int) $pricingProfile['amountMinor'];
        }
        $this->touchModified();
    }

    /**
     * Selects a published vendor service directly for a customer task at an agreed amount.
     */
    public function selectServiceCandidate(RetailEntity $service, int $agreedAmountMinor): void
    {
        if (RetailKind::Task !== $this->kind || 'access' !== $this->ownerType || 'published' !== $this->getObjectStatus()) {
            throw new \DomainException('Only a published access-owned task can select a marketplace service.');
        }
        if (RetailKind::Service !== $service->getKind() || 'vendor' !== $service->getOwnerType() || 'published' !== $service->getObjectStatus()) {
            throw new \DomainException('Only a published vendor-owned service can be selected.');
        }
        if ($service->getId() <= 0 || null === $service->getOwner()) {
            throw new \DomainException('Selected marketplace service must be persisted and vendor-owned.');
        }
        if (null === $this->categoryId || $this->categoryId !== $service->getCategoryId()) {
            throw new \DomainException('Customer task and selected service must use the same category.');
        }
        if ($this->currency !== $service->getCurrency()) {
            throw new \DomainException('Customer task and selected service currencies must match.');
        }
        if ($agreedAmountMinor < 0) {
            throw new \InvalidArgumentException('Agreed service amount cannot be negative.');
        }

        $minimumAmount = $service->getPricingProfile()['minimumProjectAmountMinor'] ?? null;
        if (is_numeric($minimumAmount) && $agreedAmountMinor < (int) $minimumAmount) {
            throw new \DomainException('Agreed service amount cannot be below the service minimum project amount.');
        }

        $this->selectionProfile = [
            'serviceId' => (string) $service->getId(),
            'vendorId' => $service->getOwner(),
            'agreedAmountMinor' => $agreedAmountMinor,
            'currency' => $service->getCurrency(),
        ];
        $this->touchModified();
    }

    public function getPublicationStartsAt(): ?\DateTimeImmutable
    {
        return $this->publicationStartsAt;
    }

    public function getPublicationEndsAt(): ?\DateTimeImmutable
    {
        return $this->publicationEndsAt;
    }

    /**
     * Defines the optional effective publication window without changing lifecycle state.
     */
    public function schedulePublication(?\DateTimeImmutable $startsAt, ?\DateTimeImmutable $endsAt): void
    {
        if (null !== $startsAt && null !== $endsAt && $endsAt <= $startsAt) {
            throw new \InvalidArgumentException('Retail publication end must be later than its start.');
        }

        $this->publicationStartsAt = $startsAt;
        $this->publicationEndsAt = $endsAt;
        $this->touchModified();
    }

    /**
     * Reports whether a published listing is effective at the supplied instant.
     */
    public function isPublicationEffectiveAt(\DateTimeImmutable $at): bool
    {
        if ('published' !== $this->getObjectStatus()) {
            return false;
        }
        if (null !== $this->publicationStartsAt && $this->publicationStartsAt > $at) {
            return false;
        }
        if (null !== $this->publicationEndsAt && $this->publicationEndsAt <= $at) {
            return false;
        }

        return true;
    }

    /** @return list<string> */
    public function marketplaceIneligibilityReasonsAt(\DateTimeImmutable $at): array
    {
        $reasons = [];
        if ('published' !== $this->getObjectStatus()) {
            $reasons[] = 'not_published';
        }
        if (null !== $this->publicationStartsAt && $this->publicationStartsAt > $at) {
            $reasons[] = 'publication_not_started';
        }
        if (null !== $this->publicationEndsAt && $this->publicationEndsAt <= $at) {
            $reasons[] = 'publication_expired';
        }
        if ($this->catalogCode !== $this->kind->catalogCode()) {
            $reasons[] = 'catalog_mismatch';
        }

        $expectedOwnerType = match ($this->kind) {
            RetailKind::Task, RetailKind::Project => 'access',
            RetailKind::Service, RetailKind::Goods => 'vendor',
        };
        if ($this->ownerType !== $expectedOwnerType) {
            $reasons[] = 'owner_scope_mismatch';
        }

        return $reasons;
    }

    public function isMarketplaceEligibleAt(\DateTimeImmutable $at): bool
    {
        return [] === $this->marketplaceIneligibilityReasonsAt($at);
    }

    /**
     * Publishes a complete listing only after required ownership and commercial profiles are present.
     */
    public function publish(): void
    {
        if (
            null === $this->catalogCode
            || null === $this->categoryId
            || '' === $this->title
            || null === $this->ownerType
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

    /**
     * Returns a published listing to draft so it is no longer eligible for marketplace queries.
     */
    public function unpublish(): void
    {
        if ('published' !== $this->getObjectStatus()) {
            throw new \DomainException('Only a published retail listing can be unpublished.');
        }

        $this->setObjectStatus('draft');
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

    public function __toString(): string
    {
        return $this->title;
    }
}
