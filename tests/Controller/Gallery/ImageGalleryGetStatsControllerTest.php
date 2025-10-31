<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller\Gallery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryGetStatsController;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Exception\ClientNotInitializedException;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryGetStatsController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryGetStatsControllerTest extends AbstractWebTestCase
{
    private ?KernelBrowser $client = null;

    private FileRepository $fileRepository;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        $this->fileRepository = self::getService(FileRepository::class);

        // 清理数据库中的文件数据以确保测试隔离
        $entityManager = self::getEntityManager();
        $files = $this->fileRepository->findAll();
        foreach ($files as $file) {
            $entityManager->remove($file);
        }
        $entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(string $content): array
    {
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            self::fail('Failed to decode JSON response');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    public function testGetStatsWithNoFiles(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $client->request('GET', '/gallery/api/stats');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('content-type'));

        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);

        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);

        $stats = $responseData['data'];
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('image_count', $stats);
        $this->assertArrayHasKey('document_count', $stats);
        $this->assertArrayHasKey('recent_count', $stats);
        $this->assertArrayHasKey('total_size_formatted', $stats);

        $this->assertEquals(0, $stats['total_files']);
        $this->assertEquals(0, $stats['total_size']);
        $this->assertEquals(0, $stats['image_count']);
        $this->assertEquals(0, $stats['document_count']);
        $this->assertEquals(0, $stats['recent_count']);
        $this->assertIsString($stats['total_size_formatted']);
        $this->assertEquals('0 B', $stats['total_size_formatted']);
    }

    public function testGetStatsWithFiles(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $entityManager = self::getEntityManager();

        // 创建测试文件
        $imageFile = new File();
        $imageFile->setOriginalName('test.jpg');
        $imageFile->setFileName('test_image.jpg');
        $imageFile->setFilePath('/uploads/test_image.jpg');
        $imageFile->setType('image/jpeg');
        $imageFile->setFileSize(1024 * 1024); // 1MB
        $imageFile->setValid(true);
        $imageFile->setCreateTime(new \DateTimeImmutable());

        $documentFile = new File();
        $documentFile->setOriginalName('doc.pdf');
        $documentFile->setFileName('test_doc.pdf');
        $documentFile->setFilePath('/uploads/test_doc.pdf');
        $documentFile->setType('application/pdf');
        $documentFile->setFileSize(512 * 1024); // 512KB
        $documentFile->setValid(true);
        $documentFile->setCreateTime(new \DateTimeImmutable('-10 days'));

        $recentFile = new File();
        $recentFile->setOriginalName('recent.png');
        $recentFile->setFileName('recent.png');
        $recentFile->setFilePath('/uploads/recent.png');
        $recentFile->setType('image/png');
        $recentFile->setFileSize(2 * 1024 * 1024); // 2MB
        $recentFile->setValid(true);
        $recentFile->setCreateTime(new \DateTimeImmutable('-1 day'));

        $entityManager->persist($imageFile);
        $entityManager->persist($documentFile);
        $entityManager->persist($recentFile);
        $entityManager->flush();

        $client->request('GET', '/gallery/api/stats');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);

        $this->assertTrue($responseData['success']);
        $stats = $responseData['data'];
        $this->assertIsArray($stats);

        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('total_size', $stats);
        $this->assertArrayHasKey('image_count', $stats);
        $this->assertArrayHasKey('document_count', $stats);
        $this->assertArrayHasKey('recent_count', $stats);
        $this->assertArrayHasKey('total_size_formatted', $stats);

        $this->assertEquals(3, $stats['total_files']);
        $this->assertEquals(3670016, $stats['total_size']); // 1MB + 512KB + 2MB
        $this->assertEquals(2, $stats['image_count']); // 2 images
        $this->assertEquals(1, $stats['document_count']); // 1 document
        $this->assertEquals(2, $stats['recent_count']); // files created within last 7 days
        $this->assertIsString($stats['total_size_formatted']);
        $this->assertStringContainsString('3.5 MB', $stats['total_size_formatted']);
    }

    public function testGetStatsIgnoresInvalidFiles(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $entityManager = self::getEntityManager();

        // 创建有效和无效的文件
        $validFile = new File();
        $validFile->setOriginalName('valid.jpg');
        $validFile->setFileName('valid.jpg');
        $validFile->setFilePath('/uploads/valid.jpg');
        $validFile->setType('image/jpeg');
        $validFile->setFileSize(1024);
        $validFile->setValid(true);
        $validFile->setCreateTime(new \DateTimeImmutable());

        $invalidFile = new File();
        $invalidFile->setOriginalName('invalid.jpg');
        $invalidFile->setFileName('invalid.jpg');
        $invalidFile->setFilePath('/uploads/invalid.jpg');
        $invalidFile->setType('image/jpeg');
        $invalidFile->setFileSize(1024);
        $invalidFile->setValid(false);
        $invalidFile->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($validFile);
        $entityManager->persist($invalidFile);
        $entityManager->flush();

        $client->request('GET', '/gallery/api/stats');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);

        $stats = $responseData['data'];
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertEquals(1, $stats['total_files']); // 只统计有效文件
    }

    public function testFormatFileSize(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $entityManager = self::getEntityManager();

        // 创建不同大小的文件来测试格式化
        $testCases = [
            ['size' => 0, 'expected' => '0 B'],
            ['size' => 500, 'expected' => '500 B'],
            ['size' => 1536, 'expected' => '1.5 KB'], // 1.5 KB
            ['size' => 1572864, 'expected' => '1.5 MB'], // 1.5 MB
            ['size' => 1610612736, 'expected' => '1.5 GB'], // 1.5 GB
        ];

        foreach ($testCases as $testCase) {
            $file = new File();
            $file->setOriginalName("test_{$testCase['size']}.jpg");
            $file->setFileName("test_{$testCase['size']}.jpg");
            $file->setFilePath("/uploads/test_{$testCase['size']}.jpg");
            $file->setType('image/jpeg');
            $file->setFileSize($testCase['size']);
            $file->setValid(true);
            $file->setCreateTime(new \DateTimeImmutable());

            $entityManager->persist($file);
        }
        $entityManager->flush();

        $client->request('GET', '/gallery/api/stats');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);

        // 验证总大小格式化正确
        $stats = $responseData['data'];
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_size_formatted', $stats);
        $this->assertIsString($stats['total_size_formatted']);
        $this->assertStringContainsString(' ', $stats['total_size_formatted']);
    }

    public function testGetStatsRecentCountCalculation(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $entityManager = self::getEntityManager();

        // 创建不同时间的文件
        $recentFile1 = new File();
        $recentFile1->setOriginalName('recent1.jpg');
        $recentFile1->setFileName('recent1.jpg');
        $recentFile1->setFilePath('/uploads/recent1.jpg');
        $recentFile1->setType('image/jpeg');
        $recentFile1->setFileSize(1024);
        $recentFile1->setValid(true);
        $recentFile1->setCreateTime(new \DateTimeImmutable('-3 days'));

        $recentFile2 = new File();
        $recentFile2->setOriginalName('recent2.jpg');
        $recentFile2->setFileName('recent2.jpg');
        $recentFile2->setFilePath('/uploads/recent2.jpg');
        $recentFile2->setType('image/jpeg');
        $recentFile2->setFileSize(1024);
        $recentFile2->setValid(true);
        $recentFile2->setCreateTime(new \DateTimeImmutable('-6 days'));

        $oldFile = new File();
        $oldFile->setOriginalName('old.jpg');
        $oldFile->setFileName('old.jpg');
        $oldFile->setFilePath('/uploads/old.jpg');
        $oldFile->setType('image/jpeg');
        $oldFile->setFileSize(1024);
        $oldFile->setValid(true);
        $oldFile->setCreateTime(new \DateTimeImmutable('-10 days'));

        $entityManager->persist($recentFile1);
        $entityManager->persist($recentFile2);
        $entityManager->persist($oldFile);
        $entityManager->flush();

        $client->request('GET', '/gallery/api/stats');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);

        $stats = $responseData['data'];
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('recent_count', $stats);
        $this->assertEquals(3, $stats['total_files']);
        $this->assertEquals(2, $stats['recent_count']); // 只有7天内的文件
    }

    public function testFileTypeClassification(): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }
        $entityManager = self::getEntityManager();

        // 创建不同类型的文件
        $imageFile1 = new File();
        $imageFile1->setOriginalName('image1.jpg');
        $imageFile1->setFileName('image1.jpg');
        $imageFile1->setFilePath('/uploads/image1.jpg');
        $imageFile1->setType('image');
        $imageFile1->setFileSize(1024);
        $imageFile1->setValid(true);
        $imageFile1->setCreateTime(new \DateTimeImmutable());

        $imageFile2 = new File();
        $imageFile2->setOriginalName('image2.png');
        $imageFile2->setFileName('image2.png');
        $imageFile2->setFilePath('/uploads/image2.png');
        $imageFile2->setType('image/png');
        $imageFile2->setFileSize(1024);
        $imageFile2->setValid(true);
        $imageFile2->setCreateTime(new \DateTimeImmutable());

        $documentFile = new File();
        $documentFile->setOriginalName('doc.pdf');
        $documentFile->setFileName('doc.pdf');
        $documentFile->setFilePath('/uploads/doc.pdf');
        $documentFile->setType('application/pdf');
        $documentFile->setFileSize(1024);
        $documentFile->setValid(true);
        $documentFile->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($imageFile1);
        $entityManager->persist($imageFile2);
        $entityManager->persist($documentFile);
        $entityManager->flush();

        $client->request('GET', '/gallery/api/stats');

        $response = $client->getResponse();
        $this->assertSame(200, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertNotFalse($content);
        $responseData = $this->decodeJsonResponse($content);

        $stats = $responseData['data'];
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_files', $stats);
        $this->assertArrayHasKey('image_count', $stats);
        $this->assertArrayHasKey('document_count', $stats);
        $this->assertEquals(3, $stats['total_files']);
        $this->assertEquals(2, $stats['image_count']); // type='image' 和 type='image/png'
        $this->assertEquals(1, $stats['document_count']); // 非image类型
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = $this->client;
        if (null === $client) {
            throw new ClientNotInitializedException('Client not initialized');
        }

        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/gallery/api/stats');
    }
}
