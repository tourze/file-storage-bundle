<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\FileStorageBundle\Service\FileFilterService;
use Tourze\FileStorageBundle\Service\FileService;

final class ImageGalleryGetFilesController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly FolderRepository $folderRepository,
        private readonly FileFilterService $fileFilterService,
    ) {
    }

    #[Route(path: '/gallery/api/files', name: 'file_gallery_api_files', methods: ['GET'])]
    public function __invoke(Request $request, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $filters = $this->parseFilters($request);
        $pagination = $this->parsePagination($request);

        // 构建基础查询条件
        $criteria = ['valid' => true];
        $orderBy = ['createTime' => 'DESC'];

        // 处理文件夹筛选
        $folderFilterResult = $this->applyFolderFilter($criteria, $filters['folder'], $pagination);
        if (null !== $folderFilterResult['early_return']) {
            return $folderFilterResult['early_return'];
        }
        $criteria = $folderFilterResult['criteria'];

        // 获取所有符合条件的文件
        $repository = $this->fileService->getFileRepository();
        $allFiles = $repository->findBy($criteria, $orderBy);

        // 应用筛选条件
        $filteredFiles = $this->fileFilterService->applyFilters($allFiles, $filters);

        // 分页处理
        $paginatedFiles = $this->applyPagination($filteredFiles, $pagination);

        // 按年月分组并格式化数据
        $groupedFiles = $this->groupFilesByMonth($paginatedFiles['files']);

        return $this->json([
            'success' => true,
            'data' => $groupedFiles,
            'pagination' => $paginatedFiles['pagination'],
        ]);
    }

    /**
     * @return array{year: string|null, month: string|null, filename: string|null, folder: string}
     */
    private function parseFilters(Request $request): array
    {
        $year = $request->query->get('year');
        $month = $request->query->get('month');
        $filename = $request->query->get('filename');
        $folder = $request->query->get('folder', 'all');

        return [
            'year' => is_string($year) ? $year : null,
            'month' => is_string($month) ? $month : null,
            'filename' => is_string($filename) ? $filename : null,
            'folder' => is_string($folder) ? $folder : 'all',
        ];
    }

    /**
     * @return array{page: int, limit: int}
     */
    private function parsePagination(Request $request): array
    {
        $pageParam = $request->query->get('page', 1);
        $limitParam = $request->query->get('limit', 17);

        $page = is_numeric($pageParam) ? (int) $pageParam : 1;
        $limit = is_numeric($limitParam) ? (int) $limitParam : 17;

        return [
            'page' => max(1, $page), // Ensure page is at least 1
            'limit' => max(1, $limit), // Ensure limit is at least 1
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array{page: int, limit: int} $pagination
     * @return array{criteria: array<string, mixed>, early_return: JsonResponse|null}
     */
    private function applyFolderFilter(array $criteria, string $folder, array $pagination): array
    {
        if (!is_numeric($folder)) {
            return ['criteria' => $criteria, 'early_return' => null];
        }

        $folderId = (int) $folder;
        $folderEntity = $this->folderRepository->find($folderId);

        if (null !== $folderEntity) {
            $criteria['folder'] = $folderEntity;

            return ['criteria' => $criteria, 'early_return' => null];
        }

        // 文件夹不存在，返回空结果
        return ['criteria' => $criteria, 'early_return' => $this->json([
            'success' => true,
            'data' => [],
            'pagination' => [
                'current_page' => $pagination['page'],
                'total' => 0,
                'per_page' => $pagination['limit'],
                'total_pages' => 0,
            ],
        ])];
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

    /**
     * @param File[] $files
     * @param array{page: int, limit: int} $pagination
     * @return array{files: File[], pagination: array{current_page: int, total: int, per_page: int, total_pages: int|float}}
     */
    private function applyPagination(array $files, array $pagination): array
    {
        $total = count($files);
        $offset = ($pagination['page'] - 1) * $pagination['limit'];
        $paginatedFiles = array_slice($files, $offset, $pagination['limit']);

        return [
            'files' => $paginatedFiles,
            'pagination' => [
                'current_page' => $pagination['page'],
                'total' => $total,
                'per_page' => $pagination['limit'],
                'total_pages' => ceil($total / $pagination['limit']),
            ],
        ];
    }

    /**
     * @param File[] $files
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupFilesByMonth(array $files): array
    {
        $groupedFiles = [];

        foreach ($files as $file) {
            assert($file instanceof File);

            $createTime = $file->getCreateTime();
            $yearMonth = null !== $createTime ? $createTime->format('Y年n月') : '未知时间';

            if (!isset($groupedFiles[$yearMonth])) {
                $groupedFiles[$yearMonth] = [];
            }

            $groupedFiles[$yearMonth][] = $this->formatFileData($file);
        }

        return $groupedFiles;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFileData(File $file): array
    {
        $createTime = $file->getCreateTime();

        return [
            'id' => $file->getId(),
            'originalName' => $file->getFileName(),
            'fileName' => $file->getFileName(),
            'filePath' => $file->getFilePath(),
            'publicUrl' => $file->getPublicUrl(),
            'mimeType' => $file->getType(),
            'fileSize' => $file->getSize(),
            'formattedSize' => $this->formatFileSize($file->getSize() ?? 0),
            'createTime' => null !== $createTime ? $createTime->format('Y-m-d H:i') : '',
            'isImage' => $this->isImageFile($file),
            'isVideo' => $this->isVideoFile($file),
            'folder' => null !== $file->getFolder() ? [
                'id' => $file->getFolder()->getId(),
                'name' => $file->getFolder()->getName(),
            ] : null,
        ];
    }

    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        if (0 === $bytes) {
            return '0 B';
        }

        $i = floor(log($bytes) / log(1024));
        $size = $bytes / pow(1024, $i);

        return round($size, 2) . ' ' . $units[(int) $i];
    }
}
