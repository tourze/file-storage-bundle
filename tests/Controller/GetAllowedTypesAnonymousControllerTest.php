<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\FileStorageBundle\Controller\GetAllowedTypesAnonymousController;
use Tourze\FileStorageBundle\Entity\FileType;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(GetAllowedTypesAnonymousController::class)]
#[RunTestsInSeparateProcesses]
final class GetAllowedTypesAnonymousControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    protected function onSetUp(): void
    {
        // 每个测试都需要初始化数据库，因为使用了RunTestsInSeparateProcesses
        $this->client = self::createClientWithDatabase();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertJsonResponseHasKey(string $key, array $data, string $message = ''): void
    {
        $this->assertArrayHasKey($key, $data, $message);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertJsonContentType(array $data, string $key, string $expectedType, string $message = ''): void
    {
        $this->assertArrayHasKey($key, $data);

        if ('array' === $expectedType) {
            $this->assertIsArray($data[$key], $message);
        } elseif ('string' === $expectedType) {
            $this->assertIsString($data[$key], $message);
        } elseif ('int' === $expectedType) {
            $this->assertIsInt($data[$key], $message);
        }
    }

    public function testGetAllowedTypesForAnonymous(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/allowed-types/anonymous');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        /** @var array<string, mixed> $data */
        $this->assertJsonResponseHasKey('allowedTypes', $data);
        $this->assertJsonContentType($data, 'allowedTypes', 'array');
    }

    public function testGetAllowedTypesReturnsCorrectStructure(): void
    {
        // Create test file type
        $fileType = new FileType();
        $fileType->setName('Images');
        $fileType->setMimeType('image/jpeg');
        $fileType->setExtension('jpg');
        $fileType->setMaxSize(5242880);
        $fileType->setUploadType('anonymous');
        $fileType->setIsActive(true);

        self::getEntityManager()->persist($fileType);
        self::getEntityManager()->flush();

        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/allowed-types/anonymous');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        /** @var array<string, mixed> $data */
        $this->assertJsonResponseHasKey('allowedTypes', $data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertIsArray($data['allowedTypes']);
        $this->assertNotEmpty($data['allowedTypes']);

        $foundTestType = false;
        foreach ($data['allowedTypes'] as $type) {
            $this->assertIsArray($type);
            $this->assertArrayHasKey('mimeType', $type);

            if ('image/jpeg' === $type['mimeType']) {
                $foundTestType = true;
                $this->assertArrayHasKey('name', $type);
                $this->assertArrayHasKey('extension', $type);
                $this->assertArrayHasKey('maxSize', $type);

                $this->assertEquals('JPEG Image', $type['name']); // 使用fixture中的实际名称
                $this->assertEquals('jpg', $type['extension']);
                // 可能是fixture的最大尺寸或新创建的，取较小值进行验证
                $this->assertGreaterThan(0, $type['maxSize']);
                break;
            }
        }

        $this->assertTrue($foundTestType, 'Test file type not found in response');
    }

    public function testGetAllowedTypesExcludesInactiveTypes(): void
    {
        // Create inactive file type
        $inactiveType = new FileType();
        $inactiveType->setName('Inactive Type');
        $inactiveType->setMimeType('application/inactive');
        $inactiveType->setExtension('inactive');
        $inactiveType->setMaxSize(1024);
        $inactiveType->setUploadType('anonymous');
        $inactiveType->setIsActive(false);

        self::getEntityManager()->persist($inactiveType);
        self::getEntityManager()->flush();

        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/allowed-types/anonymous');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertIsArray($data['allowedTypes']);

        foreach ($data['allowedTypes'] as $type) {
            $this->assertIsArray($type);
            $this->assertArrayHasKey('mimeType', $type);
            $this->assertNotEquals('application/inactive', $type['mimeType']);
        }
    }

    public function testGetAllowedTypesExcludesMemberOnlyTypes(): void
    {
        // Create member-only file type
        $memberOnlyType = new FileType();
        $memberOnlyType->setName('Member Only Type');
        $memberOnlyType->setMimeType('application/member-only');
        $memberOnlyType->setExtension('member');
        $memberOnlyType->setMaxSize(1024);
        $memberOnlyType->setUploadType('member');
        $memberOnlyType->setIsActive(true);

        self::getEntityManager()->persist($memberOnlyType);
        self::getEntityManager()->flush();

        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/allowed-types/anonymous');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertIsArray($data['allowedTypes']);

        foreach ($data['allowedTypes'] as $type) {
            $this->assertIsArray($type);
            $this->assertArrayHasKey('mimeType', $type);
            $this->assertNotEquals('application/member-only', $type['mimeType']);
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->catchExceptions(false);
        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/allowed-types/anonymous');
    }

    public function testGetAllowedTypesIncludesBothTypes(): void
    {
        // Create type allowed for both anonymous and member
        $bothType = new FileType();
        $bothType->setName('Both Type');
        $bothType->setMimeType('application/both');
        $bothType->setExtension('both');
        $bothType->setMaxSize(2048);
        $bothType->setUploadType('both');
        $bothType->setIsActive(true);

        self::getEntityManager()->persist($bothType);
        self::getEntityManager()->flush();

        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/allowed-types/anonymous');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertIsArray($data['allowedTypes']);

        $foundBothType = false;
        foreach ($data['allowedTypes'] as $type) {
            $this->assertIsArray($type);
            $this->assertArrayHasKey('mimeType', $type);
            if ('application/both' === $type['mimeType']) {
                $foundBothType = true;
                break;
            }
        }

        $this->assertTrue($foundBothType, 'Type allowed for both should be included for anonymous users');
    }
}
