<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller\Gallery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryUploadFileController;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryUploadFileController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryUploadFileControllerTest extends AbstractWebTestCase
{
    private string $tempDir;

    private ?KernelBrowser $client = null;

    private FileRepository $fileRepository;

    private FolderRepository $folderRepository;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        $this->fileRepository = self::getService(FileRepository::class);
        $this->folderRepository = self::getService(FolderRepository::class);

        // 清理数据库中的文件和文件夹数据以确保测试隔离
        $entityManager = self::getEntityManager();
        $files = $this->fileRepository->findAll();
        foreach ($files as $file) {
            $entityManager->remove($file);
        }
        $folders = $this->folderRepository->findAll();
        foreach ($folders as $folder) {
            $entityManager->remove($folder);
        }
        $entityManager->flush();

        // 创建临时目录用于测试文件
        $this->tempDir = sys_get_temp_dir() . '/test_uploads_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0o777, true);
        }
    }

    protected function onTearDown(): void
    {
        // 清理临时目录
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*');
            if (false !== $files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            rmdir($this->tempDir);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(string $content): array
    {
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            self::fail('Failed to decode JSON response');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function createTestFolder(): Folder
    {
        $entityManager = self::getEntityManager();

        $folder = new Folder();
        $folder->setName('Test Upload Folder');
        $folder->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($folder);
        $entityManager->flush();

        return $folder;
    }

    private function createTestFile(string $filename, string $content = 'test content', string $mimeType = 'text/plain'): UploadedFile
    {
        $tempFile = $this->tempDir . '/' . $filename;
        file_put_contents($tempFile, $content);

        return new UploadedFile(
            $tempFile,
            $filename,
            $mimeType,
            null,
            true // test mode
        );
    }

    public function testUploadFileSuccessfully(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $folder = $this->createTestFolder();

        $uploadedFile = $this->createTestFile('test.txt', 'test content', 'text/plain');

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => (string) $folder->getId(),
        ], [
            'file' => $uploadedFile,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);

        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('文件上传成功', $responseData['message']);

        $fileData = $responseData['data'];
        $this->assertIsArray($fileData);

        $this->assertArrayHasKey('id', $fileData);
        $this->assertArrayHasKey('originalName', $fileData);
        $this->assertArrayHasKey('fileName', $fileData);
        $this->assertArrayHasKey('publicUrl', $fileData);
        $this->assertArrayHasKey('folder', $fileData);

        // 系统会设置 originalName，通常保持原始文件名
        $this->assertIsString($fileData['originalName']);
        $this->assertStringContainsString('test', $fileData['originalName']);
        $this->assertStringEndsWith('.txt', $fileData['originalName']);

        $this->assertIsArray($fileData['folder']);
        $this->assertArrayHasKey('id', $fileData['folder']);
        $this->assertArrayHasKey('name', $fileData['folder']);
        $this->assertEquals($folder->getId(), $fileData['folder']['id']);
        $this->assertEquals($folder->getName(), $fileData['folder']['name']);
    }

    public function testUploadImageFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $folder = $this->createTestFolder();

        // 创建一个简单的图片内容（实际上是文本，但MIME类型是图片）
        $uploadedFile = $this->createTestFile('test.jpg', 'fake image content', 'image/jpeg');

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => (string) $folder->getId(),
        ], [
            'file' => $uploadedFile,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);
        $fileData = $responseData['data'];
        $this->assertIsArray($fileData);
        $this->assertArrayHasKey('isImage', $fileData);
        $this->assertArrayHasKey('mimeType', $fileData);

        $this->assertTrue($fileData['isImage']);
        $this->assertIsString($fileData['mimeType']);
        $this->assertStringContainsString('image/', $fileData['mimeType']);
    }

    public function testUploadFileWithoutFolderId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $uploadedFile = $this->createTestFile('test.txt');

        $client->request('POST', '/gallery/api/upload', [], [
            'file' => $uploadedFile,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('文件夹ID是必需的', $responseData['error']);
    }

    public function testUploadFileWithInvalidFolderId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $uploadedFile = $this->createTestFile('test.txt');

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => 'invalid',
        ], [
            'file' => $uploadedFile,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('文件夹ID是必需的', $responseData['error']);
    }

    public function testUploadFileWithNonExistentFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $uploadedFile = $this->createTestFile('test.txt');

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => '99999',
        ], [
            'file' => $uploadedFile,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Folder not found', $responseData['error']);
    }

    public function testUploadWithoutFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $folder = $this->createTestFolder();

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => (string) $folder->getId(),
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);
        $this->assertFalse($responseData['success']);
        $this->assertEquals('未上传文件', $responseData['error']);

        // 清理
        $entityManager = self::getEntityManager();
        $folderFromDb = $entityManager->find(\get_class($folder), $folder->getId());
        if (null !== $folderFromDb) {
            $entityManager->remove($folderFromDb);
            $entityManager->flush();
        }
    }

    public function testUploadEmptyFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $folder = $this->createTestFolder();

        $uploadedFile = $this->createTestFile('empty.txt', '');

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => (string) $folder->getId(),
        ], [
            'file' => $uploadedFile,
        ]);

        // 空文件可能会被拒绝或接受，取决于验证逻辑
        $response = $client->getResponse();
        $this->assertTrue(
            Response::HTTP_BAD_REQUEST === $response->getStatusCode()
            || Response::HTTP_CREATED === $response->getStatusCode()
        );

        // 清理
        $entityManager = self::getEntityManager();
        if (Response::HTTP_CREATED === $response->getStatusCode()) {
            $content = $response->getContent();
            $this->assertNotFalse($content);
            $responseData = $this->decodeJsonResponse($content);
            $this->assertArrayHasKey('data', $responseData);
            $this->assertIsArray($responseData['data']);
            $this->assertArrayHasKey('id', $responseData['data']);
            $uploadedFileEntity = $this->fileRepository->find($responseData['data']['id']);
            if (null !== $uploadedFileEntity) {
                $entityManager->remove($uploadedFileEntity);
            }
        }
        $entityManager->remove($folder);
        $entityManager->flush();
    }

    public function testUploadWithWrongHttpMethod(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 测试不支持的HTTP方法
        try {
            $client->request('GET', '/gallery/api/upload');
            $response = $client->getResponse();
            $this->assertSame(405, $response->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 这是预期的行为 - GET方法不被允许
            $this->assertSame('No route found for "GET http://localhost/gallery/api/upload": Method Not Allowed (Allow: POST)', $e->getMessage());
        }

        try {
            $client->request('PUT', '/gallery/api/upload');
            $response = $client->getResponse();
            $this->assertSame(405, $response->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 这是预期的行为 - 该方法不被允许
            $this->assertStringContainsString('Method Not Allowed', $e->getMessage());
        }

        try {
            $client->request('DELETE', '/gallery/api/upload');
            $response = $client->getResponse();
            $this->assertSame(405, $response->getStatusCode());
        } catch (MethodNotAllowedHttpException $e) {
            // 这是预期的行为 - 该方法不被允许
            $this->assertStringContainsString('Method Not Allowed', $e->getMessage());
        }
    }

    public function testUploadFileResponseFormat(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $folder = $this->createTestFolder();

        $uploadedFile = $this->createTestFile('format_test.txt', 'test content for format');

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => (string) $folder->getId(),
        ], [
            'file' => $uploadedFile,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);
        $this->assertArrayHasKey('data', $responseData);
        $fileData = $responseData['data'];
        $this->assertIsArray($fileData);

        // 验证返回数据的完整性
        $requiredFields = [
            'id', 'originalName', 'fileName', 'filePath', 'publicUrl',
            'mimeType', 'fileSize', 'formattedSize', 'createTime', 'isImage', 'folder',
        ];

        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $fileData, "Missing field: {$field}");
        }

        // 验证具体值 - ID可能是字符串（大整数）
        $this->assertTrue(is_int($fileData['id']) || is_string($fileData['id']));
        $this->assertIsString($fileData['originalName']);
        $this->assertIsString($fileData['fileName']);
        $this->assertIsString($fileData['filePath']);
        $this->assertIsString($fileData['publicUrl']);
        $this->assertIsString($fileData['mimeType']);
        $this->assertIsInt($fileData['fileSize']);
        $this->assertIsString($fileData['formattedSize']);
        $this->assertIsString($fileData['createTime']);
        $this->assertIsBool($fileData['isImage']);
        $this->assertIsArray($fileData['folder']);

        // 验证folder结构
        $this->assertArrayHasKey('id', $fileData['folder']);
        $this->assertArrayHasKey('name', $fileData['folder']);
    }

    public function testFormatFileSizeInResponse(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $folder = $this->createTestFolder();

        // 创建不同大小的测试文件
        $testSizes = [
            ['content' => str_repeat('a', 500), 'expectedUnit' => 'B'],
            ['content' => str_repeat('b', 1500), 'expectedUnit' => 'KB'],
        ];

        foreach ($testSizes as $index => $testCase) {
            $uploadedFile = $this->createTestFile("size_test_{$index}.txt", $testCase['content']);

            $client->request('POST', '/gallery/api/upload', [
                'folderId' => (string) $folder->getId(),
            ], [
                'file' => $uploadedFile,
            ]);

            $response = $client->getResponse();
            $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());

            $content = $response->getContent();
            $this->assertNotFalse($content);
            $responseData = $this->decodeJsonResponse($content);
            $this->assertArrayHasKey('data', $responseData);
            $fileData = $responseData['data'];
            $this->assertIsArray($fileData);
            $this->assertArrayHasKey('formattedSize', $fileData);

            $this->assertIsString($fileData['formattedSize']);
            $this->assertStringContainsString($testCase['expectedUnit'], $fileData['formattedSize']);
        }
    }

    public function testIsImageFileDetection(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $folder = $this->createTestFolder();

        $testCases = [
            ['filename' => 'image.jpg', 'mimeType' => 'image/jpeg', 'expectedIsImage' => true],
            ['filename' => 'image.png', 'mimeType' => 'image/png', 'expectedIsImage' => true],
            ['filename' => 'document.pdf', 'mimeType' => 'application/pdf', 'expectedIsImage' => false],
            ['filename' => 'text.txt', 'mimeType' => 'text/plain', 'expectedIsImage' => false],
        ];

        $uploadedFiles = [];

        foreach ($testCases as $index => $testCase) {
            $uploadedFile = $this->createTestFile($testCase['filename'], 'content', $testCase['mimeType']);

            $client->request('POST', '/gallery/api/upload', [
                'folderId' => (string) $folder->getId(),
            ], [
                'file' => $uploadedFile,
            ]);

            $response = $client->getResponse();
            $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());

            $content = $response->getContent();
            $this->assertNotFalse($content);
            $responseData = $this->decodeJsonResponse($content);
            $this->assertArrayHasKey('data', $responseData);
            $fileData = $responseData['data'];
            $this->assertIsArray($fileData);
            $this->assertArrayHasKey('isImage', $fileData);
            $this->assertArrayHasKey('id', $fileData);

            $this->assertEquals(
                $testCase['expectedIsImage'],
                $fileData['isImage'],
                "Failed for file: {$testCase['filename']}"
            );

            $uploadedFiles[] = $fileData['id'];
        }
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/gallery/api/upload');
    }
}
