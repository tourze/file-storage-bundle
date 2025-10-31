<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\FileStorageBundle\Exception\FieldParameterException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(FieldParameterException::class)]
final class FieldParameterExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new FieldParameterException('test message');

        self::assertSame('test message', $exception->getMessage());
    }

    public function testCanBeThrown(): void
    {
        $this->expectException(FieldParameterException::class);
        $this->expectExceptionMessage('Field parameter error');

        throw new FieldParameterException('Field parameter error');
    }

    public function testInheritsPreviousException(): void
    {
        $previousException = new \Exception('Previous error');
        $exception = new FieldParameterException('Current error', 0, $previousException);

        self::assertSame('Current error', $exception->getMessage());
        self::assertSame($previousException, $exception->getPrevious());
    }
}
