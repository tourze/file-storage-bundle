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
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\FileStorageBundle\Service\FolderService;

final class ImageGalleryCreateFolderController extends AbstractController
{
    public function __construct(
        private readonly FolderService $folderService,
        private readonly FolderRepository $folderRepository,
    ) {
    }

    #[Route(path: '/gallery/api/folders', name: 'file_gallery_api_create_folder', methods: ['POST'])]
    public function __invoke(Request $request, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $validationResponse = $this->validateRequestData($data);
        if (null !== $validationResponse) {
            return $validationResponse;
        }

        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => '无效的请求数据格式',
            ], Response::HTTP_BAD_REQUEST);
        }

        /** @var array<string, mixed> $data */
        $folderData = $this->extractFolderData($data);

        try {
            $parent = $this->resolveParentFolder($folderData['parentId']);
            if ($parent instanceof JsonResponse) {
                return $parent;
            }

            $folder = $this->folderService->createFolder(
                $folderData['name'],
                $folderData['description'],
                $parent,
                $user,
                false // isPublic
            );

            return $this->json([
                'success' => true,
                'data' => $folder->toArray(),
                'message' => '文件夹创建成功',
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => '创建文件夹失败: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 验证请求数据
     */
    private function validateRequestData(mixed $data): ?JsonResponse
    {
        if (!is_array($data) || !isset($data['name'])) {
            return $this->json([
                'success' => false,
                'error' => '文件夹名称是必需的',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!is_string($data['name'])) {
            return $this->json([
                'success' => false,
                'error' => '文件夹名称必须为字符串',
            ], Response::HTTP_BAD_REQUEST);
        }

        $name = trim($data['name']);
        if ('' === $name) {
            return $this->json([
                'success' => false,
                'error' => '文件夹名称不能为空',
            ], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    /**
     * 提取文件夹数据
     *
     * @param array<string, mixed> $data
     * @return array{name: string, description: ?string, parentId: ?int}
     */
    private function extractFolderData(array $data): array
    {
        $name = trim(is_string($data['name']) ? $data['name'] : '');
        $description = isset($data['description']) && is_string($data['description']) ? trim($data['description']) : null;
        $parentId = isset($data['parentId']) && is_numeric($data['parentId']) ? (int) $data['parentId'] : null;

        return [
            'name' => $name,
            'description' => $description,
            'parentId' => $parentId,
        ];
    }

    /**
     * 解析父文件夹
     */
    private function resolveParentFolder(?int $parentId): Folder|JsonResponse|null
    {
        if (null === $parentId) {
            return null;
        }

        $parent = $this->folderRepository->findOneBy(['id' => $parentId, 'isActive' => true]);
        if (null === $parent) {
            return $this->json([
                'success' => false,
                'error' => '上级文件夹未找到',
            ], Response::HTTP_NOT_FOUND);
        }

        return $parent;
    }
}
