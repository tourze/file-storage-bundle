<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Form\Transformer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Tourze\FileStorageBundle\Form\Transformer\MediaGalleryTransformer;

/**
 * @internal
 */
#[CoversClass(MediaGalleryTransformer::class)]
final class MediaGalleryTransformerTest extends TestCase
{
    private MediaGalleryTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new MediaGalleryTransformer();
    }

    public function testTransformNullValue(): void
    {
        $result = $this->transformer->transform(null);
        self::assertSame('[]', $result);
    }

    public function testTransformNonArrayValue(): void
    {
        $result = $this->transformer->transform('invalid');
        self::assertSame('[]', $result);
    }

    public function testTransformValidArray(): void
    {
        $data = [
            ['url' => 'https://example.com/image.jpg', 'type' => 'image'],
            ['url' => 'https://example.com/video.mp4', 'type' => 'video'],
        ];

        $result = $this->transformer->transform($data);
        $expected = json_encode($data, JSON_THROW_ON_ERROR);

        self::assertSame($expected, $result);
    }

    public function testReverseTransformEmptyValue(): void
    {
        self::assertSame([], $this->transformer->reverseTransform(null));
        self::assertSame([], $this->transformer->reverseTransform(''));
    }

    public function testReverseTransformNonStringValue(): void
    {
        $result = $this->transformer->reverseTransform(123);
        self::assertSame([], $result);
    }

    public function testReverseTransformValidJson(): void
    {
        $json = '[{"url":"https://example.com/image.jpg","type":"image"}]';
        $result = $this->transformer->reverseTransform($json);

        $expected = [
            ['url' => 'https://example.com/image.jpg', 'type' => 'image'],
        ];

        self::assertSame($expected, $result);
    }

    public function testReverseTransformWithStringItems(): void
    {
        $json = '["https://example.com/image.jpg", "https://example.com/video.mp4"]';
        $result = $this->transformer->reverseTransform($json);

        self::assertCount(2, $result);
        self::assertSame('https://example.com/image.jpg', $result[0]['url']);
        self::assertSame('image', $result[0]['type']);
        self::assertSame('https://example.com/video.mp4', $result[1]['url']);
        self::assertSame('video', $result[1]['type']);
    }

    public function testReverseTransformInvalidJson(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Failed to decode media gallery data');

        $this->transformer->reverseTransform('invalid json');
    }

    public function testTransformWithInvalidJsonData(): void
    {
        // 创建包含资源类型的数据，这无法被JSON编码
        $invalidData = [
            'resource' => fopen('php://memory', 'r'),
        ];

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Failed to encode media gallery data');

        try {
            $this->transformer->transform($invalidData);
        } finally {
            // 确保资源被正确关闭
            if (is_resource($invalidData['resource'])) {
                fclose($invalidData['resource']);
            }
        }
    }
}
