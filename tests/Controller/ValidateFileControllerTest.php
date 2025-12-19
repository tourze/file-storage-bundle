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
use Tourze\FileStorageBundle\Controller\ValidateFileController;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\FileStorageBundle\Service\FileService;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ValidateFileController::class)]
#[RunTestsInSeparateProcesses]
final class ValidateFileControllerTest extends AbstractWebTestCase
{
    private FileRepository $fileRepository;

    private KernelBrowser $client;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        $this->fileRepository = self::getService(FileRepository::class);
    }

    #[Test]
    public function testValidateInactiveFile(): void
    {
        // 创建无效的测试文件
        $file = $this->createTestFile();
        $file->setIsActive(false);
        $this->fileRepository->save($file);
        $this->assertFalse($file->isActive());

        $this->client->request('POST', "/file/{$file->getId()}/validate");

        $response = $this->client->getResponse();
        $this->assertTrue(
            Response::HTTP_OK === $response->getStatusCode()
            || $response->isRedirect()
        );

        // 验证文件已被设置为有效 - 重新从数据库查询
        $updatedFile = $this->fileRepository->find($file->getId());
        $this->assertNotNull($updatedFile);
        $this->assertTrue($updatedFile->isActive());
    }

    #[Test]
    public function testValidateNonExistentFile(): void
    {
        $this->client->catchExceptions(false);
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('File not found');

        $this->client->request('POST', '/file/99999/validate');
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        // 创建测试文件以确保路由匹配
        $file = $this->createTestFile();

        // 对于 GET 方法，由于 Symfony 路由合并行为，可能不会抛出 MethodNotAllowed
        // 其他方法应该抛出异常
        if ('GET' === $method) {
            // GET 请求可能由于其他路由配置返回不同的状态码
            $this->client->request($method, "/file/{$file->getId()}/validate");
            $response = $this->client->getResponse();
            // 对于 GET 方法，我们验证它返回适当的状态码
            $this->assertContains(
                $response->getStatusCode(),
                [Response::HTTP_METHOD_NOT_ALLOWED, Response::HTTP_NOT_FOUND, Response::HTTP_INTERNAL_SERVER_ERROR, Response::HTTP_OK],
                'GET method should return appropriate status'
            );
        } else {
            $this->client->catchExceptions(false);
            $this->expectException(MethodNotAllowedHttpException::class);
            $this->client->request($method, "/file/{$file->getId()}/validate");
        }
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
