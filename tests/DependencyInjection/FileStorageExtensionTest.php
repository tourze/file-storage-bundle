<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\FileStorageBundle\DependencyInjection\FileStorageExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(FileStorageExtension::class)]
final class FileStorageExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    #[Test]
    public function testPrependConfiguresTwigPaths(): void
    {
        $extension = new FileStorageExtension();
        $container = new ContainerBuilder();

        // 调用 prepend 方法
        $extension->prepend($container);

        // 验证 Twig 配置被正确添加
        $twigConfig = $container->getExtensionConfig('twig');
        $this->assertNotEmpty($twigConfig);

        $config = $twigConfig[0];
        $this->assertArrayHasKey('paths', $config);
        $this->assertIsArray($config['paths']);

        // 验证 FileStorage 命名空间被正确配置
        $foundCorrectPath = false;
        foreach ($config['paths'] as $path => $namespace) {
            if ('FileStorage' === $namespace && (str_ends_with($path, '/Resources/views') || str_contains($path, '../Resources/views'))) {
                $foundCorrectPath = true;
                break;
            }
        }
        $this->assertTrue($foundCorrectPath, 'FileStorage namespace should be configured for views directory');
    }
}
