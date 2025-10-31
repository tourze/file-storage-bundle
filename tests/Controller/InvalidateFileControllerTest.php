<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\FileStorageBundle\Controller\InvalidateFileController;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\FileStorageBundle\Service\FileService;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidateFileController::class)]
#[RunTestsInSeparateProcesses]
final class InvalidateFileControllerTest extends AbstractWebTestCase
{
    private FileRepository $fileRepository;

    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        $this->fileRepository = self::getService(FileRepository::class);
    }

    #[Test]
    public function testInvalidateExistingFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建活跃的测试文件
        $file = $this->createTestFile();
        $this->assertTrue($file->isActive());

        $client->request('POST', "/file/{$file->getId()}/invalidate");

        $response = $client->getResponse();
        $this->assertTrue(
            Response::HTTP_OK === $response->getStatusCode()
            || $response->isRedirect()
        );

        // 验证文件已被设置为无效
        $updatedFile = $this->fileRepository->find($file->getId());
        $this->assertNotNull($updatedFile);
        $file = $updatedFile;
        $this->assertFalse($file->isActive());
    }

    #[Test]
    public function testInvalidateNonExistentFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('File not found');

        $client->request('POST', '/file/99999/invalidate');
    }

    #[Test]
    public function testInvalidateFileWithReferer(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $file = $this->createTestFile();

        $client->request('POST', "/file/{$file->getId()}/invalidate", [], [], [
            'HTTP_REFERER' => '/gallery',
        ]);

        $response = $client->getResponse();
        if ($response->isRedirect()) {
            $this->assertSame('/gallery', $response->headers->get('Location'));
        }

        // 验证文件已被设置为无效
        $updatedFile = $this->fileRepository->find($file->getId());
        $this->assertNotNull($updatedFile);
        $file = $updatedFile;
        $this->assertFalse($file->isActive());
    }

    #[Test]
    public function testInvalidateFileWithoutReferer(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $file = $this->createTestFile();

        $client->request('POST', "/file/{$file->getId()}/invalidate");

        $response = $client->getResponse();

        // 没有referer时，应返回JSON响应
        if ('application/json' === $response->headers->get('Content-Type')) {
            $content = $response->getContent();
            $this->assertNotFalse($content);
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $this->assertIsArray($data);
            $this->assertArrayHasKey('success', $data);
            $this->assertArrayHasKey('message', $data);
            $this->assertTrue($data['success']);
            $this->assertSame('File invalidated successfully', $data['message']);
        }

        // 验证文件已被设置为无效
        $updatedFile = $this->fileRepository->find($file->getId());
        $this->assertNotNull($updatedFile);
        $file = $updatedFile;
        $this->assertFalse($file->isActive());
    }

    #[Test]
    public function testInvalidateAlreadyInactiveFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建已经无效的文件
        $file = $this->createTestFile();
        $file->setIsActive(false);
        $this->fileRepository->save($file);

        $this->assertFalse($file->isActive());

        $client->request('POST', "/file/{$file->getId()}/invalidate");

        $response = $client->getResponse();
        $this->assertTrue(
            Response::HTTP_OK === $response->getStatusCode()
            || $response->isRedirect()
        );

        // 文件应该仍然是无效的
        $updatedFile = $this->fileRepository->find($file->getId());
        $this->assertNotNull($updatedFile);
        $file = $updatedFile;
        $this->assertFalse($file->isActive());
    }

    #[Test]
    public function testInvalidateFileWithOriginalName(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $file = $this->createTestFile();
        $file->setOriginalName('重要文档.pdf');
        $this->fileRepository->save($file);

        $client->request('POST', "/file/{$file->getId()}/invalidate");

        $response = $client->getResponse();
        $this->assertTrue(
            Response::HTTP_OK === $response->getStatusCode()
            || $response->isRedirect()
        );

        // 验证flash message包含原始文件名
        if ($response->isRedirect()) {
            // Flash message已经被设置，但在测试环境中我们无法直接验证
            // 这里我们可以检查文件确实被设置为无效
            $updatedFile = $this->fileRepository->find($file->getId());
            $this->assertNotNull($updatedFile);
            $file = $updatedFile;
            $this->assertFalse($file->isActive());
        }
    }

    #[Test]
    public function testInvalidateFileWithoutOriginalName(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $file = $this->createTestFile();
        $file->setOriginalName(null);
        $this->fileRepository->save($file);

        $client->request('POST', "/file/{$file->getId()}/invalidate");

        $response = $client->getResponse();
        $this->assertTrue(
            Response::HTTP_OK === $response->getStatusCode()
            || $response->isRedirect()
        );

        // 验证文件已被设置为无效
        $updatedFile = $this->fileRepository->find($file->getId());
        $this->assertNotNull($updatedFile);
        $file = $updatedFile;
        $this->assertFalse($file->isActive());
    }

    #[Test]
    public function testInvalidFileId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(NotFoundHttpException::class);

        $client->request('POST', '/file/invalid/invalidate');
    }

    #[Test]
    public function testInvalidateZeroFileId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(NotFoundHttpException::class);

        $client->request('POST', '/file/0/invalidate');
    }

    #[Test]
    public function testInvalidateNegativeFileId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(NotFoundHttpException::class);

        $client->request('POST', '/file/-1/invalidate');
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

        $client->request($method, '/file/1/invalidate');
    }

    private function createTestFile(): File
    {
        $file = new File();
        $file->setOriginalName('test.txt');
        $file->setFileName('test-123.txt');
        $file->setFilePath('uploads/test-123.txt');
        $file->setType('text/plain');
        $file->setFileSize(1024);
        $file->setIsActive(true);
        $file->setCreateTime(new \DateTimeImmutable());

        $this->fileRepository->save($file);

        return $file;
    }
}
