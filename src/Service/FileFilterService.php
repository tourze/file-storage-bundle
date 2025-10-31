<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Service;

use Tourze\FileStorageBundle\Entity\File;

final class FileFilterService
{
    /**
     * @param File[] $files
     * @param array{year: string|null, month: string|null, filename: string|null, folder: string} $filters
     * @return File[]
     */
    public function applyFilters(array $files, array $filters): array
    {
        return array_filter($files, fn (File $file) => $this->fileMatchesAllFilters($file, $filters));
    }

    /**
     * @param array{year: string|null, month: string|null, filename: string|null, folder: string} $filters
     */
    private function fileMatchesAllFilters(File $file, array $filters): bool
    {
        return $this->matchesDateFilter($file, $filters['year'], $filters['month'])
            && $this->matchesFilenameFilter($file, $filters['filename'])
            && $this->matchesTypeFilter($file, $filters['folder']);
    }

    private function matchesDateFilter(File $file, ?string $year, ?string $month): bool
    {
        $createTime = $file->getCreateTime();

        if (null !== $year && $createTime?->format('Y') !== $year) {
            return false;
        }

        if (null !== $month && $createTime?->format('m') !== $month) {
            return false;
        }

        return true;
    }

    private function matchesFilenameFilter(File $file, ?string $filename): bool
    {
        if (null === $filename) {
            return true;
        }

        return false !== stripos($file->getFileName() ?? '', $filename);
    }

    private function matchesTypeFilter(File $file, string $folder): bool
    {
        return match ($folder) {
            'all' => true,
            'images' => $this->isImageFile($file),
            'documents' => !$this->isImageFile($file) && !$this->isVideoFile($file),
            'videos' => $this->isVideoFile($file),
            'recent' => $this->isRecentFile($file),
            default => true,
        };
    }

    private function isImageFile(File $file): bool
    {
        $fileType = $file->getType();

        return str_starts_with($fileType ?? '', 'image/') || 'image' === $fileType;
    }

    private function isVideoFile(File $file): bool
    {
        $fileType = $file->getType();

        return str_starts_with($fileType ?? '', 'video/') || 'video' === $fileType;
    }

    private function isRecentFile(File $file): bool
    {
        $createTime = $file->getCreateTime();
        if (null === $createTime) {
            return false;
        }

        $weekAgo = new \DateTime('-7 days');

        return $createTime >= $weekAgo;
    }
}
