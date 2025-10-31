<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(FolderRepository::class)]
#[RunTestsInSeparateProcesses]
final class FolderRepositoryTest extends AbstractRepositoryTestCase
{
    private FolderRepository $folderRepository;

    protected function onSetUp(): void
    {
        $this->folderRepository = self::getService(FolderRepository::class);
    }

    public function testSaveAndFind(): void
    {
        $folder = new Folder();
        $folder->setTitle('Test Folder');
        $folder->setPath('test-folder');
        $folder->setDescription('Test Description');

        self::getEntityManager()->persist($folder);
        self::getEntityManager()->flush();

        $this->assertNotNull($folder->getId());

        $foundFolder = $this->folderRepository->find($folder->getId());
        $this->assertNotNull($foundFolder);
        $this->assertEquals('Test Folder', $foundFolder->getTitle());
        $this->assertEquals('test-folder', $foundFolder->getPath());
        $this->assertEquals('Test Description', $foundFolder->getDescription());
    }

    public function testFindByNamePattern(): void
    {
        $folder1 = new Folder();
        $folder1->setTitle('Documents Folder');
        $folder1->setPath('documents');
        self::getEntityManager()->persist($folder1);

        $folder2 = new Folder();
        $folder2->setTitle('Images Folder');
        $folder2->setPath('images');
        self::getEntityManager()->persist($folder2);

        $folder3 = new Folder();
        $folder3->setTitle('Videos');
        $folder3->setPath('videos');
        self::getEntityManager()->persist($folder3);
        self::getEntityManager()->flush();

        $results = $this->folderRepository->findByNamePattern('Folder');
        $this->assertCount(2, $results);

        $results = $this->folderRepository->findByNamePattern('Documents');
        $this->assertCount(1, $results);
        $this->assertEquals('Documents Folder', $results[0]->getTitle());
    }

    public function testFindByPath(): void
    {
        $folder = new Folder();
        $folder->setTitle('Unique Path Folder');
        $folder->setPath('unique-path');
        self::getEntityManager()->persist($folder);
        self::getEntityManager()->flush();

        $foundFolder = $this->folderRepository->findByPath('unique-path');
        $this->assertNotNull($foundFolder);
        $this->assertEquals('Unique Path Folder', $foundFolder->getTitle());

        $notFoundFolder = $this->folderRepository->findByPath('non-existent-path');
        $this->assertNull($notFoundFolder);
    }

    public function testFindRootFolders(): void
    {
        $root1 = new Folder();
        $root1->setTitle('Root 1');
        $root1->setPath('root1');
        $root1->setIsActive(true);
        self::getEntityManager()->persist($root1);

        $root2 = new Folder();
        $root2->setTitle('Root 2');
        $root2->setPath('root2');
        $root2->setIsActive(true);
        self::getEntityManager()->persist($root2);

        $child = new Folder();
        $child->setTitle('Child');
        $child->setPath('child');
        $child->setParent($root1);
        $child->setIsActive(true);
        self::getEntityManager()->persist($child);
        self::getEntityManager()->flush();

        $rootFolders = $this->folderRepository->findRootFolders();
        $this->assertGreaterThanOrEqual(2, count($rootFolders));

        $rootNames = array_map(fn ($folder) => $folder->getTitle(), $rootFolders);
        $this->assertContains('Root 1', $rootNames);
        $this->assertContains('Root 2', $rootNames);
        $this->assertNotContains('Child', $rootNames);
    }

    public function testFindChildrenByParent(): void
    {
        $parent = new Folder();
        $parent->setTitle('Parent Folder');
        $parent->setPath('parent');
        $parent->setIsActive(true);
        self::getEntityManager()->persist($parent);

        $child1 = new Folder();
        $child1->setTitle('Child 1');
        $child1->setPath('child1');
        $child1->setParent($parent);
        $child1->setIsActive(true);
        self::getEntityManager()->persist($child1);

        $child2 = new Folder();
        $child2->setTitle('Child 2');
        $child2->setPath('child2');
        $child2->setParent($parent);
        $child2->setIsActive(true);
        self::getEntityManager()->persist($child2);
        self::getEntityManager()->flush();

        $children = $this->folderRepository->findChildrenByParent($parent);
        $this->assertCount(2, $children);

        $childNames = array_map(fn ($folder) => $folder->getTitle(), $children);
        $this->assertContains('Child 1', $childNames);
        $this->assertContains('Child 2', $childNames);
    }

    public function testFindByUser(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');

        $userFolder = new Folder();
        $userFolder->setTitle('User Folder');
        $userFolder->setPath('user-folder');
        $userFolder->setUserIdentifier($user->getUserIdentifier());
        $userFolder->setIsActive(true);
        self::getEntityManager()->persist($userFolder);

        $anonymousFolder = new Folder();
        $anonymousFolder->setTitle('Anonymous Folder');
        $anonymousFolder->setPath('anonymous-folder');
        $anonymousFolder->setIsActive(true);
        self::getEntityManager()->persist($anonymousFolder);
        self::getEntityManager()->flush();

        $userFolders = $this->folderRepository->findByUser($user);
        $this->assertGreaterThanOrEqual(1, count($userFolders));

        $anonymousFolders = $this->folderRepository->findByUser(null);
        $this->assertGreaterThanOrEqual(1, count($anonymousFolders));
    }

    public function testFindPublicFolders(): void
    {
        $publicFolder = new Folder();
        $publicFolder->setTitle('Public Folder');
        $publicFolder->setPath('public-folder');
        $publicFolder->setIsPublic(true);
        $publicFolder->setIsActive(true);
        self::getEntityManager()->persist($publicFolder);

        $privateFolder = new Folder();
        $privateFolder->setTitle('Private Folder');
        $privateFolder->setPath('private-folder');
        $privateFolder->setIsPublic(false);
        $privateFolder->setIsActive(true);
        self::getEntityManager()->persist($privateFolder);
        self::getEntityManager()->flush();

        $publicFolders = $this->folderRepository->findPublicFolders();
        $this->assertGreaterThanOrEqual(1, count($publicFolders));

        $publicNames = array_map(fn ($folder) => $folder->getTitle(), $publicFolders);
        $this->assertContains('Public Folder', $publicNames);
        $this->assertNotContains('Private Folder', $publicNames);
    }

    public function testFindFoldersWithFiles(): void
    {
        $folderWithFiles = new Folder();
        $folderWithFiles->setTitle('Folder With Files');
        $folderWithFiles->setPath('with-files');
        self::getEntityManager()->persist($folderWithFiles);

        $emptyFolder = new Folder();
        $emptyFolder->setTitle('Empty Folder');
        $emptyFolder->setPath('empty');
        self::getEntityManager()->persist($emptyFolder);
        self::getEntityManager()->flush();

        $file = new File();
        $file->setOriginalName('test.txt');
        $file->setFileName('test.txt');
        $file->setFilePath('test.txt');
        $file->setMimeType('text/plain');
        $file->setFileSize(100);
        $file->setFolder($folderWithFiles);

        self::getEntityManager()->persist($file);
        self::getEntityManager()->flush();

        $foldersWithFiles = $this->folderRepository->findFoldersWithFiles();
        $this->assertGreaterThanOrEqual(1, count($foldersWithFiles));

        $folderNames = array_map(fn ($folder) => $folder->getTitle(), $foldersWithFiles);
        $this->assertContains('Folder With Files', $folderNames);
    }

    public function testFindEmptyFolders(): void
    {
        $emptyFolder = new Folder();
        $emptyFolder->setTitle('Empty Folder');
        $emptyFolder->setPath('empty-test');
        self::getEntityManager()->persist($emptyFolder);

        $folderWithChild = new Folder();
        $folderWithChild->setTitle('Folder With Child');
        $folderWithChild->setPath('with-child');
        self::getEntityManager()->persist($folderWithChild);

        $childFolder = new Folder();
        $childFolder->setTitle('Child');
        $childFolder->setPath('child');
        $childFolder->setParent($folderWithChild);
        self::getEntityManager()->persist($childFolder);
        self::getEntityManager()->flush();

        $emptyFolders = $this->folderRepository->findEmptyFolders();
        $this->assertGreaterThanOrEqual(1, count($emptyFolders));

        $emptyNames = array_map(fn ($folder) => $folder->getTitle(), $emptyFolders);
        $this->assertContains('Empty Folder', $emptyNames);
        $this->assertNotContains('Folder With Child', $emptyNames);
    }

    public function testFindByDateRange(): void
    {
        $folder = new Folder();
        $folder->setTitle('Date Test Folder');
        $folder->setPath('date-test');
        self::getEntityManager()->persist($folder);
        self::getEntityManager()->flush();

        $startDate = new \DateTime('-1 day');
        $endDate = new \DateTime('+1 day');

        $folders = $this->folderRepository->findByDateRange($startDate, $endDate);
        $this->assertGreaterThanOrEqual(1, count($folders));

        $folderNames = array_map(fn ($folder) => $folder->getTitle(), $folders);
        $this->assertContains('Date Test Folder', $folderNames);
    }

    public function testFindFolderTree(): void
    {
        $root = new Folder();
        $root->setTitle('Tree Root');
        $root->setPath('tree-root');
        $root->setIsActive(true);
        self::getEntityManager()->persist($root);

        $child1 = new Folder();
        $child1->setTitle('Tree Child 1');
        $child1->setPath('tree-child1');
        $child1->setParent($root);
        $child1->setIsActive(true);
        self::getEntityManager()->persist($child1);

        $child2 = new Folder();
        $child2->setTitle('Tree Child 2');
        $child2->setPath('tree-child2');
        $child2->setParent($root);
        $child2->setIsActive(true);
        self::getEntityManager()->persist($child2);
        self::getEntityManager()->flush();

        $rootFolders = $this->folderRepository->findFolderTree();
        $this->assertGreaterThanOrEqual(1, count($rootFolders));

        $children = $this->folderRepository->findFolderTree($root);
        $this->assertCount(2, $children);

        $childNames = array_map(fn ($folder) => $folder->getTitle(), $children);
        $this->assertContains('Tree Child 1', $childNames);
        $this->assertContains('Tree Child 2', $childNames);
    }

    public function testRemove(): void
    {
        $folder = new Folder();
        $folder->setTitle('To Be Removed');
        $folder->setPath('to-be-removed');
        self::getEntityManager()->persist($folder);
        self::getEntityManager()->flush();

        $folderId = $folder->getId();
        $this->assertNotNull($folderId);

        self::getEntityManager()->remove($folder);
        self::getEntityManager()->flush();

        $deletedFolder = $this->folderRepository->find($folderId);
        $this->assertNull($deletedFolder);
    }

    /**
     * @return ServiceEntityRepository<Folder>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->folderRepository;
    }

    public function testFindByTitlePattern(): void
    {
        $folder1 = new Folder();
        $folder1->setTitle('Pattern Test Folder');
        $folder1->setPath('pattern-test');
        self::getEntityManager()->persist($folder1);

        $folder2 = new Folder();
        $folder2->setTitle('Another Pattern');
        $folder2->setPath('another-pattern');
        self::getEntityManager()->persist($folder2);
        self::getEntityManager()->flush();

        $results = $this->folderRepository->findByTitlePattern('Pattern');
        $this->assertCount(2, $results);

        $results = $this->folderRepository->findByTitlePattern('Test');
        $this->assertCount(1, $results);
        $this->assertEquals('Pattern Test Folder', $results[0]->getTitle());
    }

    public function testFindByTitle(): void
    {
        $folder = new Folder();
        $folder->setTitle('Unique Title Test');
        $folder->setPath('unique-title');
        self::getEntityManager()->persist($folder);
        self::getEntityManager()->flush();

        $foundFolder = $this->folderRepository->findByTitle('Unique Title Test');
        $this->assertNotNull($foundFolder);
        $this->assertEquals('Unique Title Test', $foundFolder->getTitle());

        $notFoundFolder = $this->folderRepository->findByTitle('Non-existent Title');
        $this->assertNull($notFoundFolder);
    }

    public function testFindAllOrderByCreateTime(): void
    {
        $folder1 = new Folder();
        $folder1->setTitle('First Folder');
        $folder1->setPath('first');
        self::getEntityManager()->persist($folder1);

        $folder2 = new Folder();
        $folder2->setTitle('Second Folder');
        $folder2->setPath('second');
        self::getEntityManager()->persist($folder2);
        self::getEntityManager()->flush();

        $results = $this->folderRepository->findAllOrderByCreateTime();
        $this->assertGreaterThanOrEqual(2, count($results));

        // 验证结果是按创建时间降序排列的
        for ($i = 1; $i < count($results); ++$i) {
            $currentTime = $results[$i]->getCreateTime();
            $previousTime = $results[$i - 1]->getCreateTime();

            if (null !== $currentTime && null !== $previousTime) {
                $this->assertGreaterThanOrEqual(
                    $currentTime->getTimestamp(),
                    $previousTime->getTimestamp()
                );
            }
        }
    }

    public function testSaveMethod(): void
    {
        $folder = new Folder();
        $folder->setTitle('Save Test Folder');
        $folder->setPath('save-test');

        // 测试不立即刷新
        $this->folderRepository->save($folder, false);
        // 注意：对于自增ID，在flush之前ID可能为null

        // 手动刷新
        $this->folderRepository->flush();

        // 验证已保存（刷新后ID应该已经生成）
        $this->assertNotNull($folder->getId());
        $savedFolder = $this->folderRepository->find($folder->getId());
        $this->assertNotNull($savedFolder);
        $this->assertEquals('Save Test Folder', $savedFolder->getTitle());
    }

    public function testRemoveMethod(): void
    {
        $folder = new Folder();
        $folder->setTitle('Remove Test Folder');
        $folder->setPath('remove-test');
        self::getEntityManager()->persist($folder);
        self::getEntityManager()->flush();

        $folderId = $folder->getId();
        $this->assertNotNull($folderId);

        // 使用仓库的 remove 方法
        $this->folderRepository->remove($folder);

        // 验证已删除
        $deletedFolder = $this->folderRepository->find($folderId);
        $this->assertNull($deletedFolder);
    }

    public function testFlushMethod(): void
    {
        $folder = new Folder();
        $folder->setTitle('Flush Test Folder');
        $folder->setPath('flush-test');

        // 持久化但不刷新
        self::getEntityManager()->persist($folder);

        // 使用仓库的 flush 方法
        $this->folderRepository->flush();

        // 验证已保存到数据库
        $this->assertNotNull($folder->getId());
        $savedFolder = $this->folderRepository->find($folder->getId());
        $this->assertNotNull($savedFolder);
        $this->assertEquals('Flush Test Folder', $savedFolder->getTitle());
    }

    protected function createNewEntity(): Folder
    {
        $folder = new Folder();
        $folder->setTitle('test-folder-' . uniqid());
        $folder->setPath('test-path-' . uniqid());
        $folder->setDescription('Test description');
        $folder->setIsActive(true);

        return $folder;
    }
}
