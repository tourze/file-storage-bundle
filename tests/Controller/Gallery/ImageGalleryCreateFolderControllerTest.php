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
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryCreateFolderController;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryCreateFolderController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryCreateFolderControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
    }

    #[Test]
    public function testCreateFolderWithValidData(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $data = [
            'name' => 'Test Folder',
            'description' => 'Test Description',
        ];

        $client->request(
            'POST',
            '/gallery/api/folders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($data) ? json_encode($data) : ''
        );

        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
    }

    #[Test]
    public function testCreateFolderWithMissingName(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $data = [
            'description' => 'Test Description without name',
        ];

        $client->request(
            'POST',
            '/gallery/api/folders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($data) ? json_encode($data) : ''
        );

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertSame('文件夹名称是必需的', $responseData['error']);
    }

    #[Test]
    public function testCreateFolderWithEmptyName(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $data = [
            'name' => '   ',
            'description' => 'Test Description',
        ];

        $client->request(
            'POST',
            '/gallery/api/folders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($data) ? json_encode($data) : ''
        );

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertSame('文件夹名称不能为空', $responseData['error']);
    }

    #[Test]
    public function testCreateFolderWithInvalidJson(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request(
            'POST',
            '/gallery/api/folders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertSame('文件夹名称是必需的', $responseData['error']);
    }

    #[Test]
    public function testCreateFolderWithNonExistentParent(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $data = [
            'name' => 'Child Folder',
            'description' => 'Test Description',
            'parentId' => 99999,
        ];

        $client->request(
            'POST',
            '/gallery/api/folders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($data) ? json_encode($data) : ''
        );

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
        $this->assertSame('上级文件夹未找到', $responseData['error']);
    }

    #[Test]
    public function testRouteExists(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('POST', '/gallery/api/folders');

        $response = $client->getResponse();
        $this->assertNotEquals(404, $response->getStatusCode(), 'Route should exist');
    }

    #[Test]
    public function testResponseContentTypeIsJson(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $data = ['name' => 'Test'];
        $client->request(
            'POST',
            '/gallery/api/folders',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($data) ? json_encode($data) : ''
        );

        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // GET 方法被 ImageGalleryGetFoldersController 处理，不应该抛出异常
        if ('GET' === $method) {
            $client->request($method, '/gallery/api/folders');
            $response = $client->getResponse();
            $this->assertSame(200, $response->getStatusCode());

            return;
        }

        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/gallery/api/folders');
    }
}
