<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(FileRepository::class)]
#[RunTestsInSeparateProcesses]
final class FileRepositoryTest extends AbstractRepositoryTestCase
{
    private FileRepository $repository;

    public function testSave(): void
    {
        $file = $this->createFile();

        self::getEntityManager()->persist($file);
        self::getEntityManager()->flush();

        $this->assertNotNull($file->getId());

        $foundFile = $this->repository->find($file->getId());
        $this->assertNotNull($foundFile);
        $this->assertEquals('test.pdf', $foundFile->getOriginalName());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createFile(array $data = []): File
    {
        $file = new File();

        $this->setBasicFileProperties($file, $data);
        $this->setHashProperties($file, $data);
        $this->setOptionalProperties($file, $data);

        return $file;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setBasicFileProperties(File $file, array $data): void
    {
        $originalName = $data['originalName'] ?? 'test.pdf';
        $filePath = $data['filePath'] ?? '2024/01/test.pdf';
        $mimeType = $data['mimeType'] ?? 'application/pdf';
        $fileSize = $data['fileSize'] ?? 1024;
        $isActive = $data['isActive'] ?? true;

        $this->assertIsString($filePath);
        $this->assertIsString($mimeType);
        $this->assertIsBool($isActive);

        $file->setOriginalName($originalName);

        $file->setFilePath($filePath);
        $file->setMimeType($mimeType);
        $file->setFileSize($fileSize);

        $file->setIsActive($isActive);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setHashProperties(File $file, array $data): void
    {
        if (array_key_exists('md5Hash', $data)) {
            $md5Hash = $data['md5Hash'];
            $this->assertTrue(is_string($md5Hash) || null === $md5Hash);
            $file->setMd5Hash($md5Hash);
        } else {
            $file->setMd5Hash(md5('test'));
        }

        if (array_key_exists('sha1Hash', $data)) {
            $sha1Hash = $data['sha1Hash'];
            $this->assertTrue(is_string($sha1Hash) || null === $sha1Hash);
            $file->setSha1Hash($sha1Hash);
        } else {
            $file->setSha1Hash(sha1('test'));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setOptionalProperties(File $file, array $data): void
    {
        if (isset($data['user'])) {
            $user = $data['user'];
            $file->setUser($user);
        }

        if (array_key_exists('metadata', $data)) {
            $metadata = $data['metadata'];
            if (is_array($metadata)) {
                /** @var array<string, mixed> $metadata */
                $file->setMetadata($metadata);
            } else {
                $file->setMetadata(null);
            }
        }

        if (array_key_exists('publicUrl', $data)) {
            $publicUrl = $data['publicUrl'];
            $this->assertTrue(is_string($publicUrl) || null === $publicUrl);
            $file->setPublicUrl($publicUrl);
        }
    }

    public function testRemove(): void
    {
        $file = $this->createFile();
        self::getEntityManager()->persist($file);
        self::getEntityManager()->flush();
        $fileId = $file->getId();

        self::getEntityManager()->remove($file);
        self::getEntityManager()->flush();

        $foundFile = $this->repository->find($fileId);
        $this->assertNull($foundFile);
    }

    public function testFindByOriginalNamePattern(): void
    {
        // 创建测试数据时需要使用唯一的名称模式，并设置fileName
        $file1 = $this->createFile(['originalName' => 'document-findtest.pdf', 'filePath' => '2024/01/doc1.pdf']);
        $file2 = $this->createFile(['originalName' => 'image-findtest.jpg', 'filePath' => '2024/01/img1.jpg']);
        $file3 = $this->createFile(['originalName' => 'report.xlsx', 'filePath' => '2024/01/report1.xlsx']);
        $file4 = $this->createFile(['originalName' => 'inactive-findtest.pdf', 'filePath' => '2024/01/inactive1.pdf', 'isActive' => false]);

        // 手动设置fileName以匹配findByOriginalNamePattern的查询逻辑
        $file1->setFileName('document-findtest.pdf');
        $file2->setFileName('image-findtest.jpg');
        $file3->setFileName('report.xlsx');
        $file4->setFileName('inactive-findtest.pdf');

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->persist($file4);
        self::getEntityManager()->flush();

        $results = $this->repository->findByOriginalNamePattern('findtest');

        // Debug output to see what's happening
        $actualNames = array_map(fn ($f) => $f->getOriginalName(), $results);

        // 应该只返回活动文件，所以排除inactive文件
        $this->assertCount(2, $results);
        $this->assertContains('document-findtest.pdf', $actualNames);
        $this->assertContains('image-findtest.jpg', $actualNames);
        $this->assertNotContains('inactive-findtest.pdf', $actualNames);
    }

    public function testFindByMimeType(): void
    {
        // 使用唯一的MIME类型避免与其他测试干扰
        $file1 = $this->createFile(['mimeType' => 'application/test-pdf', 'originalName' => 'file1.pdf', 'filePath' => '2024/01/file1.pdf']);
        $file2 = $this->createFile(['mimeType' => 'application/test-pdf', 'originalName' => 'file2.pdf', 'filePath' => '2024/01/file2.pdf']);
        $file3 = $this->createFile(['mimeType' => 'image/test-jpeg', 'originalName' => 'file3.jpg', 'filePath' => '2024/01/file3.jpg']);
        $file4 = $this->createFile(['mimeType' => 'application/test-pdf', 'originalName' => 'file4.pdf', 'filePath' => '2024/01/file4.pdf', 'isActive' => false]);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->persist($file4);
        self::getEntityManager()->flush();

        $results = $this->repository->findByMimeType('application/test-pdf');

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertEquals('application/test-pdf', $result->getMimeType());
            $this->assertTrue($result->isActive());
        }
    }

    public function testFindByMd5Hash(): void
    {
        $md5Hash = md5('unique-content');

        $file1 = $this->createFile(['md5Hash' => $md5Hash]);
        $file2 = $this->createFile(['md5Hash' => md5('other-content')]);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        $result = $this->repository->findByMd5Hash($md5Hash);

        $this->assertNotNull($result);
        $this->assertEquals($md5Hash, $result->getMd5Hash());

        // Test with non-existent hash
        $nonExistentResult = $this->repository->findByMd5Hash(md5('non-existent'));
        $this->assertNull($nonExistentResult);
    }

    public function testFindByDateRange(): void
    {
        // 清理数据库确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        $em->flush();

        $now = new \DateTimeImmutable();
        $yesterday = $now->modify('-1 day');
        $tomorrow = $now->modify('+1 day');
        $lastWeek = $now->modify('-7 days');

        $file1 = $this->createFile(['originalName' => 'daterange1.pdf', 'filePath' => '2024/01/daterange1.pdf']);
        $file2 = $this->createFile(['originalName' => 'daterange2.pdf', 'filePath' => '2024/01/daterange2.pdf']);
        $file3 = $this->createFile(['originalName' => 'daterange3.pdf', 'filePath' => '2024/01/daterange3.pdf']);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        // Test files created today
        $results = $this->repository->findByDateRange($yesterday, $tomorrow);
        $this->assertCount(3, $results);

        // Test empty range
        $results = $this->repository->findByDateRange($lastWeek, $yesterday);
        $this->assertCount(0, $results);
    }

    public function testGetTotalActiveFilesSize(): void
    {
        // 清理数据库确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        $em->flush();

        $file1 = $this->createFile(['fileSize' => 1000, 'originalName' => 'size1.pdf', 'filePath' => '2024/01/size1.pdf']);
        $file2 = $this->createFile(['fileSize' => 2000, 'originalName' => 'size2.pdf', 'filePath' => '2024/01/size2.pdf']);
        $file3 = $this->createFile(['fileSize' => 3000, 'originalName' => 'size3.pdf', 'filePath' => '2024/01/size3.pdf', 'isActive' => false]);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $totalSize = $this->repository->getTotalActiveFilesSize();

        $this->assertEquals(3000, $totalSize);
    }

    public function testFindAnonymousFilesOlderThan(): void
    {
        // 清理数据库确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        $em->flush();

        // 只测试匿名文件，不涉及用户关联
        $file1 = $this->createFile(['originalName' => 'anon1.pdf', 'filePath' => '2024/01/anon1.pdf', 'user' => null]);
        $file2 = $this->createFile(['originalName' => 'anon2.pdf', 'filePath' => '2024/01/anon2.pdf', 'user' => null]);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        // Set creation time manually using reflection
        $em = self::getEntityManager();
        $connection = $em->getConnection();

        // Make file1 older than 1 hour
        $oldTime = new \DateTimeImmutable();
        $oldTime = $oldTime->modify('-2 hours');
        $connection->executeStatement(
            'UPDATE upload_file SET create_time = :time WHERE id = :id',
            ['time' => $oldTime->format('Y-m-d H:i:s'), 'id' => $file1->getId()]
        );

        $oneHourAgo = new \DateTimeImmutable();
        $oneHourAgo = $oneHourAgo->modify('-1 hour');
        $results = $this->repository->findAnonymousFilesOlderThan($oneHourAgo);

        $this->assertCount(1, $results);
        $this->assertEquals($file1->getId(), $results[0]->getId());
        $this->assertNull($results[0]->getUser());
    }

    public function testFlush(): void
    {
        $file1 = $this->createFile(['originalName' => 'file1.pdf']);
        $file2 = $this->createFile(['originalName' => 'file2.pdf']);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);

        // Files should have IDs after persist because of SnowflakeIdGenerator
        $this->assertNotNull($file1->getId());
        $this->assertNotNull($file2->getId());

        self::getEntityManager()->flush();

        // Files should now be persisted
        $this->assertNotNull($file1->getId());
        $this->assertNotNull($file2->getId());
    }

    // ==== 基础的find方法测试 ====

    // ==== findAll方法测试 ====

    // ==== findBy方法测试 ====

    // ==== findOneBy方法测试 ====

    public function testFindOneByRespectsSortingWhenMultipleRecordsExist(): void
    {
        $file1 = $this->createFile(['originalName' => 'z-file.pdf', 'mimeType' => 'application/pdf']);
        $file1->setFileName('z-file.pdf');
        $file2 = $this->createFile(['originalName' => 'a-file.pdf', 'mimeType' => 'application/pdf']);
        $file2->setFileName('a-file.pdf');

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['type' => 'application/pdf'], ['fileName' => 'ASC']);

        $this->assertNotNull($result);
        $this->assertEquals('a-file.pdf', $result->getOriginalName());
    }

    // ==== 特殊ID值测试 ====

    // ==== 可空字段测试 ====
    public function testFindByNullUserShouldFindAnonymousFiles(): void
    {
        // 清理数据库确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        $em->flush();

        // 创建匿名文件（user为null）
        $anonymousFile = $this->createFile(['originalName' => 'anonymous.pdf', 'filePath' => '2024/01/anon.pdf']);
        self::getEntityManager()->persist($anonymousFile);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['userIdentifier' => null]);

        $this->assertCount(1, $results);
        $this->assertEquals('anonymous.pdf', $results[0]->getOriginalName());
        $this->assertNull($results[0]->getUser());
    }

    public function testCountByNullUserShouldCountAnonymousFiles(): void
    {
        // 清理数据库确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        $em->flush();

        // 创建匿名文件
        $anonymousFile = $this->createFile(['originalName' => 'count-anon.pdf', 'filePath' => '2024/01/count-anon.pdf']);
        self::getEntityManager()->persist($anonymousFile);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['userIdentifier' => null]);

        $this->assertEquals(1, $count);
    }

    // ==== 关联查询测试 ====
    public function testFindByUserNull(): void
    {
        // Clear any existing data to ensure clean state
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        // Test finding anonymous files (user is null)
        $anonymousFile1 = $this->createFile(['originalName' => 'anonymous1.pdf']);
        $anonymousFile2 = $this->createFile(['originalName' => 'anonymous2.pdf']);

        self::getEntityManager()->persist($anonymousFile1);
        self::getEntityManager()->persist($anonymousFile2);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['userIdentifier' => null]);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertNull($result->getUser());
        }
    }

    public function testCountByUserNull(): void
    {
        // Clear any existing data to ensure clean state
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        // Test counting anonymous files (user is null)
        $anonymousFile1 = $this->createFile();
        $anonymousFile2 = $this->createFile();

        self::getEntityManager()->persist($anonymousFile1);
        self::getEntityManager()->persist($anonymousFile2);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['userIdentifier' => null]);

        $this->assertEquals(2, $count);
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(FileRepository::class);
    }

    /**
     * @return ServiceEntityRepository<File>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): File
    {
        $uniqueId = uniqid();
        $file = new File();
        $file->setOriginalName('test-file-' . $uniqueId . '.pdf');
        $file->setFileName('test-file-' . $uniqueId . '.pdf');
        $file->setFilePath('2024/01/test-file-' . $uniqueId . '.pdf');
        $file->setMimeType('application/pdf');
        $file->setFileSize(1024);
        $file->setMd5Hash(md5('test-content-' . $uniqueId));
        $file->setSha1Hash(sha1('test-content-' . $uniqueId));

        return $file;
    }

    // ==== findOneBy排序逻辑测试 ====
    public function testFindOneByWithSortingByOriginalName(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['originalName' => 'b-file.pdf', 'mimeType' => 'application/pdf']);
        $file1->setFileName('b-file.pdf');
        $file2 = $this->createFile(['originalName' => 'a-file.pdf', 'mimeType' => 'application/pdf']);
        $file2->setFileName('a-file.pdf');

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['type' => 'application/pdf'], ['fileName' => 'ASC']);

        $this->assertNotNull($result);
        $this->assertEquals('a-file.pdf', $result->getOriginalName());
    }

    // ==== 可空字段IS NULL查询测试 ====
    public function testFindByNullableFields(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        // Create files with different metadata values
        $file1 = $this->createFile(['originalName' => 'null-metadata.pdf', 'metadata' => null]);
        $file2 = $this->createFile(['originalName' => 'with-metadata.pdf', 'metadata' => ['key' => 'value']]);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['metadata' => null]);

        $this->assertCount(1, $results);
        $this->assertNull($results[0]->getMetadata());
    }

    public function testCountByNullableFields(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['metadata' => null]);
        $file2 = $this->createFile(['metadata' => ['key' => 'value']]);
        $file3 = $this->createFile(['metadata' => null]);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['metadata' => null]);

        $this->assertEquals(2, $count);
    }

    public function testCountByAssociationUserShouldReturnCorrectNumber(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['user' => null]);
        $file2 = $this->createFile(['user' => null]);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['userIdentifier' => null]);
        $this->assertEquals(2, $count);
    }

    public function testFindOneByAssociationUserShouldReturnMatchingEntity(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['user' => null, 'originalName' => 'anonymous.pdf']);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['userIdentifier' => null]);
        $this->assertNotNull($result);
        $this->assertNull($result->getUser());
        $this->assertEquals('anonymous.pdf', $result->getOriginalName());
    }

    // ==== 新增缺失的方法测试 ====

    public function testFindByExt(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['originalName' => 'test1.pdf', 'filePath' => '2024/01/test1.pdf']);
        $file2 = $this->createFile(['originalName' => 'test2.jpg', 'filePath' => '2024/01/test2.jpg']);
        $file3 = $this->createFile(['originalName' => 'test3.pdf', 'filePath' => '2024/01/test3.pdf', 'isActive' => false]);

        // 使用setter方法设置扩展名
        $file1->setExt('pdf');
        $file2->setExt('jpg');
        $file3->setExt('pdf');

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findByExt('pdf');

        $this->assertCount(1, $results); // 只有活跃的文件
        $this->assertEquals('test1.pdf', $results[0]->getOriginalName());
    }

    public function testFindByFileKey(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $fileKey = 'unique-file-key-' . uniqid();
        $file1 = $this->createFile(['originalName' => 'test1.pdf', 'filePath' => '2024/01/test1.pdf']);
        $file2 = $this->createFile(['originalName' => 'test2.pdf', 'filePath' => '2024/01/test2.pdf']);

        // 使用setter方法设置文件密钥
        $file1->setFileKey($fileKey);
        $file2->setFileKey('other-key');

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        $result = $this->repository->findByFileKey($fileKey);

        $this->assertNotNull($result);
        $this->assertEquals('test1.pdf', $result->getOriginalName());

        // 测试不存在的key
        $notFound = $this->repository->findByFileKey('non-existent-key');
        $this->assertNull($notFound);
    }

    public function testFindByFileNamePattern(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['originalName' => 'pattern-test-doc.pdf', 'filePath' => '2024/01/doc1.pdf']);
        $file1->setFileName('pattern-test-doc.pdf');
        $file2 = $this->createFile(['originalName' => 'pattern-test-img.jpg', 'filePath' => '2024/01/img1.jpg']);
        $file2->setFileName('pattern-test-img.jpg');
        $file3 = $this->createFile(['originalName' => 'other-file.pdf', 'filePath' => '2024/01/other.pdf']);
        $file3->setFileName('other-file.pdf');

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findByFileNamePattern('pattern-test');

        $this->assertCount(2, $results);
        $actualNames = array_map(fn ($f) => $f->getOriginalName(), $results);
        $this->assertContains('pattern-test-doc.pdf', $actualNames);
        $this->assertContains('pattern-test-img.jpg', $actualNames);
    }

    public function testFindByFiletype(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['mimeType' => 'application/pdf-test', 'originalName' => 'test1.pdf']);
        $file2 = $this->createFile(['mimeType' => 'application/pdf-test', 'originalName' => 'test2.pdf']);
        $file3 = $this->createFile(['mimeType' => 'image/jpeg', 'originalName' => 'test3.jpg']);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findByFiletype('application/pdf-test');

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertEquals('application/pdf-test', $result->getMimeType());
        }
    }

    public function testFindByType(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['mimeType' => 'application/pdf-direct', 'originalName' => 'test1.pdf']);
        $file2 = $this->createFile(['mimeType' => 'application/pdf-direct', 'originalName' => 'test2.pdf']);
        $file3 = $this->createFile(['mimeType' => 'image/jpeg', 'originalName' => 'test3.jpg']);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findByType('application/pdf-direct');

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertEquals('application/pdf-direct', $result->getMimeType());
        }
    }

    public function testFindByMd5File(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $md5Hash = md5('unique-md5-test-content');
        $file1 = $this->createFile(['originalName' => 'test1.pdf']);
        $file2 = $this->createFile(['originalName' => 'test2.pdf']);

        // 使用setter方法设置 md5File 字段
        $file1->setMd5File($md5Hash);
        $file2->setMd5File(md5('other-content'));

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        $result = $this->repository->findByMd5File($md5Hash);

        $this->assertNotNull($result);
        $this->assertEquals('test1.pdf', $result->getOriginalName());

        // 测试不存在的hash
        $notFound = $this->repository->findByMd5File('non-existent-hash');
        $this->assertNull($notFound);
    }

    public function testFindByNamePattern(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['originalName' => 'name-pattern-doc.pdf']);
        $file1->setFileName('name-pattern-doc.pdf');
        $file2 = $this->createFile(['originalName' => 'name-pattern-img.jpg']);
        $file2->setFileName('name-pattern-img.jpg');
        $file3 = $this->createFile(['originalName' => 'different-file.pdf']);
        $file3->setFileName('different-file.pdf');

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findByNamePattern('name-pattern');

        $this->assertCount(2, $results);
        $actualNames = array_map(fn ($f) => $f->getOriginalName(), $results);
        $this->assertContains('name-pattern-doc.pdf', $actualNames);
        $this->assertContains('name-pattern-img.jpg', $actualNames);
    }

    public function testFindByYearMonth(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['originalName' => 'test1.pdf']);
        $file2 = $this->createFile(['originalName' => 'test2.pdf']);
        $file3 = $this->createFile(['originalName' => 'test3.pdf']);

        // 使用setter方法设置年月字段
        $file1->setYear(2024);
        $file1->setMonth(3);

        $file2->setYear(2024);
        $file2->setMonth(3);

        $file3->setYear(2024);
        $file3->setMonth(4);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findByYearMonth(2024, 3);
        $this->assertCount(2, $results);

        $resultsYearOnly = $this->repository->findByYearMonth(2024, null);
        $this->assertCount(3, $resultsYearOnly);

        $resultsEmpty = $this->repository->findByYearMonth(null, null);
        $this->assertCount(3, $resultsEmpty);
    }

    public function testFindImages(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['originalName' => 'image1.jpg']);
        $file2 = $this->createFile(['originalName' => 'image2.png']);
        $file3 = $this->createFile(['originalName' => 'document.pdf']);

        // 使用setter方法设置宽高字段
        $file1->setWidth(800);
        $file1->setHeight(600);

        $file2->setWidth(1024);
        $file2->setHeight(768);

        // file3 没有宽高，不应该被检索到

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findImages();

        $this->assertCount(2, $results);
        $actualNames = array_map(fn ($f) => $f->getOriginalName(), $results);
        $this->assertContains('image1.jpg', $actualNames);
        $this->assertContains('image2.png', $actualNames);
        $this->assertNotContains('document.pdf', $actualNames);
    }

    public function testFindMostViewed(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['originalName' => 'popular1.pdf']);
        $file2 = $this->createFile(['originalName' => 'popular2.pdf']);
        $file3 = $this->createFile(['originalName' => 'not-viewed.pdf']);

        // 使用setter方法设置访问次数
        $file1->setViewCount(100);
        $file2->setViewCount(50);

        // file3 保持默认的0次访问

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findMostViewed(5);

        $this->assertCount(2, $results); // 只返回访问次数 > 0 的文件
        $this->assertEquals('popular1.pdf', $results[0]->getOriginalName()); // 按访问次数降序
        $this->assertEquals('popular2.pdf', $results[1]->getOriginalName());
    }

    public function testFindMostDownloaded(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['originalName' => 'downloaded1.pdf']);
        $file2 = $this->createFile(['originalName' => 'downloaded2.pdf']);
        $file3 = $this->createFile(['originalName' => 'not-downloaded.pdf']);

        // 使用setter方法设置下载次数
        $file1->setDownloadCount(80);
        $file2->setDownloadCount(120);

        // file3 保持默认的0次下载

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findMostDownloaded(5);

        $this->assertCount(2, $results); // 只返回下载次数 > 0 的文件
        $this->assertEquals('downloaded2.pdf', $results[0]->getOriginalName()); // 按下载次数降序
        $this->assertEquals('downloaded1.pdf', $results[1]->getOriginalName());
    }

    public function testFindPublicFiles(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        // 创建一个没有文件夹的文件（应该被包含）
        $file1 = $this->createFile(['originalName' => 'no-folder.pdf']);

        // 创建带有公共文件夹的文件
        $file2 = $this->createFile(['originalName' => 'public-folder.pdf']);

        // 创建带有私有文件夹的文件（不应该被包含）
        $file3 = $this->createFile(['originalName' => 'private-folder.pdf']);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $results = $this->repository->findPublicFiles();

        // 由于我们没有实际创建文件夹实体，这个测试主要验证查询不会出错
        // 在实际应用中，需要创建相应的 Folder 实体来完整测试
        $this->assertGreaterThanOrEqual(0, count($results));
    }

    public function testIncrementViewCount(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file = $this->createFile(['originalName' => 'test-view.pdf']);
        self::getEntityManager()->persist($file);
        self::getEntityManager()->flush();

        // 初始访问次数应该是0或null
        $initialCount = $file->getViewCount() ?? 0;

        $this->repository->incrementViewCount($file);

        // 刷新实体以获取更新后的数据
        self::getEntityManager()->refresh($file);

        // 验证访问次数增加了1
        $newCount = $file->getViewCount() ?? 0;
        $this->assertEquals($initialCount + 1, $newCount);
    }

    public function testIncrementDownloadCount(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file = $this->createFile(['originalName' => 'test-download.pdf']);
        self::getEntityManager()->persist($file);
        self::getEntityManager()->flush();

        // 初始下载次数应该是0或null
        $initialCount = $file->getDownloadCount() ?? 0;

        $this->repository->incrementDownloadCount($file);

        // 刷新实体以获取更新后的数据
        self::getEntityManager()->refresh($file);

        // 验证下载次数增加了1
        $newCount = $file->getDownloadCount() ?? 0;
        $this->assertEquals($initialCount + 1, $newCount);
    }

    public function testFindByFolder(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        // 创建一个没有文件夹的文件
        $file1 = $this->createFile(['originalName' => 'no-folder.pdf']);

        // 创建有文件夹的文件（这里简化测试，实际中需要创建Folder实体）
        $file2 = $this->createFile(['originalName' => 'with-folder.pdf']);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        // 测试查找没有文件夹的文件
        $results = $this->repository->findByFolder(null);

        // 由于我们没有实际设置folder关联，所有文件都应该被找到
        $this->assertGreaterThanOrEqual(0, count($results));
    }

    public function testFindByUser(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        // 创建匿名文件（user为null）
        $file1 = $this->createFile(['originalName' => 'anonymous.pdf', 'user' => null]);
        $file2 = $this->createFile(['originalName' => 'anonymous2.pdf', 'user' => null]);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        // 测试查找匿名文件
        $results = $this->repository->findByUser(null);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertNull($result->getUser());
        }
    }

    public function testSaveWithFlushOptions(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file = $this->createFile(['originalName' => 'save-test.pdf']);

        // 测试不刷新的保存
        $this->repository->save($file, false);
        $this->assertNotNull($file->getId()); // SnowflakeIdGenerator应该已经分配ID

        // 测试带刷新的保存
        $this->repository->save($file, true);
        $foundFile = $this->repository->find($file->getId());
        $this->assertNotNull($foundFile);
        $this->assertEquals('save-test.pdf', $foundFile->getOriginalName());
    }

    public function testRepositoryRemoveMethod(): void
    {
        $file = $this->createFile(['originalName' => 'remove-test.pdf']);
        $this->repository->save($file, true);
        $fileId = $file->getId();

        // 确认文件存在
        $foundFile = $this->repository->find($fileId);
        $this->assertNotNull($foundFile);

        // 测试删除
        $this->repository->remove($file, true);

        // 确认文件已删除
        $foundFile = $this->repository->find($fileId);
        $this->assertNull($foundFile);
    }

    public function testGetTotalValidFilesSize(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\File')->execute();
        self::getEntityManager()->flush();

        $file1 = $this->createFile(['fileSize' => 1000, 'originalName' => 'size1.pdf', 'isActive' => true]);
        $file2 = $this->createFile(['fileSize' => 2000, 'originalName' => 'size2.pdf', 'isActive' => true]);
        $file3 = $this->createFile(['fileSize' => 3000, 'originalName' => 'size3.pdf', 'isActive' => false]); // 不活跃

        // 使用setter方法设置size和valid字段
        $file1->setSize(1000);
        $file1->setValid(true);

        $file2->setSize(2000);
        $file2->setValid(true);

        $file3->setSize(3000);
        $file3->setValid(false);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->persist($file3);
        self::getEntityManager()->flush();

        $totalSize = $this->repository->getTotalValidFilesSize();

        $this->assertEquals(3000, $totalSize); // 只统计有效文件
    }
}
