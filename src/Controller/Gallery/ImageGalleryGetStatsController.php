<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Tourze\FileStorageBundle\Service\FileService;

final class ImageGalleryGetStatsController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    #[Route(path: '/gallery/api/stats', name: 'file_gallery_api_stats', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $repository = $this->fileService->getFileRepository();

        // 构建查询条件
        $criteria = ['valid' => true];
        // 注意：不根据用户过滤文件，显示所有有效文件的统计信息
        // 与getFiles方法保持一致

        $allFiles = $repository->findBy($criteria);

        $stats = [
            'total_files' => count($allFiles),
            'total_size' => 0,
            'image_count' => 0,
            'document_count' => 0,
            'recent_count' => 0,
        ];

        $weekAgo = new \DateTimeImmutable('-7 days');

        foreach ($allFiles as $file) {
            $stats['total_size'] += $file->getSize() ?? 0;

            $fileType = $file->getType();
            if (null !== $fileType && ('image' === $fileType || str_starts_with($fileType, 'image/'))) {
                ++$stats['image_count'];
            } else {
                ++$stats['document_count'];
            }

            $createTime = $file->getCreateTime();
            if (null !== $createTime && $createTime >= $weekAgo) {
                ++$stats['recent_count'];
            }
        }

        $stats['total_size_formatted'] = $this->formatFileSize($stats['total_size']);

        return $this->json([
            'success' => true,
            'data' => $stats,
        ]);
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
