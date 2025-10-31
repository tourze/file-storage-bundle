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
        $this->client->catchExceptions(false);

        $this->expectException(MethodNotAllowedHttpException::class);

        $this->client->request($method, '/file/1/validate');
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
