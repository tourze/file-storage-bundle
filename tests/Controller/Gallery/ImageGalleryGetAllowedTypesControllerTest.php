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
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryGetAllowedTypesController;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryGetAllowedTypesController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryGetAllowedTypesControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
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
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);

        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);

        $this->assertArrayHasKey('member', $responseData['data']);
        $this->assertArrayHasKey('anonymous', $responseData['data']);

        $this->assertIsArray($responseData['data']['member']);
        $this->assertIsArray($responseData['data']['anonymous']);
    }

    #[Test]
    public function testGetAllowedTypesResponseStructure(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/allowed-types');

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

        // Verify data structure
        $allowedTypes = $data['data'];
        $this->assertArrayHasKey('member', $allowedTypes);
        $this->assertArrayHasKey('anonymous', $allowedTypes);

        // Both should be arrays
        $this->assertIsArray($allowedTypes['member']);
        $this->assertIsArray($allowedTypes['anonymous']);
    }

    #[Test]
    public function testGetAllowedTypesReturnsExpectedMimeTypes(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/allowed-types');

        $response = $client->getResponse();
        $content = $response->getContent();
        $this->assertIsString($content);

        $data = json_decode($content, true);
        $this->assertIsArray($data);

        $allowedTypes = $data['data'];
        $this->assertIsArray($allowedTypes);
        $this->assertArrayHasKey('member', $allowedTypes);
        $this->assertIsArray($allowedTypes['member']);

        // Member types should be an array of strings (mime types)
        foreach ($allowedTypes['member'] as $mimeType) {
            $this->assertIsString($mimeType);
            // Basic mime type format check
            $this->assertMatchesRegularExpression('/^[a-z]+\/[a-z0-9\-\+\.]+$/i', $mimeType);
        }

        // Anonymous types should be an array of strings (mime types)
        $this->assertArrayHasKey('anonymous', $allowedTypes);
        $this->assertIsArray($allowedTypes['anonymous']);
        foreach ($allowedTypes['anonymous'] as $mimeType) {
            $this->assertIsString($mimeType);
            // Basic mime type format check
            $this->assertMatchesRegularExpression('/^[a-z]+\/[a-z0-9\-\+\.]+$/i', $mimeType);
        }
    }

    #[Test]
    public function testRouteExists(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/allowed-types');

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

        $client->request('GET', '/gallery/api/allowed-types');

        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testGetAllowedTypesRoutePath(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // Test that the route exists with proper path structure
        $client->request('GET', '/gallery/api/allowed-types');

        $response = $client->getResponse();
        // Should not be 404 - route should exist
        $this->assertNotEquals(404, $response->getStatusCode());
        // Should return successful response
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        // Content should be JSON
        $this->assertSame('application/json', $response->headers->get('content-type'));
    }

    #[Test]
    public function testGetAllowedTypesSuccessResponse(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/allowed-types');

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
    }

    #[Test]
    public function testGetAllowedTypesNoAuthenticationRequired(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        // This endpoint should be accessible without authentication
        $client->request('GET', '/gallery/api/allowed-types');

        $response = $client->getResponse();
        // Should not return 401 unauthorized
        $this->assertNotEquals(401, $response->getStatusCode());
        // Should return successful response
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
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

        $client->request($method, '/gallery/api/allowed-types');
    }
}
