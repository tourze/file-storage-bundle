<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\FileStorageBundle\Entity\FileType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(FileType::class)]
final class FileTypeTest extends AbstractEntityTestCase
{
    private FileType $fileType;

    public function testGetId(): void
    {
        $this->assertNull($this->fileType->getId());
    }

    public function testName(): void
    {
        $name = 'JPEG Image';
        $this->fileType->setName($name);
        $this->assertEquals($name, $this->fileType->getName());
    }

    public function testMimeType(): void
    {
        $mimeType = 'image/jpeg';
        $this->fileType->setMimeType($mimeType);
        $this->assertEquals($mimeType, $this->fileType->getMimeType());
    }

    public function testExtension(): void
    {
        $extension = 'jpg';
        $this->fileType->setExtension($extension);
        $this->assertEquals($extension, $this->fileType->getExtension());
    }

    public function testMaxSize(): void
    {
        $maxSize = 5242880; // 5 MB
        $this->fileType->setMaxSize($maxSize);
        $this->assertEquals($maxSize, $this->fileType->getMaxSize());
    }

    public function testUploadType(): void
    {
        $uploadType = 'member';
        $this->fileType->setUploadType($uploadType);
        $this->assertEquals($uploadType, $this->fileType->getUploadType());
    }

    public function testDescription(): void
    {
        $description = 'JPEG image files for photos';
        $this->fileType->setDescription($description);
        $this->assertEquals($description, $this->fileType->getDescription());
    }

    public function testIsActive(): void
    {
        $this->assertTrue($this->fileType->isActive());

        $this->fileType->setIsActive(false);
        $this->assertFalse($this->fileType->isActive());

        $this->fileType->setIsActive(true);
        $this->assertTrue($this->fileType->isActive());
    }

    public function testDisplayOrder(): void
    {
        $this->assertEquals(0, $this->fileType->getDisplayOrder());

        $displayOrder = 10;
        $this->fileType->setDisplayOrder($displayOrder);
        $this->assertEquals($displayOrder, $this->fileType->getDisplayOrder());
    }

    /**
     * 测试实体属性设置
     */
    public function testEntityProperties(): void
    {
        $this->fileType->setName('PNG Image');
        $this->fileType->setMimeType('image/png');
        $this->fileType->setExtension('png');
        $this->fileType->setMaxSize(10485760);
        $this->fileType->setUploadType('both');
        $this->fileType->setDescription('PNG image files');
        $this->fileType->setIsActive(true);
        $this->fileType->setDisplayOrder(5);

        $this->assertSame('PNG Image', $this->fileType->getName());
        $this->assertSame('image/png', $this->fileType->getMimeType());
        $this->assertSame('png', $this->fileType->getExtension());
        $this->assertSame(10485760, $this->fileType->getMaxSize());
        $this->assertSame('both', $this->fileType->getUploadType());
        $this->assertSame('PNG image files', $this->fileType->getDescription());
        $this->assertTrue($this->fileType->isActive());
        $this->assertSame(5, $this->fileType->getDisplayOrder());
    }

    /**
     * 测试上传类型验证
     */
    public function testUploadTypeValues(): void
    {
        $validTypes = ['anonymous', 'member', 'both'];

        foreach ($validTypes as $type) {
            $this->fileType->setUploadType($type);
            $this->assertEquals($type, $this->fileType->getUploadType());
        }
    }

    protected function createEntity(): FileType
    {
        return new FileType();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'JPEG Image'];
        yield 'mimeType' => ['mimeType', 'image/jpeg'];
        yield 'extension' => ['extension', 'jpg'];
        yield 'maxSize' => ['maxSize', 5242880];
        yield 'uploadType' => ['uploadType', 'member'];
        yield 'description' => ['description', 'JPEG image files for photos'];
        yield 'displayOrder' => ['displayOrder', 10];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileType = $this->createEntity();
    }
}
