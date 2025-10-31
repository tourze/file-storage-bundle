<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller;

use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\FileStorageBundle\Controller\DownloadFileController;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\FileStorageBundle\Service\FileService;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(DownloadFileController::class)]
#[RunTestsInSeparateProcesses]
final class DownloadFileControllerTest extends AbstractWebTestCase
{
    private FileRepository $fileRepository;

    private FilesystemOperator $filesystem;

    private ?KernelBrowser $client = null;

    /** @var array<string> */
    private array $testFiles = [];

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        $this->fileRepository = self::getService(FileRepository::class);
        $this->filesystem = self::getService(FilesystemOperator::class);
    }

    protected function onTearDown(): void
    {
        // 清理测试文件
        foreach ($this->testFiles as $filePath) {
            try {
                if ($this->filesystem->fileExists($filePath)) {
                    $this->filesystem->delete($filePath);
                }
            } catch (\Exception $e) {
                // 忽略清理错误
            }
        }
        $this->testFiles = [];

        parent::onTearDown();
    }

    #[Test]
    public function testDownloadExistingFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建测试文件
        $file = $this->createTestFile();

        $client->request('GET', "/file/{$file->getId()}/download");

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        // 验证响应头
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('attachment; filename=', $contentDisposition);
        $this->assertSame((string) $file->getFileSize(), $response->headers->get('Content-Length'));
    }

    #[Test]
    public function testDownloadNonExistentFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('File not found');

        $client->request('GET', '/file/99999/download');
    }

    #[Test]
    public function testDownloadFileWithoutContent(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建一个没有内容的文件记录（不创建实际文件）
        $file = $this->createTestFile(false);

        // 应该抛出 NotFoundHttpException，因为文件内容不存在
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Unable to download file');

        $client->request('GET', "/file/{$file->getId()}/download");
    }

    #[Test]
    public function testDownloadFileWithReferer(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建一个没有内容的文件记录
        $file = $this->createTestFile(false);

        $client->request('GET', "/file/{$file->getId()}/download", [], [], [
            'HTTP_REFERER' => '/gallery',
        ]);

        $response = $client->getResponse();
        if ($response->isRedirect()) {
            $this->assertSame('/gallery', $response->headers->get('Location'));
        }
    }

    #[Test]
    public function testDownloadFileWithOriginalName(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $file = $this->createTestFile();
        $file->setOriginalName('测试文档.pdf');
        $this->fileRepository->save($file);

        $client->request('GET', "/file/{$file->getId()}/download");

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('filename="测试文档.pdf"', $contentDisposition);
    }

    #[Test]
    public function testDownloadFileWithoutOriginalName(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $file = $this->createTestFile();
        $file->setOriginalName(null);
        $this->fileRepository->save($file);

        $client->request('GET', "/file/{$file->getId()}/download");

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('filename="download"', $contentDisposition);
    }

    #[Test]
    public function testInvalidFileId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(NotFoundHttpException::class);

        $client->request('GET', '/file/invalid/download');
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

        $client->request($method, '/file/1/download');
    }

    private function createTestFile(bool $withContent = true): File
    {
        $fileContent = 'This is test file content for download testing.';
        $fileName = 'test-' . uniqid() . '.txt';
        $filePath = 'uploads/' . $fileName;

        $file = new File();
        $file->setOriginalName('test.txt');
        $file->setFileName($fileName);
        $file->setFilePath($filePath);
        $file->setType('text/plain');
        $file->setFileSize(strlen($fileContent));
        $file->setIsActive(true);
        $file->setCreateTime(new \DateTimeImmutable());

        $this->fileRepository->save($file);

        if ($withContent) {
            // 创建实际文件
            $this->filesystem->write($filePath, $fileContent);
            $this->testFiles[] = $filePath; // 记录文件路径，用于清理
        }

        return $file;
    }
}
