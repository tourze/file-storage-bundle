<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Tourze\FileStorageBundle\Service\FileService;

final class ImageGalleryDeleteFileController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    #[Route(path: '/gallery/api/files/{id}', name: 'file_gallery_api_delete', methods: ['DELETE'], requirements: ['id' => '-?\d+'])]
    public function __invoke(int $id, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $file = $this->fileService->getFile($id);

        if (null === $file) {
            return $this->json([
                'success' => false,
                'error' => '文件未找到',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->fileService->deleteFile($file, true);

            return $this->json([
                'success' => true,
                'message' => '文件删除成功',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => '删除文件失败: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
