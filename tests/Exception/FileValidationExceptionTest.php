<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\FileStorageBundle\Exception\FileValidationException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(FileValidationException::class)]
final class FileValidationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new FileValidationException('Validation failed');

        $this->assertEquals('Validation failed', $exception->getMessage());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new FileValidationException('Validation failed', 422, $previous);

        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
