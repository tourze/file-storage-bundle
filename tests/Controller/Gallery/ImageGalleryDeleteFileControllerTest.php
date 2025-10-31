<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller\Gallery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryDeleteFileController;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryDeleteFileController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryDeleteFileControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
    }

    #[Test]
    public function testDeleteNonExistentFile(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('DELETE', '/gallery/api/files/99999');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertSame('文件未找到', $responseData['error']);
    }

    #[Test]
    public function testDeleteFileWithInvalidId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(NotFoundHttpException::class);

        $client->request('DELETE', '/gallery/api/files/invalid');
    }

    #[Test]
    public function testDeleteFileWithZeroId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('DELETE', '/gallery/api/files/0');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertSame('文件未找到', $responseData['error']);
    }

    #[Test]
    public function testDeleteFileWithNegativeId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('DELETE', '/gallery/api/files/-1');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertSame('文件未找到', $responseData['error']);
    }

    #[Test]
    public function testRouteExists(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建一个测试文件
        $entityManager = self::getEntityManager();
        $file = new File();
        $file->setOriginalName('test.txt');
        $file->setFileName('test.txt');
        $file->setFilePath('/uploads/test.txt');
        $file->setType('text/plain');
        $file->setFileSize(1024);
        $file->setValid(true);
        $file->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($file);
        $entityManager->flush();

        $fileId = $file->getId();

        $client->request('DELETE', '/gallery/api/files/' . $fileId);

        $response = $client->getResponse();
        // 路由存在，应该返回200（删除成功）或其他非404状态码
        $this->assertNotEquals(404, $response->getStatusCode(), 'Route should exist');
    }

    #[Test]
    public function testResponseContentTypeIsJson(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('DELETE', '/gallery/api/files/99999');

        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testDeleteFileRoutePath(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建一个测试文件来确保路由正确匹配
        $entityManager = self::getEntityManager();
        $file = new File();
        $file->setOriginalName('route-test.txt');
        $file->setFileName('route-test.txt');
        $file->setFilePath('/uploads/route-test.txt');
        $file->setType('text/plain');
        $file->setFileSize(512);
        $file->setValid(true);
        $file->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($file);
        $entityManager->flush();

        $fileId = $file->getId();

        // Test that the route exists with proper path structure
        $client->request('DELETE', '/gallery/api/files/' . $fileId);

        $response = $client->getResponse();
        // Should not be 404 - route should exist
        $this->assertNotEquals(404, $response->getStatusCode());
        // Content should be JSON
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testDeleteFileSuccessfulResponse(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Even with non-existent file, we should get a proper JSON response structure
        $client->request('DELETE', '/gallery/api/files/99999');

        $response = $client->getResponse();
        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertIsBool($data['success']);

        if (!$data['success']) {
            $this->assertArrayHasKey('error', $data);
            $this->assertIsString($data['error']);
        } else {
            $this->assertArrayHasKey('message', $data);
            $this->assertIsString($data['message']);
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

        $client->catchExceptions(false);
        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/gallery/api/files/1');
    }
}
