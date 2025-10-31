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
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryGetFilesController;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryGetFilesController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryGetFilesControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
    }

    #[Test]
    public function testGetFiles(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files');

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

        $this->assertArrayHasKey('pagination', $responseData);
        $this->assertIsArray($responseData['pagination']);
    }

    #[Test]
    public function testGetFilesResponseStructure(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files');

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

        $this->assertArrayHasKey('pagination', $data);
        $this->assertIsArray($data['pagination']);

        // Verify pagination structure
        $pagination = $data['pagination'];
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);

        $this->assertIsInt($pagination['current_page']);
        $this->assertIsInt($pagination['total']);
        $this->assertIsInt($pagination['per_page']);
        $this->assertTrue(is_int($pagination['total_pages']) || is_float($pagination['total_pages']));
    }

    #[Test]
    public function testGetFilesWithPagination(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files?page=1&limit=5');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        $this->assertArrayHasKey('pagination', $data);
        $pagination = $data['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertSame(1, $pagination['current_page']);
        $this->assertSame(5, $pagination['per_page']);
    }

    #[Test]
    public function testGetFilesWithFilters(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Test with year filter
        $client->request('GET', '/gallery/api/files?year=2024');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
    }

    #[Test]
    public function testGetFilesWithMonthFilter(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files?year=2024&month=1');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    #[Test]
    public function testGetFilesWithFilenameFilter(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files?filename=test');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    #[Test]
    public function testGetFilesWithFolderFilter(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files?folder=all');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);

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
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        // Should return empty data for non-existent folder
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertEmpty($data['data']);

        // Should have proper pagination with zero results
        $this->assertArrayHasKey('pagination', $data);
        $pagination = $data['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('total_pages', $pagination);
        $this->assertSame(0, $pagination['total']);
        $this->assertSame(0, $pagination['total_pages']);
    }

    #[Test]
    public function testRouteExists(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files');

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

        $client->request('GET', '/gallery/api/files');

        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testGetFilesRoutePath(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Test that the route exists with proper path structure
        $client->request('GET', '/gallery/api/files');

        $response = $client->getResponse();
        // Should not be 404 - route should exist
        $this->assertNotEquals(404, $response->getStatusCode());
        // Should return successful response
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        // Content should be JSON
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testGetFilesSuccessResponse(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/files');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertIsArray($data['pagination']);
    }

    #[Test]
    public function testGetFilesWithInvalidPaginationValues(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Test with invalid page and limit values
        $client->request('GET', '/gallery/api/files?page=invalid&limit=invalid');

        $response = $client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        // Should use default pagination values
        $this->assertArrayHasKey('pagination', $data);
        $pagination = $data['pagination'];
        $this->assertIsArray($pagination);
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertSame(1, $pagination['current_page']); // Default page
        $this->assertSame(17, $pagination['per_page']); // Default limit
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

        $client->request($method, '/gallery/api/files');
    }
}
