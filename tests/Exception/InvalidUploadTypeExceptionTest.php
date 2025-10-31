<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\FileStorageBundle\Exception\InvalidUploadTypeException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidUploadTypeException::class)]
final class InvalidUploadTypeExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new InvalidUploadTypeException('Invalid upload type');

        $this->assertEquals('Invalid upload type', $exception->getMessage());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidUploadTypeException('Invalid upload type', 400, $previous);

        $this->assertEquals('Invalid upload type', $exception->getMessage());
        $this->assertEquals(400, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
