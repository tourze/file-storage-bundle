<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\FileStorageBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 从容器获取服务实例
    }

    public function testAdminMenuServiceIsAvailable(): void
    {
        // 测试服务是否在容器中可用
        $this->assertTrue(self::getContainer()->has(AdminMenu::class));

        $service = self::getContainer()->get(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $service);
    }

    public function testMenuServiceIsInvokable(): void
    {
        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);

        // 测试服务实现了 MenuProviderInterface
        $this->assertInstanceOf(MenuProviderInterface::class, $adminMenu);
    }
}
