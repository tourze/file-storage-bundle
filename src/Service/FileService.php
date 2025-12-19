<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToGeneratePublicUrl;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Exception\FileReadException;
use Tourze\FileStorageBundle\Exception\FileValidationException;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\FileStorageBundle\Repository\FileTypeRepository;

#[WithMonologChannel(channel: 'file_storage')]
readonly class FileService
{
    public function __construct(
        private FileRepository $fileRepository,
        private FileTypeRepository $fileTypeRepository,
        private SluggerInterface $slugger,
        private FilesystemOperator $filesystem,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function uploadFile(UploadedFile $uploadedFile, ?UserInterface $user, ?Request $request = null, ?Folder $folder = null): File
    {
        $fileInfo = $this->generateFileInfo($uploadedFile);
        $content = $this->processFileContent($uploadedFile);

        $file = $this->createFileEntity($uploadedFile, $fileInfo, $content, $user, $folder);
        $this->setImageDimensions($file, $uploadedFile);
        $this->setFileUrls($file, $fileInfo['relativePath'], $request);

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        return $file;
    }

    /**
     * @return array<string, string>
     */
    private function generateFileInfo(UploadedFile $uploadedFile): array
    {
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $uploadedFile->guessExtension() ?? 'bin';
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        $dateDirectory = date('Y/m');
        $relativePath = $dateDirectory . '/' . $newFilename;

        return [
            'newFilename' => $newFilename,
            'extension' => $extension,
            'relativePath' => $relativePath,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function processFileContent(UploadedFile $uploadedFile): array
    {
        try {
            $content = file_get_contents($uploadedFile->getPathname());
            if (false === $content) {
                throw new FileReadException('Failed to read uploaded file');
            }

            $md5Hash = md5($content);
            $sha1Hash = sha1($content);

            return [
                'content' => $content,
                'md5Hash' => $md5Hash,
                'sha1Hash' => $sha1Hash,
            ];
        } catch (\Exception $e) {
            throw new UnableToWriteFile('Failed to process file: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, string> $fileInfo
     * @param array<string, string> $content
     */
    private function createFileEntity(UploadedFile $uploadedFile, array $fileInfo, array $content, ?UserInterface $user, ?Folder $folder): File
    {
        try {
            $this->filesystem->write($fileInfo['relativePath'], $content['content']);
        } catch (\Exception $e) {
            throw new UnableToWriteFile('Failed to upload file: ' . $e->getMessage(), 0, $e);
        }

        $file = new File();
        $mimeType = '' !== $uploadedFile->getClientMimeType() ? $uploadedFile->getClientMimeType() : 'application/octet-stream';

        $file->setOriginalName($uploadedFile->getClientOriginalName());
        $file->setFileName($fileInfo['newFilename']);
        $file->setFilePath($fileInfo['relativePath']);
        $file->setMimeType($mimeType);
        $file->setFileSize($uploadedFile->getSize());
        $file->setMd5Hash($content['md5Hash']);
        $file->setSha1Hash($content['sha1Hash']);
        $file->setExt($fileInfo['extension']);
        $file->setCreatedBy($user?->getUserIdentifier());
        $file->setFolder($folder);
        $file->setYear((int) date('Y'));
        $file->setMonth((int) date('n'));

        return $file;
    }

    private function setImageDimensions(File $file, UploadedFile $uploadedFile): void
    {
        if (!str_starts_with($file->getMimeType() ?? '', 'image/')) {
            return;
        }

        try {
            $imageInfo = @getimagesize($uploadedFile->getPathname());
            if (false !== $imageInfo) {
                $file->setWidth($imageInfo[0]);
                $file->setHeight($imageInfo[1]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to get image dimensions', [
                'filename' => $uploadedFile->getClientOriginalName(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function setFileUrls(File $file, string $relativePath, ?Request $request): void
    {
        $defaultUrl = '/files/' . $relativePath;
        $file->setUrl($defaultUrl);

        $publicUrl = $this->generatePublicUrl($file, $defaultUrl, $request);
        $file->setPublicUrl($publicUrl ?? $defaultUrl);
    }

    private function generatePublicUrl(File $file, string $defaultUrl, ?Request $request): ?string
    {
        $publicUrl = $this->tryFilesystemPublicUrl($file);

        if (null === $publicUrl && null !== $request) {
            $publicUrl = $this->generateRequestBasedUrl($request, $defaultUrl, $file);
        }

        return $publicUrl;
    }

    private function tryFilesystemPublicUrl(File $file): ?string
    {
        try {
            $publicUrl = $this->filesystem->publicUrl($file->getFilePath());
            $this->logger->debug('Generated public URL via filesystem', [
                'filePath' => $file->getFilePath(),
                'publicUrl' => $publicUrl,
            ]);

            return $publicUrl;
        } catch (\Error $e) {
            $this->logger->debug('Filesystem does not support publicUrl method', [
                'error' => $e->getMessage(),
            ]);
        } catch (UnableToGeneratePublicUrl $e) {
            $this->logger->debug('Filesystem publicUrl failed', [
                'filePath' => $file->getFilePath(),
                'exception' => $e->getMessage(),
            ]);
        }

        return null;
    }

    private function generateRequestBasedUrl(Request $request, string $defaultUrl, File $file): string
    {
        $scheme = $request->getScheme();
        $host = $request->getHttpHost();
        $publicUrl = sprintf('%s://%s%s', $scheme, $host, $defaultUrl);

        $this->logger->debug('Generated public URL via Request host', [
            'filePath' => $file->getFilePath(),
            'publicUrl' => $publicUrl,
        ]);

        return $publicUrl;
    }

    public function getFile(string $id): ?File
    {
        return $this->fileRepository->find($id);
    }

    public function getFileRepository(): FileRepository
    {
        return $this->fileRepository;
    }

    public function getFileTypeRepository(): FileTypeRepository
    {
        return $this->fileTypeRepository;
    }

    public function getActiveFile(string $id): ?File
    {
        $file = $this->fileRepository->find($id);

        if (null !== $file && $file->isValid()) {
            return $file;
        }

        return null;
    }

    public function deleteFile(File $file, bool $removePhysicalFile = false): void
    {
        if ($removePhysicalFile) {
            try {
                $this->filesystem->delete($file->getFilePath());
            } catch (UnableToDeleteFile) {
                // File might already be deleted, continue
            }
        }

        $file->setValid(false);
        $this->entityManager->persist($file);
        $this->entityManager->flush();
    }

    public function getFileContent(File $file): string
    {
        try {
            return $this->filesystem->read($file->getFilePath());
        } catch (UnableToReadFile $e) {
            throw new FileReadException('Unable to read file: ' . $e->getMessage(), 0, $e);
        }
    }

    public function fileExists(File $file): bool
    {
        return $this->filesystem->fileExists($file->getFilePath());
    }

    /**
     * 根据 MD5 哈希值查找重复文件
     *
     * @return File[]
     */
    public function findDuplicatesByMd5(string $md5Hash): array
    {
        $file = $this->fileRepository->findByMd5Hash($md5Hash);

        return null !== $file ? [$file] : [];
    }

    /**
     * 获取文件统计信息
     *
     * @return array<string, mixed>
     */
    public function getFileStats(): array
    {
        $totalSize = $this->fileRepository->getTotalActiveFilesSize();
        $totalCount = count($this->fileRepository->findBy(['valid' => true]));

        return [
            'total_files' => $totalCount,
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'average_size' => $totalCount > 0 ? $totalSize / $totalCount : 0,
            'average_size_formatted' => $totalCount > 0 ? $this->formatBytes($totalSize / $totalCount) : '0 B',
        ];
    }

    /**
     * 清理早于指定时间的匿名文件
     */
    public function cleanupAnonymousFiles(\DateTimeInterface $olderThan): int
    {
        $files = $this->fileRepository->findAnonymousFilesOlderThan($olderThan);
        $deletedCount = 0;

        foreach ($files as $file) {
            // Delete physical file
            try {
                $this->filesystem->delete($file->getFilePath());
            } catch (UnableToDeleteFile) {
                // File might already be deleted, continue
            }

            // Remove from database
            $this->entityManager->remove($file);
            ++$deletedCount;
        }

        // Flush all deletions at once
        if ($deletedCount > 0) {
            $this->entityManager->flush();
        }

        return $deletedCount;
    }

    /**
     * 验证文件是否可以上传
     *
     * @throws FileValidationException
     */
    public function validateFileForUpload(UploadedFile $file, string $uploadType): void
    {
        $this->logger->info('文件上传', [
            'mimeType' => $file->getClientMimeType(),
        ]);
        $mimeType = '' !== $file->getClientMimeType() ? $file->getClientMimeType() : 'application/octet-stream';
        // 优先使用客户端文件名的扩展名，而不是基于内容猜测的扩展名
        $clientExtension = strtolower((string) pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

        // Check if file type is allowed by MIME type first
        $fileType = $this->fileTypeRepository->findByMimeType($mimeType, $uploadType);

        // Only fallback to extension if MIME type lookup failed AND client provided an extension
        if (null === $fileType && '' !== $clientExtension) {
            $fileType = $this->fileTypeRepository->findByExtension($clientExtension, $uploadType);
        }

        if (null === $fileType) {
            throw new FileValidationException(sprintf('File type not allowed for %s upload: %s', $uploadType, $mimeType));
        }

        // Check file size
        if ($file->getSize() > $fileType->getMaxSize()) {
            throw new FileValidationException(sprintf('File size exceeds maximum allowed size of %s', $this->formatBytes($fileType->getMaxSize())));
        }
    }

    /**
     * 获取指定上传类型允许的 MIME 类型
     *
     * @return string[]
     */
    public function getAllowedMimeTypes(string $uploadType): array
    {
        return $this->fileTypeRepository->getActiveMimeTypes($uploadType);
    }

    private function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes > 0 ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[(int) $pow];
    }
}
