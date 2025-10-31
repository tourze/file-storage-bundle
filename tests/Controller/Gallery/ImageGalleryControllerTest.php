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
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryIndexController;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryIndexController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryControllerTest extends AbstractWebTestCase
{
    private FileRepository $fileRepository;

    private FolderRepository $folderRepository;

    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        $this->fileRepository = self::getService(FileRepository::class);
        $this->folderRepository = self::getService(FolderRepository::class);
    }

    #[Test]
    public function testIndexReturnsGalleryView(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('文件管理器', $content);
    }

    #[Test]
    public function testGetFilesReturnsJsonResponse(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建测试文件
        $file = $this->createTestFile();

        $client->request('GET', '/gallery/api/files');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
    }

    #[Test]
    public function testGetFilesWithPagination(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建多个测试文件
        for ($i = 1; $i <= 5; ++$i) {
            $this->createTestFile("test{$i}.txt");
        }

        $client->request('GET', '/gallery/api/files?page=1&limit=2');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertIsArray($data['pagination']);
        $this->assertArrayHasKey('current_page', $data['pagination']);
        $this->assertArrayHasKey('per_page', $data['pagination']);
        $this->assertArrayHasKey('total', $data['pagination']);
        $this->assertSame(1, $data['pagination']['current_page']);
        $this->assertSame(2, $data['pagination']['per_page']);
        $this->assertGreaterThanOrEqual(5, $data['pagination']['total']);
    }

    #[Test]
    public function testGetFilesWithFilters(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->createTestFile('example.txt');

        $client->request('GET', '/gallery/api/files?filename=example&year=2024');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    #[Test]
    public function testGetFilesWithFolderFilter(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $folder = $this->createTestFolder();
        $file = $this->createTestFile();
        $file->setFolder($folder);
        $this->fileRepository->save($file);

        $client->request('GET', "/gallery/api/files?folder={$folder->getId()}");

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    #[Test]
    public function testGetFilesWithNonExistentFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files?folder=99999');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertEmpty($data['data']);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertIsArray($data['pagination']);
        $this->assertArrayHasKey('total', $data['pagination']);
        $this->assertSame(0, $data['pagination']['total']);
    }

    #[Test]
    public function testDeleteFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $file = $this->createTestFile();

        $client->request('DELETE', "/gallery/api/files/{$file->getId()}");

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('文件删除成功', $data['message']);
    }

    #[Test]
    public function testDeleteNonExistentFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $client->request('DELETE', '/gallery/api/files/99999');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('文件未找到', $data['error']);
    }

    #[Test]
    public function testGetStats(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        // 创建一些测试文件
        $this->createTestFile('image1.jpg', 'image/jpeg');
        $this->createTestFile('doc1.pdf', 'application/pdf');

        $client->request('GET', '/gallery/api/stats');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);

        $this->assertIsArray($data['data']);
        $stats = $data['data'];
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('total_size_formatted', $stats);
        $this->assertArrayHasKey('image_count', $stats);
        $this->assertArrayHasKey('document_count', $stats);
        $this->assertArrayHasKey('recent_count', $stats);
    }

    #[Test]
    public function testGetAllowedTypes(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/allowed-types');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('member', $data['data']);
        $this->assertArrayHasKey('anonymous', $data['data']);
    }

    #[Test]
    public function testGetFolders(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $folder = $this->createTestFolder();

        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    #[Test]
    public function testCreateFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $folderData = [
            'name' => '测试文件夹',
            'description' => '测试描述',
        ];

        $client->request('POST', '/gallery/api/folders', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], false !== json_encode($folderData) ? json_encode($folderData) : '{}');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('文件夹创建成功', $data['message']);
        $this->assertArrayHasKey('data', $data);
    }

    #[Test]
    public function testCreateFolderWithoutName(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $folderData = [
            'description' => '测试描述',
        ];

        $client->request('POST', '/gallery/api/folders', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], false !== json_encode($folderData) ? json_encode($folderData) : '{}');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('文件夹名称是必需的', $data['error']);
    }

    #[Test]
    public function testCreateFolderWithEmptyName(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $folderData = [
            'name' => '',
            'description' => '测试描述',
        ];

        $client->request('POST', '/gallery/api/folders', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], false !== json_encode($folderData) ? json_encode($folderData) : '{}');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('文件夹名称不能为空', $data['error']);
    }

    #[Test]
    public function testUpdateFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $folder = $this->createTestFolder();

        $updateData = [
            'name' => '更新的文件夹名',
            'description' => '更新的描述',
        ];

        $client->request('PUT', "/gallery/api/folders/{$folder->getId()}", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], false !== json_encode($updateData) ? json_encode($updateData) : '{}');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('文件夹更新成功', $data['message']);
    }

    #[Test]
    public function testUpdateNonExistentFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $updateData = [
            'name' => '更新的文件夹名',
        ];

        $client->request('PUT', '/gallery/api/folders/99999', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], false !== json_encode($updateData) ? json_encode($updateData) : '{}');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('文件夹未找到', $data['error']);
    }

    #[Test]
    public function testDeleteFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $folder = $this->createTestFolder();

        $client->request('DELETE', "/gallery/api/folders/{$folder->getId()}");

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('文件夹删除成功', $data['message']);
    }

    #[Test]
    public function testDeleteNonExistentFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $client->request('DELETE', '/gallery/api/folders/99999');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('文件夹未找到', $data['error']);
    }

    #[Test]
    public function testUploadFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $folder = $this->createTestFolder();

        // 创建临时文件
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => $folder->getId(),
        ], [
            'file' => $uploadedFile,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('文件上传成功', $data['message']);
        $this->assertArrayHasKey('data', $data);
    }

    #[Test]
    public function testUploadFileWithoutFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $tempFile = tempnam(sys_get_temp_dir(), 'upload_test');
        file_put_contents($tempFile, 'test content');

        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.txt',
            'text/plain',
            null,
            true
        );

        $client->request('POST', '/gallery/api/upload', [], [
            'file' => $uploadedFile,
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('文件夹ID是必需的', $data['error']);
    }

    #[Test]
    public function testUploadFileWithoutFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $user = $this->loginAsAdmin($client);

        $folder = $this->createTestFolder();

        $client->request('POST', '/gallery/api/upload', [
            'folderId' => $folder->getId(),
        ]);

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('未上传文件', $data['error']);
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->catchExceptions(false);
        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/gallery');
    }

    private function createTestFile(string $name = 'test.txt', string $mimeType = 'text/plain'): File
    {
        $file = new File();
        $file->setOriginalName($name);
        $file->setFileName('test-' . uniqid() . '.txt');
        $file->setFilePath('uploads/' . $file->getFileName());
        $file->setType($mimeType);
        $file->setFileSize(1024);
        $file->setIsActive(true);
        $file->setCreateTime(new \DateTimeImmutable());
        $file->setValid(true);

        $this->fileRepository->save($file);

        return $file;
    }

    private function createTestFolder(string $name = '测试文件夹'): Folder
    {
        $folder = new Folder();
        $folder->setName($name);
        $folder->setDescription('测试文件夹描述');
        $folder->setIsActive(true);
        $folder->setCreateTime(new \DateTimeImmutable());

        $this->folderRepository->save($folder);

        return $folder;
    }
}
