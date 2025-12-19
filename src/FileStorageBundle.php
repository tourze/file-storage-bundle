<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\AccessTokenBundle\AccessTokenBundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineIpBundle\DoctrineIpBundle;
use Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\DoctrineTrackBundle\DoctrineTrackBundle;
use Tourze\DoctrineUserBundle\DoctrineUserBundle;
use Tourze\EasyAdminImagePreviewFieldBundle\EasyAdminImagePreviewFieldBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;
use Tourze\FileStorageBundle\DependencyInjection\Compiler\SecurityFirewallCompilerPass;
use Tourze\FlysystemBundle\FlysystemBundle;
use Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle;
use Tourze\Symfony\CronJob\CronJobBundle;

class FileStorageBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            FlysystemBundle::class => ['all' => true],
            DoctrineBundle::class => ['all' => true],
            DoctrineIpBundle::class => ['all' => true],
            DoctrineTimestampBundle::class => ['all' => true],
            DoctrineTrackBundle::class => ['all' => true],
            DoctrineUserBundle::class => ['all' => true],
            DoctrineSnowflakeBundle::class => ['all' => true],
            EasyAdminImagePreviewFieldBundle::class => ['all' => true],
            CronJobBundle::class => ['all' => true],
            RoutingAutoLoaderBundle::class => ['all' => true],
            AccessTokenBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SecurityFirewallCompilerPass());

        // Twig 模板命名空间在 Extension::prepend 中注册
    }
}
