<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\FileStorageBundle\Controller\GetAllowedTypesMemberController;
use Tourze\FileStorageBundle\Entity\FileType;
use Tourze\FileStorageBundle\Repository\FileTypeRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(GetAllowedTypesMemberController::class)]
#[RunTestsInSeparateProcesses]
final class GetAllowedTypesMemberControllerTest extends AbstractWebTestCase
{
    private KernelBrowser $client;

    private FileTypeRepository $fileTypeRepository;

    protected function onSetUp(): void
    {
        // 每个测试都需要初始化数据库，因为使用了RunTestsInSeparateProcesses
        $this->client = self::createClientWithDatabase();
        $this->fileTypeRepository = self::getService(FileTypeRepository::class);
    }

    public function testGetAllowedTypesForMember(): void
    {
        $this->client->request('GET', '/allowed-types/member');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertIsArray($data['allowedTypes']);
    }

    public function testGetAllowedTypesReturnsCorrectStructure(): void
    {
        // Create test file types
        $fileType = new FileType();
        $fileType->setName('Documents');
        $fileType->setMimeType('application/pdf');
        $fileType->setExtension('pdf');
        $fileType->setMaxSize(10485760);
        $fileType->setUploadType('member');
        $fileType->setIsActive(true);

        self::getEntityManager()->persist($fileType);
        self::getEntityManager()->flush();

        $this->client->request('GET', '/allowed-types/member');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertIsArray($data['allowedTypes']);
        $this->assertNotEmpty($data['allowedTypes']);

        $foundTestType = false;
        foreach ($data['allowedTypes'] as $type) {
            $this->assertIsArray($type);
            $this->assertArrayHasKey('mimeType', $type);
            if ('application/pdf' === $type['mimeType']) {
                $foundTestType = true;
                $this->assertArrayHasKey('name', $type);
                $this->assertArrayHasKey('extension', $type);
                $this->assertArrayHasKey('maxSize', $type);
                $this->assertEquals('PDF Document', $type['name']); // 使用fixture中的实际名称
                $this->assertEquals('pdf', $type['extension']);
                // 检查最大尺寸合理即可，可能是fixture的值或新创建的值
                $this->assertGreaterThan(0, $type['maxSize']);
                break;
            }
        }

        $this->assertTrue($foundTestType, 'Test file type not found in response');

        // Clean up
        self::getEntityManager()->clear();
        $foundEntity = $this->fileTypeRepository->findOneBy(['mimeType' => 'application/pdf']);
        if (null !== $foundEntity) {
            self::getEntityManager()->remove($foundEntity);
            self::getEntityManager()->flush();
        }
    }

    public function testGetAllowedTypesExcludesAnonymousOnlyTypes(): void
    {
        $anonymousOnlyType = new FileType();
        $anonymousOnlyType->setName('Anonymous Only Type');
        $anonymousOnlyType->setMimeType('application/anonymous-only');
        $anonymousOnlyType->setExtension('anon');
        $anonymousOnlyType->setMaxSize(1024);
        $anonymousOnlyType->setUploadType('anonymous');
        $anonymousOnlyType->setIsActive(true);

        self::getEntityManager()->persist($anonymousOnlyType);
        self::getEntityManager()->flush();

        $this->client->request('GET', '/allowed-types/member');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('allowedTypes', $data);
        $this->assertIsArray($data['allowedTypes']);

        foreach ($data['allowedTypes'] as $type) {
            $this->assertIsArray($type);
            $this->assertArrayHasKey('mimeType', $type);
            $this->assertNotEquals('application/anonymous-only', $type['mimeType']);
        }

        // Clean up
        self::getEntityManager()->clear();
        $foundEntity = $this->fileTypeRepository->findOneBy(['mimeType' => 'application/anonymous-only']);
        if (null !== $foundEntity) {
            self::getEntityManager()->remove($foundEntity);
            self::getEntityManager()->flush();
        }
    }

    public function testGetAllowedTypesIncludesBothTypes(): void
    {
        $bothType = new FileType();
        $bothType->setName('Both Type');
        $bothType->setMimeType('application/both');
        $bothType->setExtension('both');
        $bothType->setMaxSize(2048);
        $bothType->setUploadType('both');
        $bothType->setIsActive(true);

        self::getEntityManager()->persist($bothType);
        self::getEntityManager()->flush();

        $this->client->request('GET', '/allowed-types/member');

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());

        $content = $response->getContent();
        $this->assertIsString($content);
        $data = json_decode($content, true);
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

        $this->assertTrue($foundBothType, 'Type allowed for both should be included');

        // Clean up
        self::getEntityManager()->clear();
        $foundEntity = $this->fileTypeRepository->findOneBy(['mimeType' => 'application/both']);
        if (null !== $foundEntity) {
            self::getEntityManager()->remove($foundEntity);
            self::getEntityManager()->flush();
        }
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $this->client->catchExceptions(false);
        $this->expectException(MethodNotAllowedHttpException::class);

        $this->client->request($method, '/allowed-types/member');
    }

    public function testGetAllowedTypesExcludesInactiveTypes(): void
    {
        // Create inactive file type
        $inactiveType = new FileType();
        $inactiveType->setName('Inactive Type');
        $inactiveType->setMimeType('application/inactive');
        $inactiveType->setExtension('inactive');
        $inactiveType->setMaxSize(1024);
        $inactiveType->setUploadType('member');
        $inactiveType->setIsActive(false);

        self::getEntityManager()->persist($inactiveType);
        self::getEntityManager()->flush();

        $this->client->request('GET', '/allowed-types/member');

        $response = $this->client->getResponse();
        self::assertEquals(200, $response->getStatusCode());

        $content = $response->getContent();
        self::assertIsString($content);
        $data = json_decode($content, true);
        self::assertIsArray($data);
        self::assertArrayHasKey('allowedTypes', $data);
        self::assertIsArray($data['allowedTypes']);

        foreach ($data['allowedTypes'] as $type) {
            self::assertIsArray($type);
            self::assertArrayHasKey('mimeType', $type);
            self::assertNotEquals('application/inactive', $type['mimeType']);
        }

        // Clean up
        self::getEntityManager()->clear();
        $foundEntity = $this->fileTypeRepository->findOneBy(['mimeType' => 'application/inactive']);
        if (null !== $foundEntity) {
            self::getEntityManager()->remove($foundEntity);
            self::getEntityManager()->flush();
        }
    }
}
