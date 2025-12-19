<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\FileStorageBundle\Service\FolderService;

final class ImageGalleryUpdateFolderController extends AbstractController
{
    public function __construct(
        private readonly FolderService $folderService,
        private readonly FolderRepository $folderRepository,
    ) {
    }

    #[Route(path: '/gallery/api/folders/{id}', name: 'file_gallery_api_update_folder', methods: ['PUT'], requirements: ['id' => '-?\d+'])]
    public function __invoke(string $id, Request $request, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['name'])) {
            return $this->json([
                'success' => false,
                'error' => '文件夹名称是必需的',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $folder = $this->folderRepository->find($id);

            if (null === $folder) {
                return $this->json([
                    'success' => false,
                    'error' => '文件夹未找到',
                ], Response::HTTP_NOT_FOUND);
            }

            if (!is_string($data['name'])) {
                return $this->json([
                    'success' => false,
                    'error' => '文件夹名称必须为字符串',
                ], Response::HTTP_BAD_REQUEST);
            }

            $name = trim($data['name']);
            $description = isset($data['description']) && is_string($data['description']) ? trim($data['description']) : null;

            if ('' === $name) {
                return $this->json([
                    'success' => false,
                    'error' => '文件夹名称不能为空',
                ], Response::HTTP_BAD_REQUEST);
            }

            $updatedFolder = $this->folderService->updateFolder(
                $folder,
                $name,
                $description
            );

            return $this->json([
                'success' => true,
                'data' => $updatedFolder->toArray(),
                'message' => '文件夹更新成功',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => '更新文件夹失败: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
