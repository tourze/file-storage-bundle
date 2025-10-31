<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\FileStorageBundle\Entity\FileType;
use Tourze\FileStorageBundle\Repository\FileTypeRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(FileTypeRepository::class)]
#[RunTestsInSeparateProcesses]
final class FileTypeRepositoryTest extends AbstractRepositoryTestCase
{
    private FileTypeRepository $repository;

    private static int $counter = 0;

    public function testSave(): void
    {
        $fileType = $this->createFileType();

        self::getEntityManager()->persist($fileType);
        self::getEntityManager()->flush();

        $this->assertNotNull($fileType->getId());

        $foundFileType = $this->repository->find($fileType->getId());
        $this->assertNotNull($foundFileType);
        $this->assertEquals('Test File Type', $foundFileType->getName());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createFileType(array $data = []): FileType
    {
        ++self::$counter;

        $data = $this->resolveUniqueConstraints($data);

        return $this->buildFileTypeEntity($data);
    }

    public function testRemove(): void
    {
        $fileType = $this->createFileType();
        self::getEntityManager()->persist($fileType);
        self::getEntityManager()->flush();
        $fileTypeId = $fileType->getId();

        self::getEntityManager()->remove($fileType);
        self::getEntityManager()->flush();

        $foundFileType = $this->repository->find($fileTypeId);
        $this->assertNull($foundFileType);
    }

    public function testFindByMimeType(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType(['mimeType' => 'application/test-pdf', 'extension' => 'pdf1', 'uploadType' => 'anonymous']);
        $fileType2 = $this->createFileType(['mimeType' => 'application/test-pdf', 'extension' => 'pdf2', 'uploadType' => 'member']);
        $fileType3 = $this->createFileType(['mimeType' => 'application/test-pdf', 'extension' => 'pdf3', 'uploadType' => 'both']);
        $fileType4 = $this->createFileType(['mimeType' => 'image/test-jpeg', 'extension' => 'jpg1', 'uploadType' => 'both']);
        $fileType5 = $this->createFileType(['mimeType' => 'text/test-plain', 'extension' => 'txt1', 'uploadType' => 'both', 'isActive' => false]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->persist($fileType3);
        self::getEntityManager()->persist($fileType4);
        self::getEntityManager()->persist($fileType5);
        self::getEntityManager()->flush();

        // Test anonymous upload type
        $result = $this->repository->findByMimeType('application/test-pdf', 'anonymous');
        $this->assertNotNull($result);
        $this->assertContains($result->getUploadType(), ['anonymous', 'both']);

        // Test member upload type
        $result = $this->repository->findByMimeType('application/test-pdf', 'member');
        $this->assertNotNull($result);
        $this->assertContains($result->getUploadType(), ['member', 'both']);

        // Test non-existent mime type
        $result = $this->repository->findByMimeType('application/xml', 'anonymous');
        $this->assertNull($result);
    }

    public function testFindByExtension(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType(['extension' => 'test-pdf1', 'mimeType' => 'application/pdf1', 'uploadType' => 'anonymous']);
        $fileType2 = $this->createFileType(['extension' => 'test-pdf2', 'mimeType' => 'application/pdf2', 'uploadType' => 'member']);
        $fileType3 = $this->createFileType(['extension' => 'test-pdf3', 'mimeType' => 'application/pdf3', 'uploadType' => 'both']);
        $fileType4 = $this->createFileType(['extension' => 'test-jpg', 'mimeType' => 'image/jpeg', 'uploadType' => 'both']);
        $fileType5 = $this->createFileType(['extension' => 'test-png', 'mimeType' => 'image/png', 'uploadType' => 'both', 'isActive' => false]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->persist($fileType3);
        self::getEntityManager()->persist($fileType4);
        self::getEntityManager()->persist($fileType5);
        self::getEntityManager()->flush();

        // Test anonymous upload type
        $result = $this->repository->findByExtension('test-pdf1', 'anonymous');
        $this->assertNotNull($result);
        $this->assertEquals('anonymous', $result->getUploadType());

        // Test member upload type
        $result = $this->repository->findByExtension('test-pdf2', 'member');
        $this->assertNotNull($result);
        $this->assertEquals('member', $result->getUploadType());

        // Test both upload type should work for anonymous
        $result = $this->repository->findByExtension('test-pdf3', 'anonymous');
        $this->assertNotNull($result);
        $this->assertEquals('both', $result->getUploadType());

        // Test non-existent extension
        $result = $this->repository->findByExtension('xml', 'anonymous');
        $this->assertNull($result);
    }

    public function testFindActiveForAnonymous(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType(['extension' => 'anon-pdf', 'mimeType' => 'application/anon-pdf', 'uploadType' => 'anonymous', 'displayOrder' => 10]);
        $fileType2 = $this->createFileType(['extension' => 'anon-jpg', 'mimeType' => 'image/anon-jpeg', 'uploadType' => 'both', 'displayOrder' => 5]);
        $fileType3 = $this->createFileType(['extension' => 'anon-doc', 'mimeType' => 'application/anon-msword', 'uploadType' => 'member', 'displayOrder' => 1]);
        $fileType4 = $this->createFileType(['extension' => 'anon-txt', 'mimeType' => 'text/anon-plain', 'uploadType' => 'anonymous', 'isActive' => false]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->persist($fileType3);
        self::getEntityManager()->persist($fileType4);
        self::getEntityManager()->flush();

        $results = $this->repository->findActiveForAnonymous();

        $this->assertCount(2, $results);
        $this->assertEquals($fileType2->getId(), $results[0]->getId()); // displayOrder 5, uploadType 'both'
        $this->assertEquals($fileType1->getId(), $results[1]->getId()); // displayOrder 10, uploadType 'anonymous'
    }

    public function testFindActiveForMember(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType(['extension' => 'mem-ext1', 'mimeType' => 'app/mem1', 'uploadType' => 'member', 'displayOrder' => 10]);
        $fileType2 = $this->createFileType(['extension' => 'mem-ext2', 'mimeType' => 'app/mem2', 'uploadType' => 'both', 'displayOrder' => 5]);
        $fileType3 = $this->createFileType(['extension' => 'mem-ext3', 'mimeType' => 'app/mem3', 'uploadType' => 'anonymous', 'displayOrder' => 1]);
        $fileType4 = $this->createFileType(['extension' => 'mem-ext4', 'mimeType' => 'app/mem4', 'uploadType' => 'member', 'isActive' => false]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->persist($fileType3);
        self::getEntityManager()->persist($fileType4);
        self::getEntityManager()->flush();

        $results = $this->repository->findActiveForMember();

        $this->assertCount(2, $results);
        $this->assertEquals($fileType2->getId(), $results[0]->getId()); // displayOrder 5, uploadType 'both'
        $this->assertEquals($fileType1->getId(), $results[1]->getId()); // displayOrder 10, uploadType 'member'
    }

    public function testGetActiveMimeTypes(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType(['extension' => 'mime1', 'mimeType' => 'application/test-pdf', 'uploadType' => 'anonymous']);
        $fileType2 = $this->createFileType(['extension' => 'mime2', 'mimeType' => 'image/test-jpeg', 'uploadType' => 'both']);
        $fileType3 = $this->createFileType(['extension' => 'mime3', 'mimeType' => 'image/test-png', 'uploadType' => 'member']);
        $fileType4 = $this->createFileType(['extension' => 'mime4', 'mimeType' => 'application/test-msword', 'uploadType' => 'anonymous', 'isActive' => false]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->persist($fileType3);
        self::getEntityManager()->persist($fileType4);
        self::getEntityManager()->flush();

        // Test anonymous mime types
        $mimeTypes = $this->repository->getActiveMimeTypes('anonymous');
        $this->assertCount(2, $mimeTypes);
        $this->assertContains('application/test-pdf', $mimeTypes);
        $this->assertContains('image/test-jpeg', $mimeTypes);

        // Test member mime types
        $mimeTypes = $this->repository->getActiveMimeTypes('member');
        $this->assertCount(2, $mimeTypes);
        $this->assertContains('image/test-jpeg', $mimeTypes);
        $this->assertContains('image/test-png', $mimeTypes);
    }

    public function testFindAllOrderedByDisplayOrder(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType(['extension' => 'order1', 'mimeType' => 'test/order1', 'displayOrder' => 30]);
        $fileType2 = $this->createFileType(['extension' => 'order2', 'mimeType' => 'test/order2', 'displayOrder' => 10]);
        $fileType3 = $this->createFileType(['extension' => 'order3', 'mimeType' => 'test/order3', 'displayOrder' => 20]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->persist($fileType3);
        self::getEntityManager()->flush();

        $results = $this->repository->findAll();

        $this->assertCount(3, $results);
        $this->assertEquals(10, $results[0]->getDisplayOrder());
        $this->assertEquals(20, $results[1]->getDisplayOrder());
        $this->assertEquals(30, $results[2]->getDisplayOrder());
    }

    /**
     * 测试唯一约束场景
     */
    public function testUniqueConstraints(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType([
            'mimeType' => 'application/test-unique-pdf',
            'extension' => 'unique-pdf',
            'uploadType' => 'anonymous',
        ]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->flush();

        // Same mime type and extension but different upload type should work
        $fileType2 = $this->createFileType([
            'mimeType' => 'application/test-unique-pdf',
            'extension' => 'unique-pdf',
            'uploadType' => 'member',
        ]);

        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->flush();

        $this->assertNotNull($fileType2->getId());
        $this->assertNotEquals($fileType1->getId(), $fileType2->getId());
    }

    // ==== 基础的find方法测试 ====

    // ==== findAll方法测试 ====

    // ==== findBy方法测试 ====

    // ==== findOneBy方法测试 ====

    public function testFindOneByRespectsSortingWhenMultipleRecordsExist(): void
    {
        $fileType1 = $this->createFileType(['name' => 'Type Z', 'uploadType' => 'anonymous', 'displayOrder' => 20]);
        $fileType2 = $this->createFileType(['name' => 'Type A', 'uploadType' => 'anonymous', 'displayOrder' => 10]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['uploadType' => 'anonymous'], ['displayOrder' => 'ASC']);

        $this->assertNotNull($result);
        $this->assertEquals('Type A', $result->getName());
    }

    // ==== 特殊ID值测试 ====

    protected function onSetUp(): void
    {
        // 初始化服务
        $this->repository = self::getService(FileTypeRepository::class);

        // 重置计数器，确保每个测试方法从相同状态开始
        self::$counter = 0;
    }

    /**
     * @return ServiceEntityRepository<FileType>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): FileType
    {
        $fileType = new FileType();
        $fileType->setName('test-type-' . uniqid());
        $fileType->setMimeType('application/test-' . uniqid());
        $fileType->setExtension('test' . uniqid());
        $fileType->setMaxSize(10485760); // 10 MB
        $fileType->setUploadType('both');

        return $fileType;
    }

    // ==== findOneBy排序逻辑测试 ====
    public function testFindOneByRespectsSortingWithDisplayOrder(): void
    {
        $fileType1 = $this->createFileType(['name' => 'Type Z', 'uploadType' => 'anonymous', 'displayOrder' => 20]);
        $fileType2 = $this->createFileType(['name' => 'Type A', 'uploadType' => 'anonymous', 'displayOrder' => 10]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneBy(['uploadType' => 'anonymous'], ['displayOrder' => 'ASC']);

        $this->assertNotNull($result);
        $this->assertEquals('Type A', $result->getName());
        $this->assertEquals(10, $result->getDisplayOrder());
    }

    // ==== 可空字段IS NULL查询测试 ====
    public function testFindByNullableDescriptionField(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType(['extension' => 'null1', 'mimeType' => 'test/null1', 'description' => null]);
        $fileType2 = $this->createFileType(['extension' => 'null2', 'mimeType' => 'test/null2', 'description' => 'Test description']);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['description' => null]);

        $this->assertCount(1, $results);
        $this->assertNull($results[0]->getDescription());
    }

    public function testCountByNullableDescriptionField(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType(['extension' => 'count1', 'mimeType' => 'test/count1', 'description' => null]);
        $fileType2 = $this->createFileType(['extension' => 'count2', 'mimeType' => 'test/count2', 'description' => 'Test description']);
        $fileType3 = $this->createFileType(['extension' => 'count3', 'mimeType' => 'test/count3', 'description' => null]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->persist($fileType3);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['description' => null]);

        $this->assertEquals(2, $count);
    }

    /**
     * 解析唯一约束问题
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function resolveUniqueConstraints(array $data): array
    {
        $typeMapping = $this->getTypeMappingTable();
        $index = (self::$counter % count($typeMapping)) + 1;
        $mapping = $typeMapping[$index];

        return $this->applyMimeTypeAndExtensionLogic($data, $mapping);
    }

    /**
     * 获取类型映射表
     *
     * @return array<int, array{extension: string, mimeType: string}>
     */
    private function getTypeMappingTable(): array
    {
        return [
            1 => ['extension' => 'test1', 'mimeType' => 'test/test1'],
            2 => ['extension' => 'test2', 'mimeType' => 'test/test2'],
            3 => ['extension' => 'test3', 'mimeType' => 'test/test3'],
            4 => ['extension' => 'test4', 'mimeType' => 'test/test4'],
            5 => ['extension' => 'test5', 'mimeType' => 'test/test5'],
            6 => ['extension' => 'test6', 'mimeType' => 'test/test6'],
            7 => ['extension' => 'test7', 'mimeType' => 'test/test7'],
            8 => ['extension' => 'test8', 'mimeType' => 'test/test8'],
            9 => ['extension' => 'test9', 'mimeType' => 'test/test9'],
            10 => ['extension' => 'test10', 'mimeType' => 'test/test10'],
        ];
    }

    /**
     * 应用MIME类型和扩展名逻辑
     *
     * @param array<string, mixed> $data
     * @param array{extension: string, mimeType: string} $mapping
     * @return array<string, mixed>
     */
    private function applyMimeTypeAndExtensionLogic(array $data, array $mapping): array
    {
        if (isset($data['mimeType']) && !isset($data['extension'])) {
            // 用户指定了mimeType，生成唯一的extension
            $data['extension'] = $mapping['extension'] . '_' . self::$counter;
        } elseif (isset($data['extension']) && !isset($data['mimeType'])) {
            // 用户指定了extension，生成唯一的mimeType
            $data['mimeType'] = $mapping['mimeType'] . '_' . self::$counter;
        } elseif (!isset($data['extension']) && !isset($data['mimeType'])) {
            // 都没指定，使用映射表的默认值
            $data['extension'] = $mapping['extension'];
            $data['mimeType'] = $mapping['mimeType'];
        }
        // 如果两个都指定了，就保持用户的值不变

        return $data;
    }

    /**
     * 构建FileType实体
     *
     * @param array<string, mixed> $data
     */
    private function buildFileTypeEntity(array $data): FileType
    {
        $fileType = new FileType();
        $fileType->setName(is_string($data['name'] ?? null) ? $data['name'] : 'Test File Type');
        $fileType->setMimeType(is_string($data['mimeType'] ?? null) ? $data['mimeType'] : '');
        $fileType->setExtension(is_string($data['extension'] ?? null) ? $data['extension'] : '');
        $fileType->setMaxSize(is_int($data['maxSize'] ?? null) ? $data['maxSize'] : 10485760); // 10 MB
        $fileType->setUploadType(is_string($data['uploadType'] ?? null) ? $data['uploadType'] : 'both');
        $fileType->setDescription(array_key_exists('description', $data) && (is_string($data['description']) || null === $data['description']) ? $data['description'] : 'Test file type');
        $fileType->setIsActive(is_bool($data['isActive'] ?? null) ? $data['isActive'] : true);
        $fileType->setDisplayOrder(is_int($data['displayOrder'] ?? null) ? $data['displayOrder'] : 0);

        return $fileType;
    }

    // ==== 新增缺失的方法测试 ====

    public function testGetMaxSizeForMimeType(): void
    {
        // 清理数据库确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType1 = $this->createFileType([
            'mimeType' => 'application/test-maxsize-pdf',
            'extension' => 'maxsize-pdf',
            'uploadType' => 'anonymous',
            'maxSize' => 5242880, // 5MB
        ]);
        $fileType2 = $this->createFileType([
            'mimeType' => 'image/test-maxsize-jpeg',
            'extension' => 'maxsize-jpg',
            'uploadType' => 'both',
            'maxSize' => 2097152, // 2MB
        ]);

        self::getEntityManager()->persist($fileType1);
        self::getEntityManager()->persist($fileType2);
        self::getEntityManager()->flush();

        // 测试存在的MIME类型
        $maxSize = $this->repository->getMaxSizeForMimeType('application/test-maxsize-pdf', 'anonymous');
        $this->assertEquals(5242880, $maxSize);

        // 测试不存在的MIME类型
        $maxSize = $this->repository->getMaxSizeForMimeType('application/non-existent', 'anonymous');
        $this->assertNull($maxSize);
    }

    public function testRepositorySaveMethod(): void
    {
        self::getEntityManager()->createQuery('DELETE FROM Tourze\FileStorageBundle\Entity\FileType')->execute();
        self::getEntityManager()->flush();

        $fileType = $this->createFileType(['name' => 'Repository Save Test']);

        // 测试不刷新的保存
        $this->repository->save($fileType, false);

        // 测试带刷新的保存
        $this->repository->save($fileType, true);
        $this->assertNotNull($fileType->getId());
        $foundFileType = $this->repository->find($fileType->getId());
        $this->assertNotNull($foundFileType);
        $this->assertEquals('Repository Save Test', $foundFileType->getName());
    }

    public function testRepositoryRemoveMethod(): void
    {
        $fileType = $this->createFileType(['name' => 'Repository Remove Test']);
        $this->repository->save($fileType, true);
        $fileTypeId = $fileType->getId();

        // 确认文件类型存在
        $foundFileType = $this->repository->find($fileTypeId);
        $this->assertNotNull($foundFileType);

        // 测试删除
        $this->repository->remove($fileType, true);

        // 确认文件类型已删除
        $foundFileType = $this->repository->find($fileTypeId);
        $this->assertNull($foundFileType);
    }
}
