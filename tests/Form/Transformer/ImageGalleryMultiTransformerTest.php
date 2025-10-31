<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Form\Transformer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\FileStorageBundle\Form\Transformer\ImageGalleryMultiTransformer;

/**
 * @internal
 */
#[CoversClass(ImageGalleryMultiTransformer::class)]
final class ImageGalleryMultiTransformerTest extends TestCase
{
    public function testTransformArray(): void
    {
        $t = new ImageGalleryMultiTransformer();
        $json = $t->transform([['url' => 'u1'], ['url' => 'u2']]);
        $this->assertJson($json);
        $this->assertSame(['u1', 'u2'], json_decode($json, true));
    }

    public function testReverseTransformJson(): void
    {
        $t = new ImageGalleryMultiTransformer();
        $arr = $t->reverseTransform('["a","b"]');
        $this->assertSame([['url' => 'a'], ['url' => 'b']], $arr);
    }

    public function testReverseTransformInvalid(): void
    {
        $t = new ImageGalleryMultiTransformer();
        $this->assertSame([], $t->reverseTransform('not-json'));
        $this->assertSame([], $t->reverseTransform(''));
    }
}
