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
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryGetFoldersController;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryGetFoldersController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryGetFoldersControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
    }

    #[Test]
    public function testGetFolders(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
    }

    #[Test]
    public function testGetFoldersResponseStructure(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();
        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        // Verify response structure
        $this->assertArrayHasKey('success', $data);
        $this->assertIsBool($data['success']);
        $this->assertTrue($data['success']);

        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
    }

    #[Test]
    public function testGetFoldersReturnsTreeStructure(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();
        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        $folderTree = $data['data'];
        $this->assertIsArray($folderTree);

        // The folder tree should be an array (potentially empty)
        // Each folder in the tree should have expected structure if not empty
        foreach ($folderTree as $folder) {
            $this->assertIsArray($folder);
            // Basic folder structure validation would go here
            // but since we don't know the exact structure, we just verify it's an array
        }
    }

    #[Test]
    public function testRouteExists(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/folders');

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

        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testGetFoldersRoutePath(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Test that the route exists with proper path structure
        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();
        // Should not be 404 - route should exist
        $this->assertNotEquals(404, $response->getStatusCode());
        // Should return successful response (or internal server error if service fails)
        $this->assertTrue(
            Response::HTTP_OK === $response->getStatusCode()
            || Response::HTTP_INTERNAL_SERVER_ERROR === $response->getStatusCode()
        );
        // Content should be JSON
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testGetFoldersSuccessResponse(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();

        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertIsBool($data['success']);

        if ($data['success']) {
            $this->assertArrayHasKey('data', $data);
            $this->assertIsArray($data['data']);
        } else {
            $this->assertArrayHasKey('error', $data);
            $this->assertIsString($data['error']);
            $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        }
    }

    #[Test]
    public function testGetFoldersErrorHandling(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();
        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        // If there's an error, it should be properly formatted
        $this->assertArrayHasKey('success', $data);
        $this->assertIsBool($data['success']);
        if (!$data['success']) {
            $this->assertArrayHasKey('error', $data);
            $this->assertIsString($data['error']);
            $this->assertStringContainsString('加载文件夹失败', $data['error']);
        }
    }

    #[Test]
    public function testGetFoldersNoAuthenticationRequired(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // This endpoint should be accessible without authentication
        $client->request('GET', '/gallery/api/folders');

        $response = $client->getResponse();
        // Should not return 401 unauthorized
        $this->assertNotEquals(401, $response->getStatusCode());
        // Should return successful response or internal server error (but not authentication error)
        $this->assertTrue(
            Response::HTTP_OK === $response->getStatusCode()
            || Response::HTTP_INTERNAL_SERVER_ERROR === $response->getStatusCode()
        );
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Special case: POST method is handled by a different controller
        // (ImageGalleryCreateFolderController), so it should return 400 for missing data
        if ('POST' === $method) {
            $client->request($method, '/gallery/api/folders');
            $response = $client->getResponse();
            $this->assertSame(400, $response->getStatusCode());

            return;
        }

        $client->catchExceptions(false);
        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/gallery/api/folders');
    }
}
