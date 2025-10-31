<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ClientNotInitializedException::class)]
final class ClientNotInitializedExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionWithDefaultMessage(): void
    {
        $exception = new ClientNotInitializedException();

        $this->assertSame('Client not initialized', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $message = 'Custom client initialization error';
        $exception = new ClientNotInitializedException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCustomCode(): void
    {
        $code = 123;
        $exception = new ClientNotInitializedException('Client not initialized', $code);

        $this->assertSame('Client not initialized', $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new ClientNotInitializedException('Client not initialized', 0, $previous);

        $this->assertSame('Client not initialized', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new ClientNotInitializedException();

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }
}
