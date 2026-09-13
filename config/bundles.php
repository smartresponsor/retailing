<?php

declare(strict_types=1);

use App\Cataloging\CatalogingBundle;
use App\Locating\LocatingBundle;
use App\Retailing\RetailingBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

return [
    FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
    TwigBundle::class => ['all' => true],
    EasyAdminBundle::class => ['all' => true],
    CatalogingBundle::class => ['all' => true],
    LocatingBundle::class => ['all' => true],
    RetailingBundle::class => ['all' => true],
];
