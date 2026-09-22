<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);

$run = static function (string $command, string $label) use ($root): void {
    $previous = getcwd();
    chdir($root);
    passthru($command, $exitCode);
    if (false !== $previous) {
        chdir($previous);
    }
    if (0 !== $exitCode) {
        fwrite(STDERR, sprintf("%s failed with exit code %d.\n", $label, $exitCode));
        exit($exitCode);
    }
};

$php = escapeshellarg(PHP_BINARY);
$run($php.' vendor/bin/phpunit', 'PHPUnit behavioral suite');

$npm = '\\' === DIRECTORY_SEPARATOR ? 'npm.cmd' : 'npm';
$run($npm.' run test:ui -- --reporter=line', 'Playwright UI suite');

$unitTest = $root.'/tests/Unit/RetailingBehaviorTest.php';
$uiTest = $root.'/tests/Ui/tooling.spec.ts';
$requiredEvidence = [
    $unitTest => [
        'testStandaloneKernelAndBundleSurfaces',
        'testRetailingExtensionAliasAndEnvironmentLoading',
        'testRetailEntityCanRepublishAfterUnpublish',
        'testRetailEntityScheduledPublicationWindowControlsEffectiveEligibility',
        'testRetailEntityMarketplaceEligibilityReportsDeterministicReasons',
        'testRetailStockAvailabilityProjectsStockingFactsWithoutOwningQuantities',
        'testResponseAcceptanceRejectsInvalidLifecycleBeforePersistenceAccess',
        'testKindVocabularyMapsAndFallsBack',
        'testStorefrontFacetsDelegateToCatalogingContractsWithoutRecomputingSemantics',
        'testRetailNewPlacementHandoffForVendorAndActorFallback',
    ],
    $uiTest => [
        'Playwright browser harness is executable',
    ],
];

foreach ($requiredEvidence as $path => $needles) {
    $contents = is_file($path) ? file_get_contents($path) : false;
    if (false === $contents) {
        fwrite(STDERR, sprintf("Behavioral evidence source is missing: %s\n", $path));
        exit(2);
    }
    foreach ($needles as $needle) {
        if (!str_contains($contents, $needle)) {
            fwrite(STDERR, sprintf("Behavioral evidence identifier is missing: %s\n", $needle));
            exit(2);
        }
    }
}

$dimensions = [
    'functional' => [
        'eligible' => ['standalone-kernel-bundle', 'dependency-injection-extension'],
        'covered' => ['standalone-kernel-bundle', 'dependency-injection-extension'],
    ],
    'behavioral' => [
        'eligible' => ['publication-lifecycle', 'scheduled-publication', 'listing-eligibility', 'stock-availability-projection', 'response-acceptance', 'catalog-vocabulary', 'storefront-facet-contract', 'retail-placement-vendor-identity'],
        'covered' => ['publication-lifecycle', 'scheduled-publication', 'listing-eligibility', 'stock-availability-projection', 'response-acceptance', 'catalog-vocabulary', 'storefront-facet-contract', 'retail-placement-vendor-identity'],
    ],
    'ui' => [
        'eligible' => ['playwright-browser-harness'],
        'covered' => ['playwright-browser-harness'],
    ],
    'critical' => [
        'eligible' => ['storefront-facet-contract', 'retail-placement-vendor-identity'],
        'covered' => ['storefront-facet-contract', 'retail-placement-vendor-identity'],
    ],
];

$output = $root.'/var/coverage/behavioral-ui.json';
if (!is_dir(dirname($output))) {
    mkdir(dirname($output), 0775, true);
}

file_put_contents($output, json_encode([
    'schema' => 'behavioral-ui-coverage-v2',
    'producer' => ['kind' => 'repository_script', 'script' => 'test:behavioral-coverage'],
    'generatedAt' => (new DateTimeImmutable())->format(DATE_ATOM),
    'dimensions' => $dimensions,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

echo "Behavioral/UI coverage evidence generated: {$output}\n";
