<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(File::class)]
final class FileTest extends AbstractEntityTestCase
{
    private File $file;

    public function testGetId(): void
    {
        $this->assertNull($this->file->getId());
    }

    public function testOriginalName(): void
    {
        $originalName = 'test-file.pdf';
        $this->file->setOriginalName($originalName);
        $this->assertEquals($originalName, $this->file->getOriginalName());
    }

    public function testFileName(): void
    {
        $fileName = 'test-file-123456.pdf';
        $this->file->setFileName($fileName);
        $this->assertEquals($fileName, $this->file->getFileName());
    }

    public function testFilePath(): void
    {
        $filePath = '2024/01/test-file-123456.pdf';
        $this->file->setFilePath($filePath);
        $this->assertEquals($filePath, $this->file->getFilePath());
    }

    public function testMimeType(): void
    {
        $mimeType = 'application/pdf';
        $this->file->setMimeType($mimeType);
        $this->assertEquals($mimeType, $this->file->getMimeType());
    }

    public function testFileSize(): void
    {
        $fileSize = 1048576;
        $this->file->setFileSize($fileSize);
        $this->assertEquals($fileSize, $this->file->getFileSize());
    }

    public function testMd5Hash(): void
    {
        $md5Hash = md5('test content');
        $this->file->setMd5Hash($md5Hash);
        $this->assertEquals($md5Hash, $this->file->getMd5Hash());
    }

    public function testSha1Hash(): void
    {
        $sha1Hash = sha1('test content');
        $this->file->setSha1Hash($sha1Hash);
        $this->assertEquals($sha1Hash, $this->file->getSha1Hash());
    }

    public function testMetadata(): void
    {
        $metadata = ['width' => 800, 'height' => 600, 'format' => 'JPEG'];
        $this->file->setMetadata($metadata);
        $this->assertEquals($metadata, $this->file->getMetadata());
    }

    public function testIsActive(): void
    {
        $this->assertTrue($this->file->isActive());

        $this->file->setIsActive(false);
        $this->assertFalse($this->file->isActive());

        $this->file->setIsActive(true);
        $this->assertTrue($this->file->isActive());
    }

    public function testPublicUrl(): void
    {
        $this->assertNull($this->file->getPublicUrl());

        $publicUrl = 'https://example.com/files/123456/test.pdf';
        $this->file->setPublicUrl($publicUrl);
        $this->assertEquals($publicUrl, $this->file->getPublicUrl());

        $this->file->setPublicUrl(null);
        $this->assertNull($this->file->getPublicUrl());
    }

    public function testUser(): void
    {
        $this->assertNull($this->file->getUser());

        $user = $this->createMock(UserInterface::class);
        $this->file->setUser($user);
        $this->assertSame($user, $this->file->getUser());

        $this->file->setUser(null);
        $this->assertNull($this->file->getUser());
    }

    public function testIsAnonymous(): void
    {
        $this->assertTrue($this->file->isAnonymous());

        $user = $this->createMock(UserInterface::class);
        $this->file->setUser($user);
        $this->assertFalse($this->file->isAnonymous());

        $this->file->setUser(null);
        $this->assertTrue($this->file->isAnonymous());
    }

    /**
     * 测试setter方法的基本功能（替代原来的流式接口测试）
     */
    public function testFluentInterface(): void
    {
        // 现在setter方法返回void，测试各个setter是否正常工作
        $this->file->setOriginalName('test.pdf');
        $this->file->setFileName('test-123.pdf');
        $this->file->setFilePath('2024/01/test-123.pdf');
        $this->file->setMimeType('application/pdf');
        $this->file->setFileSize(1024);
        $this->file->setMd5Hash('abc123');
        $this->file->setSha1Hash('def456');
        $this->file->setMetadata(['key' => 'value']);
        $this->file->setIsActive(true);
        $this->file->setPublicUrl('https://example.com/files/test.pdf');
        $this->file->setUser(null);

        // 验证设置的值是否正确
        $this->assertEquals('test.pdf', $this->file->getOriginalName());
        $this->assertEquals('test-123.pdf', $this->file->getFileName());
        $this->assertEquals('2024/01/test-123.pdf', $this->file->getFilePath());
        $this->assertEquals('application/pdf', $this->file->getMimeType());
        $this->assertEquals(1024, $this->file->getFileSize());
        $this->assertEquals('abc123', $this->file->getMd5Hash());
        $this->assertEquals('def456', $this->file->getSha1Hash());
        $this->assertEquals(['key' => 'value'], $this->file->getMetadata());
        $this->assertTrue($this->file->isActive());
        $this->assertEquals('https://example.com/files/test.pdf', $this->file->getPublicUrl());
        $this->assertNull($this->file->getUser());
    }

    public function testToArray(): void
    {
        // 使用独立的setter调用代替链式调用
        $this->file->setOriginalName('test.pdf');
        $this->file->setFileName('test-123.pdf');
        $this->file->setFilePath('2024/01/test-123.pdf');
        $this->file->setMimeType('application/pdf');
        $this->file->setFileSize(1024);
        $this->file->setMd5Hash('abc123');
        $this->file->setPublicUrl('https://example.com/files/test.pdf');

        $array = $this->file->toArray();

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('originalName', $array);
        $this->assertArrayHasKey('fileName', $array);
        $this->assertArrayHasKey('filePath', $array);
        $this->assertArrayHasKey('mimeType', $array);
        $this->assertArrayHasKey('fileSize', $array);
        $this->assertArrayHasKey('md5Hash', $array);
        $this->assertArrayHasKey('publicUrl', $array);
        $this->assertArrayHasKey('createTime', $array);

        $this->assertEquals('test.pdf', $array['originalName']);
        $this->assertEquals('test-123.pdf', $array['fileName']);
        $this->assertEquals('2024/01/test-123.pdf', $array['filePath']);
        $this->assertEquals('application/pdf', $array['mimeType']);
        $this->assertEquals(1024, $array['fileSize']);
        $this->assertEquals('abc123', $array['md5Hash']);
        $this->assertEquals('https://example.com/files/test.pdf', $array['publicUrl']);
    }

    protected function createEntity(): File
    {
        return new File();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'originalName' => ['originalName', 'test-file.pdf'];
        yield 'fileName' => ['fileName', 'test-file-123456.pdf'];
        yield 'filePath' => ['filePath', '2024/01/test-file-123456.pdf'];
        yield 'mimeType' => ['mimeType', 'application/pdf'];
        yield 'fileSize' => ['fileSize', 1048576];
        yield 'md5Hash' => ['md5Hash', md5('test content')];
        yield 'sha1Hash' => ['sha1Hash', sha1('test content')];
        yield 'metadata' => ['metadata', ['width' => 800, 'height' => 600, 'format' => 'JPEG']];
        yield 'publicUrl' => ['publicUrl', 'https://example.com/files/123456/test.pdf'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = $this->createEntity();
    }
}
