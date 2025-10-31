<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\FileStorageBundle\Exception\FolderCreationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(FolderCreationException::class)]
final class FolderCreationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionConstruction(): void
    {
        $exception = new FolderCreationException();

        $this->assertEquals('Failed to create folder', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $message = 'Custom folder creation error';
        $exception = new FolderCreationException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
    }

    public function testExceptionWithCustomMessageAndCode(): void
    {
        $message = 'Custom folder creation error';
        $code = 123;
        $exception = new FolderCreationException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithPreviousException(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $exception = new FolderCreationException('Folder error', 456, $previousException);

        $this->assertEquals('Folder error', $exception->getMessage());
        $this->assertEquals(456, $exception->getCode());
        $this->assertEquals($previousException, $exception->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new FolderCreationException();

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }
}
