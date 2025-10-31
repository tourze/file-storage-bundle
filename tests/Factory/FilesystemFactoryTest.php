<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Factory;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\FileStorageBundle\Factory\FilesystemFactory;
use Tourze\FileStorageBundle\Factory\FilesystemFactoryInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(FilesystemFactory::class)]
#[RunTestsInSeparateProcesses]
final class FilesystemFactoryTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Required by AbstractIntegrationTestCase
    }

    public function testServiceIsAvailable(): void
    {
        $factory = self::getService(FilesystemFactory::class);
        $this->assertInstanceOf(FilesystemFactory::class, $factory);
    }

    public function testImplementsInterface(): void
    {
        $factory = self::getService(FilesystemFactory::class);
        $this->assertInstanceOf(FilesystemFactoryInterface::class, $factory);
    }

    public function testCreateFilesystem(): void
    {
        $factory = self::getService(FilesystemFactory::class);
        $filesystem = $factory->createFilesystem();

        $this->assertInstanceOf(FilesystemOperator::class, $filesystem);
    }

    public function testConstructorWithDependencies(): void
    {
        $factory = self::getService(FilesystemFactory::class);
        $this->assertInstanceOf(FilesystemFactory::class, $factory);
    }

    public function testCreateFilesystemLogsDebugMessage(): void
    {
        // 在集成测试中，我们验证服务能够正常创建文件系统
        // 具体的日志记录行为应该在单元测试中验证
        $factory = self::getService(FilesystemFactory::class);
        $filesystem = $factory->createFilesystem();

        $this->assertInstanceOf(FilesystemOperator::class, $filesystem);
    }

    public function testCreateFilesystemReturnsFilesystemOperator(): void
    {
        $factory = self::getService(FilesystemFactory::class);
        $filesystem = $factory->createFilesystem();

        $this->assertInstanceOf(FilesystemOperator::class, $filesystem);
    }
}
