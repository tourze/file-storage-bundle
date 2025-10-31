<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Exception\FolderCreationException;
use Tourze\FileStorageBundle\Repository\FolderRepository;

#[WithMonologChannel(channel: 'file_storage')]
readonly class FolderService
{
    public function __construct(
        private FolderRepository $folderRepository,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * 创建新文件夹
     */
    public function createFolder(
        string $name,
        ?string $description = null,
        ?Folder $parent = null,
        ?UserInterface $user = null,
        bool $isPublic = false,
    ): Folder {
        $path = $this->generatePath($name, $parent);

        $folder = new Folder();
        $folder->setName($name);
        $folder->setDescription($description);
        $folder->setPath($path);
        $folder->setCreatedBy($user?->getUserIdentifier());
        $folder->setUser($user);
        $folder->setIsPublic($isPublic);

        // 正确设置父子关系
        if (null !== $parent) {
            $parent->addChild($folder);
        }

        $this->folderRepository->save($folder);

        $this->logger->info('文件夹已创建', [
            'folder_id' => $folder->getId(),
            'name' => $name,
            'path' => $path,
            'parent_id' => $parent?->getId(),
            'user_id' => $user?->getUserIdentifier(),
        ]);

        return $folder;
    }

    /**
     * 更新文件夹信息
     */
    public function updateFolder(
        Folder $folder,
        ?string $name = null,
        ?string $description = null,
        ?bool $isPublic = null,
        ?bool $isActive = null,
    ): Folder {
        $oldName = $folder->getName();

        if (null !== $name && $name !== $folder->getName()) {
            $folder->setName($name);
            $newPath = $this->generatePath($name, $folder->getParent());
            $folder->setPath($newPath);
        }

        if (null !== $description) {
            $folder->setDescription($description);
        }

        if (null !== $isPublic) {
            $folder->setIsPublic($isPublic);
        }

        if (null !== $isActive) {
            $folder->setIsActive($isActive);
        }

        $this->folderRepository->save($folder);

        $this->logger->info('文件夹已更新', [
            'folder_id' => $folder->getId(),
            'old_name' => $oldName,
            'new_name' => $folder->getName(),
            'path' => $folder->getPath(),
        ]);

        return $folder;
    }

    /**
     * 移动文件夹到新的父文件夹
     */
    public function moveFolder(Folder $folder, ?Folder $newParent): Folder
    {
        $oldParent = $folder->getParent();
        $oldPath = $folder->getPath();

        $folder->setParent($newParent);
        $newPath = $this->generatePath($folder->getName(), $newParent);
        $folder->setPath($newPath);

        $this->folderRepository->save($folder);

        $this->logger->info('文件夹已移动', [
            'folder_id' => $folder->getId(),
            'old_parent_id' => $oldParent?->getId(),
            'new_parent_id' => $newParent?->getId(),
            'old_path' => $oldPath,
            'new_path' => $newPath,
        ]);

        return $folder;
    }

    /**
     * 删除文件夹（软删除）
     */
    public function deleteFolder(Folder $folder): void
    {
        $folderId = $folder->getId();
        $name = $folder->getName();
        $path = $folder->getPath();

        // 执行软删除，设置为非活动状态
        $folder->setIsActive(false);
        $this->folderRepository->save($folder);

        $this->logger->info('文件夹已删除（软删除）', [
            'folder_id' => $folderId,
            'name' => $name,
            'path' => $path,
        ]);
    }

    /**
     * 永久删除文件夹
     */
    public function permanentDeleteFolder(Folder $folder): void
    {
        $folderId = $folder->getId();
        $name = $folder->getName();

        $this->folderRepository->remove($folder);

        $this->logger->info('文件夹已永久删除', [
            'folder_id' => $folderId,
            'name' => $name,
        ]);
    }

    /**
     * 获取文件夹树结构
     *
     * @return list<array<string, mixed>>
     */
    public function getFolderTree(?Folder $parent = null, bool $includeFiles = false): array
    {
        $folders = $this->folderRepository->findFolderTree($parent);
        $tree = [];

        foreach ($folders as $folder) {
            $folderData = $folder->toArray();

            $folderData = $this->addChildrenToFolderData($folderData, $folder, $includeFiles);

            if ($includeFiles) {
                $folderData = $this->addFilesToFolderData($folderData, $folder);
            }

            $tree[] = $folderData;
        }

        return $tree;
    }

    /**
     * @param array<string, mixed> $folderData
     * @return array<string, mixed>
     */
    private function addChildrenToFolderData(array $folderData, Folder $folder, bool $includeFiles): array
    {
        $children = $this->getFolderTree($folder, $includeFiles);
        if ([] !== $children) {
            $folderData['children'] = $children;
        }

        return $folderData;
    }

    /**
     * @param array<string, mixed> $folderData
     * @return array<string, mixed>
     */
    private function addFilesToFolderData(array $folderData, Folder $folder): array
    {
        if (!$folder->hasFiles()) {
            return $folderData;
        }

        $folderData['files'] = [];
        foreach ($folder->getFiles() as $file) {
            if ($file->isActive()) {
                $folderData['files'][] = $file->toArray();
            }
        }

        return $folderData;
    }

    /**
     * 查找或创建文件夹路径
     */
    public function findOrCreatePath(string $path, ?UserInterface $user = null): Folder
    {
        // 检查是否已存在
        $existingFolder = $this->folderRepository->findByPath($path);
        if (null !== $existingFolder) {
            return $existingFolder;
        }

        // 分解路径
        $pathParts = array_filter(explode('/', trim($path, '/')), static fn ($v) => '' !== $v);
        $parent = null;
        $currentPath = '';

        foreach ($pathParts as $part) {
            $currentPath = ('' !== $currentPath) ? $currentPath . '/' . $part : $part;

            $existingFolder = $this->folderRepository->findByPath($currentPath);
            if (null !== $existingFolder) {
                $parent = $existingFolder;
            } else {
                $parent = $this->createFolder($part, null, $parent, $user);
            }
        }

        if (null === $parent) {
            throw new FolderCreationException('Unable to create folder path: ' . $path);
        }

        return $parent;
    }

    /**
     * 获取文件夹统计信息
     *
     * @return array<string, mixed>
     */
    public function getFolderStats(Folder $folder): array
    {
        $stats = [
            'id' => $folder->getId(),
            'name' => $folder->getName(),
            'path' => $folder->getPath(),
            'fullPath' => $folder->getFullPath(),
            'fileCount' => 0,
            'totalSize' => 0,
            'childrenCount' => $folder->getChildren()->count(),
            'isRoot' => $folder->isRoot(),
            'isPublic' => $folder->isPublic(),
        ];

        // 统计文件数量和大小
        foreach ($folder->getFiles() as $file) {
            if ($file->isActive()) {
                ++$stats['fileCount'];
                $stats['totalSize'] += $file->getFileSize() ?? 0;
            }
        }

        return $stats;
    }

    /**
     * 检查文件夹是否为空
     */
    public function isEmpty(Folder $folder): bool
    {
        // 检查是否有有效文件
        $hasValidFiles = false;
        foreach ($folder->getFiles() as $file) {
            if ($file->isValid()) {
                $hasValidFiles = true;
                break;
            }
        }

        return !$hasValidFiles && !$folder->hasChildren();
    }

    /**
     * 生成文件夹路径
     */
    private function generatePath(string $name, ?Folder $parent): string
    {
        $sluggedName = $this->slugger->slug($name)->lower();

        if (null === $parent) {
            return $sluggedName->toString();
        }

        return $parent->getPath() . '/' . $sluggedName->toString();
    }
}
