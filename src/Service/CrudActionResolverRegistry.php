<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Service;

/**
 * 全局注册表，用于在静态上下文中访问CrudActionResolver服务
 * 这是为了解决EasyAdmin字段类无法直接注入服务的问题
 */
final class CrudActionResolverRegistry
{
    private static ?CrudActionResolverInterface $instance = null;

    public static function setInstance(?CrudActionResolverInterface $resolver): void
    {
        self::$instance = $resolver;
    }

    public static function getInstance(): ?CrudActionResolverInterface
    {
        return self::$instance;
    }
}
