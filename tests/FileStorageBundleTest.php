<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\FileStorageBundle\FileStorageBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(FileStorageBundle::class)]
#[RunTestsInSeparateProcesses]
final class FileStorageBundleTest extends AbstractBundleTestCase
{
}
