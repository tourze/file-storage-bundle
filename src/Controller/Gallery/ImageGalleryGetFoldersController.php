<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Tourze\FileStorageBundle\Service\FolderService;

final class ImageGalleryGetFoldersController extends AbstractController
{
    public function __construct(
        private readonly FolderService $folderService,
    ) {
    }

    #[Route(path: '/gallery/api/folders', name: 'file_gallery_api_folders', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?UserInterface $user): JsonResponse
    {
        try {
            $folderTree = $this->folderService->getFolderTree(null, false);

            return $this->json([
                'success' => true,
                'data' => $folderTree,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => '加载文件夹失败: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
