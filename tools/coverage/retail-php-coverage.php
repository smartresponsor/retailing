<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$coverageDirectory = $root.'/var/coverage';
if (!is_dir($coverageDirectory)) {
    mkdir($coverageDirectory, 0775, true);
}

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
$standardReport = 'var/coverage/phpunit-standard.txt';
$branchReport = 'var/coverage/phpunit-path.txt';
$cloverReport = 'var/coverage/clover.xml';

$run(
    $php.' -d xdebug.mode=coverage vendor/bin/phpunit --colors=never'
        .' --coverage-text='.$standardReport
        .' --coverage-clover='.$cloverReport,
    'PHPUnit standard coverage',
);
$run(
    $php.' -d xdebug.mode=coverage vendor/bin/phpunit --colors=never'
        .' --coverage-text='.$branchReport
        .' --path-coverage',
    'PHPUnit branch/path instrumentation',
);

$metric = static function (string $reportPath, string $name) use ($root): array {
    $contents = file_get_contents($root.'/'.$reportPath);
    if (false === $contents) {
        throw new RuntimeException(sprintf('Coverage report is unreadable: %s.', $reportPath));
    }
    $contents = preg_replace('/\x1B\[[0-?]*[ -\/]*[@-~]/', '', $contents) ?? $contents;
    if (1 !== preg_match(
        '/^\s*'.preg_quote($name, '/').':\s+\S+\s+\(\s*(\d+)\s*\/\s*(\d+)\s*\)\s*$/mi',
        $contents,
        $match,
    )) {
        throw new RuntimeException(sprintf('Coverage metric %s is missing from %s.', $name, $reportPath));
    }

    return [(int) $match[1], (int) $match[2]];
};

[$coveredLines, $totalLines] = $metric($standardReport, 'Lines');
[$coveredMethods, $totalMethods] = $metric($standardReport, 'Methods');
[$coveredBranches, $totalBranches] = $metric($branchReport, 'Branches');

$percent = static fn (int $covered, int $total): float => 0 === $total ? 100.0 : 100.0 * $covered / $total;

$summary = sprintf(
    "Code Coverage Report:\n"
    ."  Methods: %.2f%% (%d/%d)\n"
    ."  Branches: %.2f%% (%d/%d)\n"
    ."  Lines: %.2f%% (%d/%d)\n",
    $percent($coveredMethods, $totalMethods),
    $coveredMethods,
    $totalMethods,
    $percent($coveredBranches, $totalBranches),
    $coveredBranches,
    $totalBranches,
    $percent($coveredLines, $totalLines),
    $coveredLines,
    $totalLines,
);

$output = $root.'/var/coverage.txt';
file_put_contents($output, $summary);
echo $summary;
