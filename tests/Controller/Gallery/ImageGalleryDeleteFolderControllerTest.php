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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryDeleteFolderController;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryDeleteFolderController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryDeleteFolderControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
    }

    #[Test]
    public function testDeleteNonExistentFolder(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('DELETE', '/gallery/api/folders/99999');

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
        $this->assertSame('文件夹未找到', $responseData['error']);
    }

    #[Test]
    public function testDeleteFolderWithInvalidId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(NotFoundHttpException::class);

        $client->request('DELETE', '/gallery/api/folders/invalid');
    }

    #[Test]
    public function testDeleteFolderWithZeroId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('DELETE', '/gallery/api/folders/0');

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
        $this->assertSame('文件夹未找到', $responseData['error']);
    }

    #[Test]
    public function testDeleteFolderWithNegativeId(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('DELETE', '/gallery/api/folders/-1');

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
        $this->assertSame('文件夹未找到', $responseData['error']);
    }

    #[Test]
    public function testRouteExists(): void
    {
        // 仅验证路由是否注册存在，而不依赖数据库中是否存在具体资源
        $router = self::getContainer()->get('router');
        $this->assertInstanceOf(UrlGeneratorInterface::class, $router);

        // 通过路由名称生成 URL，若路由未注册会抛出异常
        $url = $router->generate('file_gallery_api_delete_folder', ['id' => 1]);
        $this->assertSame('/gallery/api/folders/1', $url, '应能通过命名路由生成正确的路径');
    }

    #[Test]
    public function testResponseContentTypeIsJson(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('DELETE', '/gallery/api/folders/99999');

        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testDeleteFolderRoutePath(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // 创建一个测试文件夹来确保路由正确匹配
        $entityManager = self::getEntityManager();
        $folder = new Folder();
        $folder->setName('test-route-folder');
        $folder->setDescription('Test folder for route testing');
        $folder->setIsActive(true);
        $folder->setIsPublic(false);
        $folder->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($folder);
        $entityManager->flush();

        $folderId = $folder->getId();

        // Test that the route exists with proper path structure
        $client->request('DELETE', '/gallery/api/folders/' . $folderId);

        $response = $client->getResponse();
        // Should not be 404 - route should exist
        $this->assertNotEquals(404, $response->getStatusCode());
        // Content should be JSON
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testDeleteFolderSuccessfulResponse(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Even with non-existent folder, we should get a proper JSON response structure
        $client->request('DELETE', '/gallery/api/folders/99999');

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
    public function testDeleteFolderErrorHandling(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Test with non-existent folder to trigger error path
        $client->request('DELETE', '/gallery/api/folders/99999');

        $response = $client->getResponse();
        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertFalse($data['success']);
        $this->assertArrayHasKey('error', $data);
        $this->assertIsString($data['error']);
        $this->assertSame('文件夹未找到', $data['error']);
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // PUT 方法被 ImageGalleryUpdateFolderController 处理，不应该抛出异常
        if ('PUT' === $method) {
            $client->request($method, '/gallery/api/folders/1');
            $response = $client->getResponse();
            $this->assertNotSame(405, $response->getStatusCode(), 'PUT method should be handled by UpdateFolderController');

            return;
        }

        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/gallery/api/folders/1');
    }
}
