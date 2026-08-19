<?php

declare(strict_types=1);

namespace App\Retailing\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class RetailingExtension extends Extension
{
    /** @param array<int, array<string, mixed>> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__, 2).'/config'));
        $loader->load('services.yaml');
        $loader->load('services.bundle.yaml');

        $environment = (string) $container->getParameter('kernel.environment');
        if (in_array($environment, ['dev', 'test'], true)) {
            $loader->load('services.fixtures.yaml');
        }
    }

    public function getAlias(): string
    {
        return 'retailing';
    }
}
