<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\FileStorageBundle\Service\FolderService;

final class ImageGalleryDeleteFolderController extends AbstractController
{
    public function __construct(
        private readonly FolderService $folderService,
        private readonly FolderRepository $folderRepository,
    ) {
    }

    #[Route(path: '/gallery/api/folders/{id}', name: 'file_gallery_api_delete_folder', methods: ['DELETE'], requirements: ['id' => '-?\d+'])]
    public function __invoke(string $id, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        try {
            $folder = $this->folderRepository->find($id);

            if (null === $folder) {
                return $this->json([
                    'success' => false,
                    'error' => '文件夹未找到',
                ], Response::HTTP_NOT_FOUND);
            }

            // 检查是否为空文件夹
            if (!$this->folderService->isEmpty($folder)) {
                return $this->json([
                    'success' => false,
                    'error' => '文件夹还有文件，不允许删除',
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->folderService->deleteFolder($folder);

            return $this->json([
                'success' => true,
                'message' => '文件夹删除成功',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => '删除文件夹失败: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
