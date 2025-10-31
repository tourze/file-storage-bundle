<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\FileStorageBundle\Service\FolderService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(FolderService::class)]
#[RunTestsInSeparateProcesses]
final class FolderServiceTest extends AbstractIntegrationTestCase
{
    private FolderService $folderService;

    private FolderRepository $folderRepository;

    protected function onSetUp(): void
    {
        $this->folderService = self::getService(FolderService::class);
        $this->folderRepository = self::getService(FolderRepository::class);
    }

    public function testCreateFolder(): void
    {
        $folder = $this->folderService->createFolder('Test Folder', 'Test Description');

        $this->assertNotNull($folder->getId());
        $this->assertEquals('Test Folder', $folder->getName());
        $this->assertEquals('Test Description', $folder->getDescription());
        $this->assertEquals('test-folder', $folder->getPath());
        $this->assertTrue($folder->isActive());
        $this->assertFalse($folder->isPublic());
        $this->assertNull($folder->getParent());
        $this->assertTrue($folder->isAnonymous());
    }

    public function testCreateFolderWithParent(): void
    {
        $parent = $this->folderService->createFolder('Parent Folder');
        $child = $this->folderService->createFolder('Child Folder', null, $parent);

        $this->assertEquals($parent, $child->getParent());
        $this->assertEquals('parent-folder/child-folder', $child->getPath());
        $this->assertFalse($child->isRoot());
        $this->assertTrue($parent->hasChildren());
    }

    public function testCreateFolderWithUser(): void
    {
        $user = $this->createNormalUser('test@example.com', 'password123');
        $folder = $this->folderService->createFolder('User Folder', null, null, $user);

        $this->assertEquals($user, $folder->getUser());
        $this->assertFalse($folder->isAnonymous());
    }

    public function testCreatePublicFolder(): void
    {
        $folder = $this->folderService->createFolder('Public Folder', null, null, null, true);

        $this->assertTrue($folder->isPublic());
    }

    public function testUpdateFolder(): void
    {
        $folder = $this->folderService->createFolder('Original Name', 'Original Description');
        $originalPath = $folder->getPath();

        $updatedFolder = $this->folderService->updateFolder(
            $folder,
            'Updated Name',
            'Updated Description',
            true,
            false
        );

        $this->assertEquals('Updated Name', $updatedFolder->getName());
        $this->assertEquals('Updated Description', $updatedFolder->getDescription());
        $this->assertTrue($updatedFolder->isPublic());
        $this->assertFalse($updatedFolder->isActive());
        $this->assertNotEquals($originalPath, $updatedFolder->getPath());
        $this->assertEquals('updated-name', $updatedFolder->getPath());
    }

    public function testUpdateFolderPartial(): void
    {
        $folder = $this->folderService->createFolder('Test Folder', 'Test Description', null, null, false);

        $this->folderService->updateFolder($folder, null, null, true, null);

        $this->assertEquals('Test Folder', $folder->getName());
        $this->assertEquals('Test Description', $folder->getDescription());
        $this->assertTrue($folder->isPublic());
        $this->assertTrue($folder->isActive());
    }

    public function testMoveFolder(): void
    {
        $oldParent = $this->folderService->createFolder('Old Parent');
        $newParent = $this->folderService->createFolder('New Parent');
        $folder = $this->folderService->createFolder('Movable Folder', null, $oldParent);

        $originalPath = $folder->getPath();
        $movedFolder = $this->folderService->moveFolder($folder, $newParent);

        $this->assertEquals($newParent, $movedFolder->getParent());
        $this->assertEquals('new-parent/movable-folder', $movedFolder->getPath());
        $this->assertNotEquals($originalPath, $movedFolder->getPath());
    }

    public function testMoveFolderToRoot(): void
    {
        $parent = $this->folderService->createFolder('Parent');
        $folder = $this->folderService->createFolder('Child Folder', null, $parent);

        $movedFolder = $this->folderService->moveFolder($folder, null);

        $this->assertNull($movedFolder->getParent());
        $this->assertTrue($movedFolder->isRoot());
        $this->assertEquals('child-folder', $movedFolder->getPath());
    }

    public function testDeleteFolder(): void
    {
        $folder = $this->folderService->createFolder('To Be Deleted');
        $this->assertTrue($folder->isActive());

        $this->folderService->deleteFolder($folder);

        $this->assertFalse($folder->isActive());
    }

    public function testPermanentDeleteFolder(): void
    {
        $folder = $this->folderService->createFolder('To Be Permanently Deleted');
        $folderId = $folder->getId();

        $this->folderService->permanentDeleteFolder($folder);

        $deletedFolder = $this->folderRepository->find($folderId);
        $this->assertNull($deletedFolder);
    }

    public function testGetFolderTreeEmpty(): void
    {
        $tree = $this->folderService->getFolderTree();
        $this->assertIsArray($tree);
    }

    public function testGetFolderTreeWithFolders(): void
    {
        $parent = $this->folderService->createFolder('Tree Parent');
        $child1 = $this->folderService->createFolder('Tree Child 1', null, $parent);
        $child2 = $this->folderService->createFolder('Tree Child 2', null, $parent);

        $tree = $this->folderService->getFolderTree($parent);

        $this->assertCount(2, $tree);
        $this->assertEquals('Tree Child 1', $tree[0]['name']);
        $this->assertEquals('Tree Child 2', $tree[1]['name']);
    }

    public function testGetFolderTreeWithFiles(): void
    {
        $folder = $this->folderService->createFolder('Folder With Files');

        $file = new File();
        $file->setOriginalName('test.txt');
        $file->setFileName('test.txt');
        $file->setFilePath('test.txt');
        $file->setMimeType('text/plain');
        $file->setFileSize(100);

        // 正确设置文件与文件夹的双向关系
        $folder->addFile($file);

        self::getEntityManager()->persist($file);
        self::getEntityManager()->flush();

        $tree = $this->folderService->getFolderTree($folder, true);

        $this->assertEmpty($tree);

        $tree = $this->folderService->getFolderTree(null, true);
        $folderData = null;

        foreach ($tree as $item) {
            if ('Folder With Files' === $item['name']) {
                $folderData = $item;
                break;
            }
        }

        $this->assertNotNull($folderData);
        $this->assertIsArray($folderData);
        $this->assertArrayHasKey('files', $folderData);
        $this->assertIsArray($folderData['files']);
        $this->assertCount(1, $folderData['files']);
        $this->assertIsArray($folderData['files'][0]);
        $this->assertArrayHasKey('originalName', $folderData['files'][0]);
        $this->assertEquals('test.txt', $folderData['files'][0]['originalName']);
    }

    public function testFindOrCreatePathNew(): void
    {
        $folder = $this->folderService->findOrCreatePath('new/path/structure');

        $this->assertEquals('structure', $folder->getName());
        $this->assertEquals('new/path/structure', $folder->getPath());
        $this->assertNotNull($folder->getParent());
        $this->assertEquals('path', $folder->getParent()->getName());
        $this->assertEquals('new/path', $folder->getParent()->getPath());
    }

    public function testFindOrCreatePathExisting(): void
    {
        $existingFolder = $this->folderService->createFolder('Existing Path');
        $existingFolder->setPath('existing/path');
        self::getEntityManager()->persist($existingFolder);
        self::getEntityManager()->flush();

        $foundFolder = $this->folderService->findOrCreatePath('existing/path');

        $this->assertEquals($existingFolder->getId(), $foundFolder->getId());
        $this->assertEquals('Existing Path', $foundFolder->getName());
    }

    public function testGetFolderStats(): void
    {
        $folder = $this->folderService->createFolder('Stats Test Folder');

        $file1 = new File();
        $file1->setOriginalName('file1.txt');
        $file1->setFileName('file1.txt');
        $file1->setFilePath('file1.txt');
        $file1->setMimeType('text/plain');
        $file1->setFileSize(100);

        $file2 = new File();
        $file2->setOriginalName('file2.txt');
        $file2->setFileName('file2.txt');
        $file2->setFilePath('file2.txt');
        $file2->setMimeType('text/plain');
        $file2->setFileSize(200);

        // 正确设置文件与文件夹的双向关系
        $folder->addFile($file1);
        $folder->addFile($file2);

        self::getEntityManager()->persist($file1);
        self::getEntityManager()->persist($file2);
        self::getEntityManager()->flush();

        $stats = $this->folderService->getFolderStats($folder);

        $this->assertEquals($folder->getId(), $stats['id']);
        $this->assertEquals('Stats Test Folder', $stats['name']);
        $this->assertEquals(2, $stats['fileCount']);
        $this->assertEquals(300, $stats['totalSize']);
        $this->assertEquals(0, $stats['childrenCount']);
        $this->assertTrue($stats['isRoot']);
        $this->assertFalse($stats['isPublic']);
    }

    public function testIsEmpty(): void
    {
        $emptyFolder = $this->folderService->createFolder('Empty Folder');
        $this->assertTrue($this->folderService->isEmpty($emptyFolder));

        $folderWithChild = $this->folderService->createFolder('Folder With Child');
        $child = $this->folderService->createFolder('Child', null, $folderWithChild);
        $this->assertFalse($this->folderService->isEmpty($folderWithChild));

        $folderWithFile = $this->folderService->createFolder('Folder With File');
        $file = new File();
        $file->setOriginalName('test.txt');
        $file->setFileName('test.txt');
        $file->setFilePath('test.txt');
        $file->setMimeType('text/plain');
        $file->setFileSize(100);

        // 正确设置文件与文件夹的双向关系
        $folderWithFile->addFile($file);

        self::getEntityManager()->persist($file);
        self::getEntityManager()->flush();

        $this->assertFalse($this->folderService->isEmpty($folderWithFile));
    }
}
