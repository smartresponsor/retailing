<?php

declare(strict_types=1);

namespace App\Retailing\Tests\Unit;

use App\Cataloging\Entity\Catalog\CatalogCatalogEntity;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudMutationLifecycleContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cataloging\Entity\Catalog\CatalogCategoryEntity;
use App\Cataloging\ServiceInterface\CatalogCategoryLookupServiceInterface;
use App\Cataloging\ServiceInterface\CatalogCategoryVocabularyServiceInterface;
use App\Cataloging\ServiceInterface\CatalogSearchServiceInterface;
use App\Cataloging\ServiceInterface\CatalogCatalogTreeReadServiceInterface;
use App\Locating\ServiceInterface\Provider\Location\Runtime\Geo\LocationDistanceServiceInterface;
use App\Retailing\DependencyInjection\RetailingExtension;
use App\Retailing\Entity\Retail\RetailEntity;
use App\Retailing\Entity\Retail\RetailResponseEntity;
use App\Retailing\Enum\RetailKind;
use App\Retailing\Form\RetailType;
use App\Retailing\Service\Marketplace\RetailAvailabilityMatchService;
use App\Retailing\Service\Marketplace\RetailCandidateMatchService;
use App\Retailing\Service\Marketplace\RetailResponseAcceptanceService;
use App\Retailing\Factory\RetailOrderIntentFactory;
use App\Retailing\DataFixtures\RetailCustomerRequestFixtures;
use App\Retailing\DataFixtures\RetailMarketplaceFixtures;
use App\Retailing\DataFixtures\RetailResponseFixtures;
use App\Retailing\Kernel;
use App\Retailing\RetailingBundle;
use App\Retailing\Service\Http\Retail\RetailNewService;
use App\Retailing\Service\Marketplace\RetailServiceAreaMatchService;
use App\Retailing\Normalizer\RetailPricingProfileNormalizer;
use App\Retailing\Service\RetailCategoryVocabularyService;
use App\Retailing\Service\RetailKindVocabularyService;
use App\Retailing\Service\RetailService;
use App\Retailing\Service\RetailStorefrontFacetService;
use App\Retailing\Repository\RetailRepository;
use App\Retailing\EventSubscriber\RetailOwnershipSubscriber;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Retailing\ValueObject\RetailViewPayload;
use App\Retailing\Provider\RetailViewProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RetailingBehaviorTest extends TestCase
{
    /** @return iterable<string, array{RetailKind, string, string}> */
    public static function kindProvider(): iterable
    {
        yield 'task' => [RetailKind::Task, 'Task', 'services'];
        yield 'service' => [RetailKind::Service, 'Service', 'services'];
        yield 'goods' => [RetailKind::Goods, 'Product', 'products'];
        yield 'project' => [RetailKind::Project, 'Project', 'projects'];
    }

    #[DataProvider('kindProvider')]
    public function testLabelAndCatalogCode(RetailKind $kind, string $label, string $catalogCode): void
    {
        self::assertSame($label, $kind->label());
        self::assertSame($catalogCode, $kind->catalogCode());
    }

    public function testRetailEntityNormalizationAndPublication(): void
    {
        $retail = new RetailEntity();
        self::assertSame(0, $retail->getId());
        self::assertSame('', $retail->getCode());
        self::assertSame(RetailKind::Task, $retail->getKind());
        self::assertSame('services', $retail->getCatalogCode());
        self::assertSame('USD', $retail->getCurrency());

        $retail->setOwnerType(' ACCESS ');
        $retail->setOwner(' customer-1 ');
        $retail->setCategoryId(' category-1 ');
        $retail->setTitle('  Need help  ');
        $retail->setDescription('  Description  ');
        $retail->setAmountMinor(10000);
        $retail->setCurrency(' usd ');
        $retail->setLocation(' Houston ');
        $retail->setLocationProfile(['postalCode' => '77002']);
        $retail->setFulfillmentProfile(['mode' => 'remote']);
        $retail->setPricingProfile(['budgetAmountMinor' => 10000]);
        $retail->setAvailabilityProfile(['preferredWindows' => []]);

        self::assertSame('access', $retail->getOwnerType());
        self::assertSame('customer-1', $retail->getOwner());
        self::assertSame('category-1', $retail->getCategoryId());
        self::assertSame('Need help', $retail->getTitle());
        self::assertSame('Description', $retail->getDescription());
        self::assertSame(10000, $retail->getAmountMinor());
        self::assertSame('USD', $retail->getCurrency());
        self::assertSame('Houston', $retail->getLocation());
        self::assertSame(['postalCode' => '77002'], $retail->getLocationProfile());
        self::assertSame(['mode' => 'remote'], $retail->getFulfillmentProfile());
        self::assertSame(['budgetAmountMinor' => 10000], $retail->getPricingProfile());
        self::assertSame(['preferredWindows' => []], $retail->getAvailabilityProfile());
        self::assertSame('Need help', (string) $retail);

        $retail->publish();
        self::assertSame('published', $retail->getObjectStatus());

        $this->expectException(\DomainException::class);
        $retail->setKind(RetailKind::Project);
    }

    public function testRetailEntityPublicationLifecycleSupportsExplicitUnpublishAndRepublish(): void
    {
        $retail = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');

        $this->expectException(\DomainException::class);
        $retail->unpublish();
    }

    public function testRetailEntityCanRepublishAfterUnpublish(): void
    {
        $retail = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $retail->publish();
        $retail->unpublish();

        self::assertSame('draft', $retail->getObjectStatus());

        $retail->setTitle('Updated listing');
        $retail->publish();

        self::assertSame('published', $retail->getObjectStatus());
        self::assertSame('Updated listing', $retail->getTitle());
    }

    public function testRetailEntityScheduledPublicationWindowControlsEffectiveEligibility(): void
    {
        $retail = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $startsAt = new \DateTimeImmutable('2026-09-23T10:00:00+00:00');
        $endsAt = new \DateTimeImmutable('2026-09-24T10:00:00+00:00');

        $retail->schedulePublication($startsAt, $endsAt);
        $retail->publish();

        self::assertSame($startsAt, $retail->getPublicationStartsAt());
        self::assertSame($endsAt, $retail->getPublicationEndsAt());
        self::assertFalse($retail->isPublicationEffectiveAt(new \DateTimeImmutable('2026-09-23T09:59:59+00:00')));
        self::assertTrue($retail->isPublicationEffectiveAt(new \DateTimeImmutable('2026-09-23T10:00:00+00:00')));
        self::assertTrue($retail->isPublicationEffectiveAt(new \DateTimeImmutable('2026-09-24T09:59:59+00:00')));
        self::assertFalse($retail->isPublicationEffectiveAt(new \DateTimeImmutable('2026-09-24T10:00:00+00:00')));

        $retail->unpublish();
        self::assertFalse($retail->isPublicationEffectiveAt(new \DateTimeImmutable('2026-09-23T12:00:00+00:00')));
    }

    public function testRetailEntityRejectsInvalidScheduledPublicationWindow(): void
    {
        $retail = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');

        $this->expectException(\InvalidArgumentException::class);
        $retail->schedulePublication(
            new \DateTimeImmutable('2026-09-24T10:00:00+00:00'),
            new \DateTimeImmutable('2026-09-24T10:00:00+00:00'),
        );
    }

    public function testRetailEntityRejectsInvalidScalarState(): void
    {
        $retail = new RetailEntity();
        foreach ([
            fn() => $retail->setOwnerType('tenant'),
            fn() => $retail->setOwner(new class {}),
            fn() => $retail->setTitle(' '),
            fn() => $retail->setAmountMinor(-1),
            fn() => $retail->setCurrency('US'),
        ] as $operation) {
            try {
                $operation();
                self::fail('Expected validation exception.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testRetailEntityNullAndEmptyProfileNormalization(): void
    {
        $retail = new RetailEntity();
        $retail->setOwner(null);
        $retail->setCategoryId(' ');
        $retail->setCatalogCode(' ');
        $retail->setDescription(' ');
        $retail->setAmountMinor(null);
        $retail->setLocation(' ');
        $retail->setLocationProfile([]);
        $retail->setFulfillmentProfile(null);
        $retail->setPricingProfile([]);
        $retail->setAvailabilityProfile(null);

        self::assertNull($retail->getOwner());
        self::assertNull($retail->getCategoryId());
        self::assertNull($retail->getCatalogCode());
        self::assertNull($retail->getDescription());
        self::assertNull($retail->getAmountMinor());
        self::assertNull($retail->getLocation());
        self::assertNull($retail->getLocationProfile());
        self::assertNull($retail->getFulfillmentProfile());
        self::assertNull($retail->getPricingProfile());
        self::assertNull($retail->getAvailabilityProfile());
    }

    public function testOwnerObjectAndCatalogSynchronization(): void
    {
        $retail = new RetailEntity();
        $owner = new class {
            public function getId(): int
            {
                return 42;
            }
        };
        $retail->setOwner($owner);
        self::assertSame('42', $retail->getOwner());

        $retail->setKind(RetailKind::Goods);
        self::assertSame('products', $retail->getCatalogCode());
        $retail->setCatalogCode('custom');
        $retail->setKind(RetailKind::Project);
        self::assertSame('custom', $retail->getCatalogCode());
    }

    public function testServiceSelectionProjectsTermsAndEnforcesMinimum(): void
    {
        $task = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $task->setPricingProfile(['budgetAmountMinor' => 10000]);
        $task->publish();

        $service = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-9');
        $service->setPricingProfile(['mode' => 'fixed', 'minimumProjectAmountMinor' => 5000]);
        $service->publish();
        $this->setId($service, 77);

        $task->selectServiceCandidate($service, 7500);
        self::assertSame([
            'serviceId' => '77',
            'vendorId' => 'vendor-9',
            'agreedAmountMinor' => 7500,
            'currency' => 'USD',
        ], $task->getSelectionProfile());

        $otherTask = $this->completeListing(RetailKind::Task, 'access', 'customer-2');
        $otherTask->publish();
        $this->expectException(\DomainException::class);
        $otherTask->selectServiceCandidate($service, 4999);
    }

    public function testExactLocationRequirement(): void
    {
        $goods = $this->completeListing(RetailKind::Goods, 'vendor', 'vendor-1');
        $goods->setFulfillmentProfile(['mode' => 'shipping']);
        try {
            $goods->publish();
            self::fail('Shipping goods require location profile.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }
        $goods->setLocationProfile(['postalCode' => '77002']);
        $goods->publish();
        self::assertSame('published', $goods->getObjectStatus());
    }

    public function testRetailResponseLifecycle(): void
    {
        $request = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $response = new RetailResponseEntity($request, ' vendor-1 ');
        self::assertSame($request, $response->getRetail());
        self::assertSame('vendor-1', $response->getVendorId());
        self::assertSame('draft', $response->getStatus());
        self::assertNull($response->getServiceId());
        self::assertNull($response->getDescription());
        self::assertInstanceOf(\DateTimeImmutable::class, $response->getCreatedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $response->getUpdatedAt());
        self::assertNull($response->getSubmittedAt());
        self::assertNull($response->getAcceptedAt());

        $response->setDescription('  Proposal  ');
        $response->setPricingProfile(['mode' => 'fixed', 'amountMinor' => 5000]);
        $response->setFulfillmentProfile(['mode' => 'remote']);
        $response->setAvailabilityProfile(['weeklyWindows' => []]);
        $response->setLocationProfile(['postalCode' => '77002']);
        self::assertSame('Proposal', $response->getDescription());
        self::assertSame(['amountMinor' => 5000, 'model' => 'fixed', 'currency' => 'USD'], $response->getPricingProfile());
        self::assertSame(['mode' => 'remote'], $response->getFulfillmentProfile());
        self::assertSame(['weeklyWindows' => []], $response->getAvailabilityProfile());
        self::assertSame(['postalCode' => '77002'], $response->getLocationProfile());

        $response->submit();
        self::assertSame('submitted', $response->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $response->getSubmittedAt());
        $response->accept();
        self::assertSame('accepted', $response->getStatus());
        self::assertInstanceOf(\DateTimeImmutable::class, $response->getAcceptedAt());
    }

    public function testRetailResponseTransitionsAndValidation(): void
    {
        $request = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $draft = new RetailResponseEntity($request, 'vendor-1');
        $draft->withdraw();
        self::assertSame('withdrawn', $draft->getStatus());

        $submitted = new RetailResponseEntity($request, 'vendor-2');
        $submitted->setPricingProfile(['model' => 'quote', 'amountMinor' => 100]);
        $submitted->submit();
        $submitted->reject();
        self::assertSame('rejected', $submitted->getStatus());

        $invalid = new RetailResponseEntity($request, 'vendor-3');
        try {
            $invalid->setPricingProfile(['model' => 'bogus']);
            self::fail('Invalid pricing model accepted.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }
        try {
            $invalid->submit();
            self::fail('Unpriced response submitted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }
    }

    public function testRetailResponseServiceValidation(): void
    {
        $request = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $response = new RetailResponseEntity($request, 'vendor-1');
        $service = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $this->setId($service, 91);
        $response->setService($service);
        self::assertSame(91, $response->getServiceId());
        $response->setService(null);
        self::assertNull($response->getServiceId());

        $wrongVendor = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-2');
        $this->setId($wrongVendor, 92);
        $this->expectException(\DomainException::class);
        $response->setService($wrongVendor);
    }

    public function testRetailResponseCoversValidationAndTerminalStateGuards(): void
    {
        $request = $this->completeListing(RetailKind::Task, 'access', 'customer-guard');

        foreach (['', '   '] as $vendorId) {
            try {
                new RetailResponseEntity($request, $vendorId);
                self::fail('Blank vendor id accepted.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }

        $vendorOwned = $this->completeListing(RetailKind::Task, 'vendor', 'vendor-1');
        try {
            new RetailResponseEntity($vendorOwned, 'vendor-1');
            self::fail('Vendor-owned request accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $goods = $this->completeListing(RetailKind::Goods, 'access', 'customer-1');
        try {
            new RetailResponseEntity($goods, 'vendor-1');
            self::fail('Goods request accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $response = new RetailResponseEntity($request, 'vendor-1');
        $response->setDescription(null);
        self::assertNull($response->getDescription());
        $response->setDescription('   ');
        self::assertNull($response->getDescription());
        $response->setFulfillmentProfile([]);
        $response->setFulfillmentProfile(null);
        $response->setAvailabilityProfile(null);
        $response->setAvailabilityProfile([]);
        $response->setLocationProfile([]);
        $response->setLocationProfile(null);
        self::assertNull($response->getFulfillmentProfile());
        self::assertNull($response->getAvailabilityProfile());
        self::assertNull($response->getLocationProfile());

        $response->setPricingProfile(['mode' => 'quote']);
        $pricingProfile = $response->getPricingProfile();
        self::assertNotNull($pricingProfile);
        self::assertArrayHasKey('model', $pricingProfile);
        self::assertSame('quote', $pricingProfile['model']);
        self::assertArrayNotHasKey('mode', $pricingProfile);

        try {
            $response->setPricingProfile(['model' => 'fixed', 'currency' => 'EUR']);
            self::fail('Currency mismatch accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $wrongKind = $this->completeListing(RetailKind::Task, 'vendor', 'vendor-1');
        $this->setId($wrongKind, 601);
        try {
            $response->setService($wrongKind);
            self::fail('Non-service candidate accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $wrongOwnerType = $this->completeListing(RetailKind::Service, 'access', 'vendor-1');
        $this->setId($wrongOwnerType, 603);
        try {
            $response->setService($wrongOwnerType);
            self::fail('Access-owned service accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $wrongVendor = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-2');
        $this->setId($wrongVendor, 604);
        try {
            $response->setService($wrongVendor);
            self::fail('Service owned by another vendor accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $wrongCategory = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $wrongCategory->setCategoryId('other-category');
        $this->setId($wrongCategory, 602);
        try {
            $response->setService($wrongCategory);
            self::fail('Mismatched service category accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $notPersisted = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        try {
            $response->setService($notPersisted);
            self::fail('Transient service accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $response->submit();
        foreach ([
            fn() => $response->setDescription('late'),
            fn() => $response->submit(),
        ] as $operation) {
            try {
                $operation();
                self::fail('Invalid submitted-state operation accepted.');
            } catch (\DomainException) {
                self::addToAssertionCount(1);
            }
        }

        $response->withdraw();
        try {
            $response->withdraw();
            self::fail('Withdrawn response withdrawn twice.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }
        try {
            $response->reject();
            self::fail('Withdrawn response rejected.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }
        try {
            $response->accept();
            self::fail('Withdrawn response accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }
    }

    public function testAvailabilityMatching(): void
    {
        $matcher = new RetailAvailabilityMatchService();
        self::assertSame('requires_scheduling', $matcher->match(null, null));
        self::assertSame('compatible', $matcher->match(
            ['preferredWindows' => ['Monday' => [['start' => '09:00', 'end' => '11:00']]]],
            ['weeklyWindows' => ['monday' => [['start' => '10:00', 'end' => '12:00']]]],
        ));
        self::assertNull($matcher->match(
            ['preferredWindows' => ['Monday' => [['start' => '09:00', 'end' => '10:00']]]],
            ['weeklyWindows' => ['monday' => [['start' => '10:00', 'end' => '12:00']]]],
        ));
        self::assertNull($matcher->match(
            ['preferredWindows' => ['Monday' => [['start' => 'bad', 'end' => '10:00']]]],
            ['weeklyWindows' => ['monday' => [['start' => '09:00', 'end' => '12:00']]]],
        ));
    }

    public function testServiceAreaMatching(): void
    {
        $distance = $this->createStub(LocationDistanceServiceInterface::class);
        $distance->method('meters')->willReturn(1000.0);
        $matcher = new RetailServiceAreaMatchService($distance);

        self::assertSame(['status' => 'requires_geovalidation', 'distanceMeters' => null], $matcher->match(['postalCode' => '77002'], null));
        self::assertSame(['status' => 'exact', 'distanceMeters' => null], $matcher->match(['postalCode' => '77002'], ['mode' => 'postal_codes', 'postalCodes' => ['77001', '77002']]));
        self::assertNull($matcher->match(['postalCode' => '77002'], ['mode' => 'postal_codes', 'postalCodes' => ['10001']]));
        self::assertSame(['status' => 'exact', 'distanceMeters' => 0.0], $matcher->match(['postalCode' => '77002'], ['mode' => 'radius', 'origin' => ['postalCode' => '77002']]));
        self::assertSame(['status' => 'exact', 'distanceMeters' => 1000.0], $matcher->match(
            ['latitude' => 29.75, 'longitude' => -95.36],
            ['mode' => 'radius', 'origin' => ['latitude' => 29.76, 'longitude' => -95.37], 'radiusMiles' => 1],
        ));
        self::assertSame(['status' => 'requires_geovalidation', 'distanceMeters' => null], $matcher->match([], ['mode' => 'other']));
    }

    public function testPricingNormalization(): void
    {
        $normalizer = new RetailPricingProfileNormalizer();
        self::assertSame([
            'version' => 1,
            'kind' => 'service',
            'model' => 'fixed',
            'amountMinor' => 1250,
            'currency' => 'USD',
        ], $normalizer->normalize(' SERVICE ', ['model' => 'fixed', 'amountMinor' => '1250']));
        self::assertSame([
            'version' => 1,
            'kind' => 'task',
            'model' => 'range',
            'amountMinor' => 1000,
            'maximumAmountMinor' => 2000,
            'currency' => 'EUR',
        ], $normalizer->normalize('task', ['model' => 'range', 'amountMinor' => 1000, 'maximumAmountMinor' => 2000, 'currency' => 'eur']));

        foreach ([
            fn() => $normalizer->normalize('unknown', ['model' => 'fixed']),
            fn() => $normalizer->normalize('goods', ['model' => 'hourly', 'amountMinor' => 1]),
            fn() => $normalizer->normalize('goods', ['model' => 'fixed', 'currency' => 'US', 'amountMinor' => 1]),
            fn() => $normalizer->normalize('goods', ['model' => 'fixed', 'amountMinor' => -1]),
            fn() => $normalizer->normalize('task', ['model' => 'range', 'amountMinor' => 2, 'maximumAmountMinor' => 1]),
            fn() => $normalizer->normalize('service', ['model' => 'fixed']),
        ] as $operation) {
            try {
                $operation();
                self::fail('Invalid pricing input accepted.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testOrderIntentAndViewPayload(): void
    {
        $task = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $service = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $service->setAmountMinor(2500);
        $service->setPricingProfile(['mode' => 'fixed']);
        $this->setId($service, 12);

        $intent = (new RetailOrderIntentFactory())->forCandidate($task, $service);
        self::assertSame('ready', $intent['status']);
        self::assertNotNull($intent['payload']);
        self::assertSame('25.00', $intent['payload']['items'][0]['price']);
        self::assertSame('retail:12', $intent['payload']['items'][0]['sku']);

        $service->setDescription('Description');
        $service->setLocation('Houston');
        $payload = new RetailViewPayload($service);
        self::assertSame($payload->toTemplateContext(), $payload->toFallbackData());
        self::assertSame('service', $payload->toFallbackData()['kind']);
        self::assertSame('Service', $payload->toFallbackData()['kindLabel']);
    }

    public function testKindVocabularyMapsAndFallsBack(): void
    {
        $catalog = $this->createStub(CatalogCategoryVocabularyServiceInterface::class);
        $catalog->method('publishedCategories')->willReturn([
            ['code' => 'product', 'label' => 'Products'],
            ['code' => 'task', 'label' => 'Tasks'],
            ['code' => 'unknown', 'label' => 'Ignored'],
            ['code' => 'service', 'label' => ''],
        ]);
        $service = new RetailKindVocabularyService($catalog);
        self::assertSame([
            'Products' => RetailKind::Goods,
            'Tasks' => RetailKind::Task,
        ], $service->choices());

        $empty = $this->createStub(CatalogCategoryVocabularyServiceInterface::class);
        $empty->method('publishedCategories')->willReturn([]);
        self::assertCount(4, (new RetailKindVocabularyService($empty))->choices());

        $failing = $this->createStub(CatalogCategoryVocabularyServiceInterface::class);
        $failing->method('publishedCategories')->willThrowException(new \RuntimeException('catalog unavailable'));
        self::assertCount(4, (new RetailKindVocabularyService($failing))->choices());
    }

    public function testCategoryVocabularyUsesPublishedMetadataTypes(): void
    {
        $catalog = new CatalogCatalogEntity('retailing', 'Retailing', 'Marketplace vocabulary');
        $category = new CatalogCategoryEntity($catalog, 'Services', 'services', 'services', 0);
        $category->setMetadata([
            'types' => [
                'invalid',
                ['label' => '', 'sourceCategoryId' => 'ignored'],
                ['label' => 'Root', 'sourceCategoryId' => 'root-id', 'types' => [
                    ['label' => 'Child', 'sourceCategoryId' => 'child-id'],
                    ['label' => 'Group only', 'types' => [
                        ['label' => 'Grandchild', 'sourceCategoryId' => 42],
                    ]],
                ]],
            ],
        ]);

        $lookup = $this->createStub(CatalogCategoryLookupServiceInterface::class);
        $lookup->method('publishedByCatalogAndPath')->willReturn($category);
        $legacy = $this->createStub(CatalogCatalogTreeReadServiceInterface::class);
        $service = new RetailCategoryVocabularyService($lookup, $legacy);

        $expected = [
            'Root' => 'root-id',
            'Root › Child' => 'child-id',
            'Root › Group only › Grandchild' => '42',
        ];
        foreach (RetailKind::cases() as $kind) {
            self::assertSame($expected, $service->choicesForKind($kind));
        }
        self::assertCount(4, $service->choices());
        self::assertTrue($service->contains(RetailKind::Service, ' root-id '));
    }

    public function testCategoryVocabularyUsesLegacyTreeFallback(): void
    {
        $lookup = $this->createStub(CatalogCategoryLookupServiceInterface::class);
        $lookup->method('publishedByCatalogAndPath')->willReturn(null);
        $legacy = $this->createStub(CatalogCatalogTreeReadServiceInterface::class);
        $legacy->method('byCode')->willReturn([
            'nodes' => [
                'invalid',
                ['nodeId' => '', 'title' => 'Missing id'],
                ['nodeId' => 'missing-title', 'title' => ''],
                [
                    'nodeId' => 'root',
                    'title' => 'Root',
                    'children' => [
                        ['nodeId' => 'child', 'title' => 'Child'],
                        ['nodeId' => 'leaf', 'title' => 'Leaf', 'children' => 'invalid'],
                    ],
                ],
            ],
        ]);

        $service = new RetailCategoryVocabularyService($lookup, $legacy);
        self::assertSame([
            'Root' => 'root',
            'Root › Child' => 'child',
            'Root › Leaf' => 'leaf',
        ], $service->choicesForKind(RetailKind::Task));
        self::assertTrue($service->contains(RetailKind::Task, ' child '));
        self::assertFalse($service->contains(RetailKind::Task, 'missing'));
        self::assertNotEmpty($service->choices());
    }

    public function testCategoryVocabularyHandlesBrokenLegacySource(): void
    {
        $lookup = $this->createStub(CatalogCategoryLookupServiceInterface::class);
        $lookup->method('publishedByCatalogAndPath')->willThrowException(new \RuntimeException('lookup unavailable'));
        $legacy = $this->createStub(CatalogCatalogTreeReadServiceInterface::class);
        $legacy->method('byCode')->willThrowException(new \RuntimeException('tree unavailable'));

        $service = new RetailCategoryVocabularyService($lookup, $legacy);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('tree unavailable');
        $service->choicesForKind(RetailKind::Goods);
    }

    public function testAcceptedResponseProjectsCommercialSelection(): void
    {
        $task = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $task->publish();
        $service = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $service->setPricingProfile(['mode' => 'fixed', 'minimumProjectAmountMinor' => 5000]);
        $service->publish();
        $this->setId($service, 501);

        $response = new RetailResponseEntity($task, 'vendor-1');
        $response->setService($service);
        $response->setDescription('Commercial response');
        $response->setPricingProfile(['model' => 'fixed', 'amountMinor' => 7500]);
        $response->setFulfillmentProfile(['mode' => 'remote']);
        $response->setAvailabilityProfile(['weeklyWindows' => []]);
        $response->setLocationProfile(['postalCode' => '77002']);
        $response->submit();
        $this->setResponseId($response, 901);

        $task->acceptResponse($response, $service);
        self::assertSame('accepted', $response->getStatus());
        self::assertSame('901', $task->getSelectionProfile()['responseId'] ?? null);
        self::assertSame('501', $task->getSelectionProfile()['serviceId'] ?? null);
        self::assertSame('vendor-1', $task->getSelectionProfile()['vendorId'] ?? null);
        self::assertSame(7500, $task->getSelectionProfile()['agreedAmountMinor'] ?? null);
        self::assertSame('USD', $task->getSelectionProfile()['currency'] ?? null);

        $task->synchronizeAcceptedResponse($response, $service);
        self::assertSame('901', $task->getSelectionProfile()['responseId'] ?? null);
    }

    public function testAcceptedResponsePricingValidationRejectsIncompleteTerms(): void
    {
        $task = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $task->publish();
        $response = new RetailResponseEntity($task, 'vendor-1');
        $response->setPricingProfile(['model' => 'fixed']);
        $response->submit();
        $this->setResponseId($response, 902);

        $this->expectException(\DomainException::class);
        $task->acceptResponse($response);
    }

    public function testAcceptedResponsePricingModelsAndBounds(): void
    {
        foreach ([
            ['model' => 'range', 'minimumAmountMinor' => 100, 'maximumAmountMinor' => 50, 'currency' => 'USD'],
            ['model' => 'estimate', 'minimumAmountMinor' => 200, 'maximumAmountMinor' => 100, 'currency' => 'USD'],
            ['model' => 'hourly', 'currency' => 'USD'],
            ['model' => 'fixed', 'amountMinor' => -1, 'currency' => 'USD'],
            ['model' => 'unsupported', 'amountMinor' => 100, 'currency' => 'USD'],
        ] as $index => $profile) {
            $task = $this->completeListing(RetailKind::Task, 'access', 'customer-' . $index);
            $task->publish();
            $response = new RetailResponseEntity($task, 'vendor-' . $index);
            if ('unsupported' === $profile['model']) {
                $profile['model'] = 'negotiable';
                $response->setPricingProfile($profile);
                $profile['model'] = 'unsupported';
                $property = new \ReflectionProperty($response, 'pricingProfile');
                $property->setValue($response, $profile);
            } else {
                $response->setPricingProfile($profile);
            }
            $response->submit();
            $this->setResponseId($response, 1000 + $index);

            try {
                $task->acceptResponse($response);
                self::fail('Invalid accepted pricing terms were accepted.');
            } catch (\DomainException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testViewProviderBuildsNeutralViewingPayload(): void
    {
        $retail = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $view = (new RetailViewProvider())->provide($retail);

        self::assertSame('retail', $view->surface);
        self::assertSame('view', $view->operation);
        self::assertSame('service', $view->intent);
        self::assertSame('Retailing', $view->component);
        self::assertArrayHasKey('retail', $view->data);
        self::assertInstanceOf(RetailViewPayload::class, $view->data['retail']);
    }

    public function testRetailOwnershipSubscriberLifecycle(): void
    {
        $crudContext = new CrudContextDTO('front', 'create', 'retail', RetailEntity::class, 'id', null, null);
        $request = new Request();
        $retail = new RetailEntity();

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new class implements UserInterface {
            public function getId(): int
            {
                return 42;
            }

            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void {}

            public function getUserIdentifier(): string
            {
                return 'vendor-42';
            }
        });
        $subscriber = new RetailOwnershipSubscriber($security);
        $create = new CrudMutationLifecycleContextDTO($crudContext, $retail, $request, 'create');
        self::assertTrue($subscriber->supports($create));
        $subscriber->before($create);
        self::assertSame('42', $retail->getOwner());
        $subscriber->after($create);

        $other = new CrudMutationLifecycleContextDTO($crudContext, new \stdClass(), $request, 'create');
        self::assertFalse($subscriber->supports($other));
        $subscriber->before($other);

        $retailEdit = new RetailEntity();
        $edit = new CrudMutationLifecycleContextDTO($crudContext, $retailEdit, $request, 'edit');
        $subscriber->before($edit);
        self::assertNull($retailEdit->getOwner());
    }

    public function testRetailOwnershipSubscriberRejectsMissingOrInvalidActor(): void
    {
        $crudContext = new CrudContextDTO('front', 'create', 'retail', RetailEntity::class, 'id', null, null);
        $request = new Request();

        $missingSecurity = $this->createStub(Security::class);
        $missingSecurity->method('getUser')->willReturn(null);
        try {
            (new RetailOwnershipSubscriber($missingSecurity))->before(
                new CrudMutationLifecycleContextDTO($crudContext, new RetailEntity(), $request, 'create'),
            );
            self::fail('Anonymous retail creation accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $noIdSecurity = $this->createStub(Security::class);
        $noIdSecurity->method('getUser')->willReturn(new class implements UserInterface {
            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void {}

            public function getUserIdentifier(): string
            {
                return 'no-id';
            }
        });
        try {
            (new RetailOwnershipSubscriber($noIdSecurity))->before(
                new CrudMutationLifecycleContextDTO($crudContext, new RetailEntity(), $request, 'create'),
            );
            self::fail('Actor without getId accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $invalidSecurity = $this->createStub(Security::class);
        $invalidSecurity->method('getUser')->willReturn(new class implements UserInterface {
            public function getId(): \stdClass
            {
                return new \stdClass();
            }

            public function getRoles(): array
            {
                return [];
            }

            public function eraseCredentials(): void {}

            public function getUserIdentifier(): string
            {
                return 'invalid';
            }
        });
        $this->expectException(\DomainException::class);
        (new RetailOwnershipSubscriber($invalidSecurity))->before(
            new CrudMutationLifecycleContextDTO($crudContext, new RetailEntity(), $request, 'create'),
        );
    }

    public function testRetailTypeConfiguresCanonicalEntityDefaults(): void
    {
        $kindSource = $this->createStub(CatalogCategoryVocabularyServiceInterface::class);
        $kindVocabulary = new RetailKindVocabularyService($kindSource);
        $categoryLookup = $this->createStub(CatalogCategoryLookupServiceInterface::class);
        $legacyTree = $this->createStub(CatalogCatalogTreeReadServiceInterface::class);
        $categoryVocabulary = new RetailCategoryVocabularyService($categoryLookup, $legacyTree);
        $type = new RetailType($kindVocabulary, $categoryVocabulary);
        $resolver = new OptionsResolver();

        $type->configureOptions($resolver);
        $resolved = $resolver->resolve();

        self::assertSame(RetailEntity::class, $resolved['data_class']);
        self::assertTrue($resolved['csrf_protection']);
    }

    public function testRetailTypeBuildsCanonicalFields(): void
    {
        $kindSource = $this->createStub(CatalogCategoryVocabularyServiceInterface::class);
        $categoryLookup = $this->createStub(CatalogCategoryLookupServiceInterface::class);
        $legacyTree = $this->createStub(CatalogCatalogTreeReadServiceInterface::class);
        $type = new RetailType(
            new RetailKindVocabularyService($kindSource),
            new RetailCategoryVocabularyService($categoryLookup, $legacyTree),
        );
        $builder = $this->createStub(FormBuilderInterface::class);
        $builder->method('add')->willReturn($builder);
        $builder->method('addEventListener')->willReturn($builder);

        $type->buildForm($builder, []);
        self::addToAssertionCount(1);
    }

    public function testRetailServicePersistsAndRemoves(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturn(new ClassMetadata(RetailEntity::class));
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($entityManager);
        $repository = new RetailRepository($registry);
        $retail = new RetailEntity();
        $service = new RetailService($repository);

        $entityManager->expects(self::once())->method('persist')->with($retail);
        $entityManager->expects(self::once())->method('remove')->with($retail);
        $entityManager->expects(self::exactly(2))->method('flush');

        self::assertSame($retail, $service->save($retail));
        $service->remove($retail);
    }

    public function testRetailingExtensionAliasAndEnvironmentLoading(): void
    {
        $extension = new RetailingExtension();
        self::assertSame('retailing', $extension->getAlias());

        foreach (['prod', 'dev', 'test'] as $environment) {
            $container = new ContainerBuilder();
            $container->setParameter('kernel.environment', $environment);
            $extension->load([], $container);
            self::assertNotEmpty($container->getDefinitions());
        }
    }

    public function testRetailingExtensionRejectsNonStringEnvironment(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 123);

        $this->expectException(\LogicException::class);
        (new RetailingExtension())->load([], $container);
    }

    public function testOrderIntentRejectsInvalidParticipantsAndCommercialTerms(): void
    {
        $factory = new RetailOrderIntentFactory();
        $task = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $service = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $this->setId($service, 701);

        foreach ([
            fn() => $factory->forCandidate($this->completeListing(RetailKind::Goods, 'access', 'customer-1'), $service),
            fn() => $factory->forCandidate($task, $this->completeListing(RetailKind::Task, 'vendor', 'vendor-1')),
        ] as $operation) {
            try {
                $operation();
                self::fail('Invalid order-intent participant accepted.');
            } catch (\InvalidArgumentException) {
                self::addToAssertionCount(1);
            }
        }

        $missingCustomer = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $missingCustomer->setOwner(null);
        try {
            $factory->forCandidate($missingCustomer, $service);
            self::fail('Missing customer identity accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $eurService = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $eurService->setCurrency('EUR');
        try {
            $factory->forCandidate($task, $eurService);
            self::fail('Currency mismatch accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $service->setPricingProfile(['mode' => 'quote']);
        $service->setAmountMinor(null);
        self::assertSame(['status' => 'agreed_price_required', 'payload' => null], $factory->forCandidate($task, $service));

        try {
            $factory->forCandidate($task, $service, -1);
            self::fail('Negative agreed amount accepted.');
        } catch (\InvalidArgumentException) {
            self::addToAssertionCount(1);
        }

        $selection = new \ReflectionProperty($task, 'selectionProfile');
        $selection->setValue($task, [
            'serviceId' => '701',
            'vendorId' => 'vendor-1',
            'agreedAmountMinor' => 4321,
            'currency' => 'USD',
        ]);
        $ready = $factory->forCandidate($task, $service);
        self::assertSame('ready', $ready['status']);
        self::assertSame('43.21', $ready['payload']['items'][0]['price'] ?? null);

        foreach ([
            ['serviceId' => '999', 'vendorId' => 'vendor-1', 'agreedAmountMinor' => 100, 'currency' => 'USD'],
            ['serviceId' => '701', 'vendorId' => 'other-vendor', 'agreedAmountMinor' => 100, 'currency' => 'USD'],
            ['serviceId' => '701', 'vendorId' => 'vendor-1', 'agreedAmountMinor' => 100, 'currency' => 'EUR'],
        ] as $invalidSelection) {
            $selection->setValue($task, $invalidSelection);
            self::assertSame(
                ['status' => 'agreed_price_required', 'payload' => null],
                $factory->forCandidate($task, $service),
            );
        }
    }

    public function testFixtureGuardsGroupsAndDeterministicProfiles(): void
    {
        $objectManager = $this->createStub(ObjectManager::class);
        $customer = new RetailCustomerRequestFixtures();
        $marketplace = new RetailMarketplaceFixtures();

        self::assertSame(['retailing_customer_requests'], RetailCustomerRequestFixtures::getGroups());
        self::assertSame(['retailing_marketplace'], RetailMarketplaceFixtures::getGroups());
        $customer->load($objectManager);
        $marketplace->load($objectManager);
        self::addToAssertionCount(2);

        $customerAvailability = new \ReflectionMethod($customer, 'availability');
        self::assertSame('10:00', $customerAvailability->invoke($customer, 'emily.customer@smartresponsor.local')['preferredWindows']['saturday'][0]['start']);
        self::assertArrayHasKey('thursday', $customerAvailability->invoke($customer, 'james.customer@smartresponsor.local')['preferredWindows']);
        self::assertArrayHasKey('tuesday', $customerAvailability->invoke($customer, 'sophia.customer@smartresponsor.local')['preferredWindows']);
        self::assertSame('16:00', $customerAvailability->invoke($customer, 'other@example.test')['preferredWindows']['saturday'][0]['end']);

        $availability = new \ReflectionMethod($marketplace, 'availability');
        self::assertSame(24, $availability->invoke($marketplace, 'Katy Home Care')['minimumLeadHours']);
        self::assertSame(4, $availability->invoke($marketplace, 'OneTasker Houston')['minimumLeadHours']);

        $serviceArea = new \ReflectionMethod($marketplace, 'serviceArea');
        self::assertSame('radius', $serviceArea->invoke($marketplace, 'Katy Home Care')['mode']);
        self::assertContains('77024', $serviceArea->invoke($marketplace, 'Bayou Assembly & Mounting')['postalCodes']);
        self::assertContains('77493', $serviceArea->invoke($marketplace, 'OneTasker Houston')['postalCodes']);

        $description = new \ReflectionMethod($marketplace, 'description');
        self::assertStringContainsString('TV Mounting by OneTasker Houston', $description->invoke($marketplace, 'OneTasker Houston', 'TV Mounting'));

        $acceptance = (new \ReflectionClass(RetailResponseAcceptanceService::class))->newInstanceWithoutConstructor();
        $responses = new RetailResponseFixtures($acceptance);
        self::assertSame(['retailing_responses'], RetailResponseFixtures::getGroups());
        $this->expectException(\RuntimeException::class);
        $responses->load($objectManager);
    }

    public function testCustomerRequestFixturesLoadPublishedTasksWithoutDatabase(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            11,
            'cat-1',
            12,
            'cat-2',
            13,
            'cat-3',
            14,
            'cat-4',
        );
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $persisted = [];
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getConnection')->willReturn($connection);
        $manager->method('getRepository')->willReturn($repository);
        $manager->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        (new RetailCustomerRequestFixtures())->load($manager);

        self::assertCount(4, $persisted);
        foreach ($persisted as $entity) {
            self::assertInstanceOf(RetailEntity::class, $entity);
            self::assertSame(RetailKind::Task, $entity->getKind());
            self::assertSame('published', $entity->getObjectStatus());
            self::assertSame('access', $entity->getOwnerType());
        }
    }

    public function testMarketplaceFixturesLoadPublishedServicesWithoutDatabase(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            101,
            'cat-1',
            'cat-2',
            'cat-3',
            'cat-4',
            'cat-5',
            'cat-6',
            'cat-7',
            'cat-8',
            false,
            false,
        );
        $repository = $this->createStub(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $persisted = [];
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getConnection')->willReturn($connection);
        $manager->method('getRepository')->willReturn($repository);
        $manager->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });

        (new RetailMarketplaceFixtures())->load($manager);

        self::assertCount(8, $persisted);
        self::assertContainsOnlyInstancesOf(RetailEntity::class, $persisted);
        /** @var list<RetailEntity> $persisted */
        self::assertSame('TV Mounting', $persisted[0]->getTitle());
        self::assertSame(RetailKind::Service, $persisted[0]->getKind());
        self::assertSame('published', $persisted[0]->getObjectStatus());
        self::assertSame('vendor', $persisted[0]->getOwnerType());
        self::assertArrayHasKey('serviceCallAmountMinor', $persisted[1]->getPricingProfile() ?? []);
    }

    public function testResponseFixturesFailFastWhenRequiredCustomerIsMissing(): void
    {
        $connection = $this->createStub(Connection::class);
        $connection->method('fetchOne')->willReturn(false);
        $repository = $this->createStub(EntityRepository::class);
        $manager = $this->createStub(EntityManagerInterface::class);
        $manager->method('getConnection')->willReturn($connection);
        $manager->method('getRepository')->willReturn($repository);
        $acceptance = (new \ReflectionClass(RetailResponseAcceptanceService::class))->newInstanceWithoutConstructor();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Marketplace response fixture customer is missing');
        (new RetailResponseFixtures($acceptance))->load($manager);
    }

    public function testResponseAcceptanceRejectsInvalidLifecycleBeforePersistenceAccess(): void
    {
        $service = (new \ReflectionClass(RetailResponseAcceptanceService::class))->newInstanceWithoutConstructor();
        $task = $this->completeListing(RetailKind::Task, 'access', 'customer-1');
        $draft = new RetailResponseEntity($task, 'vendor-1');

        try {
            $service->accept($draft);
            self::fail('Draft response accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $draft->setPricingProfile(['model' => 'quote', 'amountMinor' => 100]);
        $draft->submit();
        try {
            $service->accept($draft);
            self::fail('Transient submitted response accepted.');
        } catch (\DomainException) {
            self::addToAssertionCount(1);
        }

        $other = new RetailResponseEntity($task, 'vendor-2');
        $this->expectException(\DomainException::class);
        $service->synchronizeAccepted($other);
    }

    public function testAvailabilityMatcherCoversMalformedAndMissingWindows(): void
    {
        $matcher = new RetailAvailabilityMatchService();

        self::assertNull($matcher->match(
            ['preferredWindows' => [0 => [['start' => '09:00', 'end' => '10:00']], 'monday' => 'invalid']],
            ['weeklyWindows' => ['tuesday' => [['start' => '09:00', 'end' => '10:00']]]],
        ));
        self::assertNull($matcher->match(
            ['preferredWindows' => ['monday' => ['invalid', ['start' => '09:00', 'end' => '10:00']]]],
            ['weeklyWindows' => ['monday' => ['invalid']]],
        ));
        self::assertNull($matcher->match(
            ['preferredWindows' => ['monday' => [['start' => '10:00', 'end' => '09:00']]]],
            ['weeklyWindows' => ['monday' => [['start' => '08:00', 'end' => '11:00']]]],
        ));
    }

    public function testServiceAreaMatcherCoversValidationAndDistanceBoundaries(): void
    {
        $distance = $this->createStub(LocationDistanceServiceInterface::class);
        $distance->method('meters')->willReturnOnConsecutiveCalls(40000.0, 1000.0);
        $matcher = new RetailServiceAreaMatchService($distance);

        self::assertSame(['status' => 'requires_geovalidation', 'distanceMeters' => null], $matcher->match(null, ['mode' => 'postal_codes', 'postalCodes' => ['77002']]));
        self::assertSame(['status' => 'requires_geovalidation', 'distanceMeters' => null], $matcher->match(['postalCode' => []], ['mode' => 'postal_codes', 'postalCodes' => ['77002']]));
        self::assertSame(['status' => 'requires_geovalidation', 'distanceMeters' => null], $matcher->match(null, ['mode' => 'radius', 'origin' => ['latitude' => 29.7, 'longitude' => -95.3], 'radiusMiles' => 10]));
        self::assertSame(['status' => 'requires_geovalidation', 'distanceMeters' => null], $matcher->match(['postalCode' => '77002'], ['mode' => 'postal_codes', 'postalCodes' => 'invalid']));
        self::assertSame(['status' => 'requires_geovalidation', 'distanceMeters' => null], $matcher->match(
            ['geoPoint' => ['lat' => 91, 'lng' => -95]],
            ['mode' => 'radius', 'origin' => ['lat' => 29.7, 'lng' => -95.3], 'radiusMiles' => 10],
        ));
        self::assertSame(['status' => 'requires_geovalidation', 'distanceMeters' => null], $matcher->match(
            ['geoPoint' => ['lat' => 'invalid', 'lng' => -95]],
            ['mode' => 'radius', 'origin' => ['lat' => 29.7, 'lng' => -95.3], 'radiusMiles' => 10],
        ));
        self::assertNull($matcher->match(
            ['geoPoint' => ['lat' => 29.7, 'lng' => -95.3]],
            ['mode' => 'radius', 'origin' => ['lat' => 29.8, 'lng' => -95.4], 'radiusMiles' => 10],
        ));
        self::assertSame(['status' => 'exact', 'distanceMeters' => 1000.0], $matcher->match(
            ['geoPoint' => ['lat' => '29.7', 'lon' => '-95.3']],
            ['mode' => 'radius', 'origin' => ['latitude' => 29.8, 'longitude' => -95.4], 'radiusMiles' => 10],
        ));
    }

    public function testRetailNewPlacementHandoffForVendorAndActorFallback(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/crud/fulfillment/new');
        $candidateMatcher = (new \ReflectionClass(RetailCandidateMatchService::class))->newInstanceWithoutConstructor();
        $service = new RetailNewService($urlGenerator, $candidateMatcher, new RetailOrderIntentFactory());
        $afterDefault = new \ReflectionMethod($service, 'afterDefault');

        $vendor = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-77');
        $this->setId($vendor, 77);
        $request = Request::create('/retail/new', 'POST');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $context = new CrudServiceContextDTO(
            $request,
            new CrudContextDTO('front', 'create', 'retail', RetailEntity::class, 'id', null, null),
            $vendor,
        );
        $result = CrudServiceResultDTO::response(new RedirectResponse('/original'));
        $returned = $afterDefault->invoke($service, $context, $result);
        self::assertInstanceOf(RedirectResponse::class, $returned->payload());
        self::assertSame('/crud/fulfillment/new', $returned->payload()->getTargetUrl());
        $placement = $request->getSession()->get('retail_placement');
        self::assertSame('77', $placement['retailId'] ?? null);
        self::assertSame('vendor-77', $placement['vendorId'] ?? null);
        self::assertArrayNotHasKey('tenantId', $placement);

        $goods = $this->completeListing(RetailKind::Goods, 'access', 'temporary');
        $goods->setOwner(null);
        $this->setId($goods, 88);
        $fallbackRequest = Request::create('/retail/new', 'POST');
        $fallbackRequest->attributes->set('_crud_actor_identity_value', 515);
        $fallbackRequest->setSession(new Session(new MockArraySessionStorage()));
        $fallbackContext = new CrudServiceContextDTO(
            $fallbackRequest,
            new CrudContextDTO('front', 'create', 'retail', RetailEntity::class, 'id', null, null),
            $goods,
        );
        $afterDefault->invoke($service, $fallbackContext, $result);
        self::assertSame('515', $fallbackRequest->getSession()->get('retail_placement')['ownerId'] ?? null);

        $userIdGoods = $this->completeListing(RetailKind::Goods, 'access', 'temporary');
        $userIdGoods->setOwner(null);
        $this->setId($userIdGoods, 89);
        $userIdRequest = Request::create('/retail/new', 'POST');
        $userIdRequest->attributes->set('_crud_actor_user_id', 616);
        $userIdRequest->setSession(new Session(new MockArraySessionStorage()));
        $userIdContext = new CrudServiceContextDTO(
            $userIdRequest,
            new CrudContextDTO('front', 'create', 'retail', RetailEntity::class, 'id', null, null),
            $userIdGoods,
        );
        $afterDefault->invoke($service, $userIdContext, $result);
        self::assertSame('616', $userIdRequest->getSession()->get('retail_placement')['ownerId'] ?? null);

        $getRequest = Request::create('/retail/new', 'GET');
        $getContext = new CrudServiceContextDTO(
            $getRequest,
            new CrudContextDTO('front', 'create', 'retail', RetailEntity::class, 'id', null, null),
            $goods,
        );
        self::assertSame($result, $afterDefault->invoke($service, $getContext, $result));
    }

    public function testRetailNewGuardPathsAndPublishedTaskWithoutCategory(): void
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/crud/fulfillment/new');
        $distance = $this->createStub(LocationDistanceServiceInterface::class);
        $candidateMatcher = new RetailCandidateMatchService(
            (new \ReflectionClass(RetailRepository::class))->newInstanceWithoutConstructor(),
            new RetailServiceAreaMatchService($distance),
            new RetailAvailabilityMatchService(),
        );
        $service = new RetailNewService($urlGenerator, $candidateMatcher, new RetailOrderIntentFactory());
        $afterDefault = new \ReflectionMethod($service, 'afterDefault');
        $crudContext = new CrudContextDTO('front', 'create', 'retail', RetailEntity::class, 'id', null, null);

        $post = Request::create('/retail/new', 'POST');
        $plainResult = CrudServiceResultDTO::continueDefault();
        self::assertSame($plainResult, $afterDefault->invoke($service, new CrudServiceContextDTO($post, $crudContext, new RetailEntity()), $plainResult));

        $redirect = CrudServiceResultDTO::response(new RedirectResponse('/original'));
        self::assertSame($redirect, $afterDefault->invoke($service, new CrudServiceContextDTO($post, $crudContext, new \stdClass()), $redirect));

        $draft = $this->completeListing(RetailKind::Goods, 'access', 'owner-1');
        $postWithSession = Request::create('/retail/new', 'POST');
        $postWithSession->setSession(new Session(new MockArraySessionStorage()));
        self::assertSame($redirect, $afterDefault->invoke($service, new CrudServiceContextDTO($postWithSession, $crudContext, $draft), $redirect));

        $this->setId($draft, 22);
        $postWithoutSession = Request::create('/retail/new', 'POST');
        self::assertSame($redirect, $afterDefault->invoke($service, new CrudServiceContextDTO($postWithoutSession, $crudContext, $draft), $redirect));

        $draft->setOwner(null);
        $noOwnerRequest = Request::create('/retail/new', 'POST');
        $noOwnerRequest->setSession(new Session(new MockArraySessionStorage()));
        self::assertSame($redirect, $afterDefault->invoke($service, new CrudServiceContextDTO($noOwnerRequest, $crudContext, $draft), $redirect));

        $task = $this->completeListing(RetailKind::Task, 'access', 'customer-22');
        $task->publish();
        $task->setCategoryId(null);
        $this->setId($task, 23);
        $taskRequest = Request::create('/retail/new', 'POST');
        $taskRequest->setSession(new Session(new MockArraySessionStorage()));
        $taskResult = $afterDefault->invoke($service, new CrudServiceContextDTO($taskRequest, $crudContext, $task), $redirect);
        self::assertInstanceOf(RedirectResponse::class, $taskResult->payload());
        self::assertSame([], $taskRequest->getSession()->get('retail_placement')['candidateServices'] ?? null);

        $invalidTask = $this->completeListing(RetailKind::Service, 'vendor', 'vendor-1');
        $this->expectException(\InvalidArgumentException::class);
        $candidateMatcher->matchForTask($invalidTask);
    }

    public function testStorefrontFacetsDelegateToCatalogingContractsWithoutRecomputingSemantics(): void
    {
        $catalogSearch = $this->createMock(CatalogSearchServiceInterface::class);
        $catalogSearch
            ->expects(self::once())
            ->method('search')
            ->willReturn([
                'facet_contracts' => [
                    ['identifier' => 'workflow_state', 'buckets' => ['published' => 3, 'draft' => 1]],
                    ['identifier' => 'locale', 'buckets' => ['en' => 4]],
                ],
            ]);

        $facets = (new RetailStorefrontFacetService($catalogSearch))->published('en');

        self::assertSame([
            ['identifier' => 'workflow_state', 'buckets' => ['published' => 3, 'draft' => 1]],
            ['identifier' => 'locale', 'buckets' => ['en' => 4]],
        ], $facets);
    }

    public function testStorefrontFacetsReturnEmptyWhenCatalogingProvidesNoFacetContracts(): void
    {
        $catalogSearch = $this->createStub(CatalogSearchServiceInterface::class);
        $catalogSearch->method('search')->willReturn(['items' => []]);

        self::assertSame([], (new RetailStorefrontFacetService($catalogSearch))->published());
    }

    public function testStorefrontFacetsIgnoreMalformedCatalogingProjectionEntries(): void
    {
        $catalogSearch = $this->createStub(CatalogSearchServiceInterface::class);
        $catalogSearch->method('search')->willReturn([
            'facet_contracts' => [
                null,
                ['identifier' => '', 'buckets' => []],
                ['identifier' => 'locale', 'buckets' => 'invalid'],
                ['identifier' => 'published', 'buckets' => ['true' => 2]],
            ],
        ]);

        self::assertSame(
            [['identifier' => 'published', 'buckets' => ['true' => 2]]],
            (new RetailStorefrontFacetService($catalogSearch))->published(),
        );
    }

    public function testStandaloneKernelAndBundleSurfaces(): void
    {
        $kernel = new Kernel('test', false);
        self::assertSame(dirname(__DIR__, 2), $kernel->getProjectDir());
        self::assertInstanceOf(RetailingBundle::class, new RetailingBundle());
    }

    private function completeListing(RetailKind $kind, string $ownerType, string $owner): RetailEntity
    {
        $retail = new RetailEntity();
        $retail->setKind($kind);
        $retail->setOwnerType($ownerType);
        $retail->setOwner($owner);
        $retail->setCategoryId('category-1');
        $retail->setTitle('Listing');
        $retail->setFulfillmentProfile(['mode' => 'remote']);
        $retail->setPricingProfile(['mode' => 'fixed']);

        return $retail;
    }

    private function setId(RetailEntity $retail, int $id): void
    {
        $property = new \ReflectionProperty($retail, 'id');
        $property->setValue($retail, $id);
    }

    private function setResponseId(RetailResponseEntity $response, int $id): void
    {
        $property = new \ReflectionProperty($response, 'id');
        $property->setValue($response, $id);
    }
}
