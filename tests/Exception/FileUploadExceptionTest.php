<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\FileStorageBundle\Exception\FileUploadException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(FileUploadException::class)]
final class FileUploadExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new FileUploadException('Test message');

        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new FileUploadException('Test message', 500, $previous);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionInheritsFromRuntimeException(): void
    {
        $exception = new FileUploadException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
