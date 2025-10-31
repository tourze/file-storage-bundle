<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\FileStorageBundle\Service\FileService;

final class ImageGalleryGetAllowedTypesController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    #[Route(path: '/gallery/api/allowed-types', name: 'file_gallery_api_allowed_types', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $memberTypes = $this->fileService->getAllowedMimeTypes('member');
        $anonymousTypes = $this->fileService->getAllowedMimeTypes('anonymous');

        return $this->json([
            'success' => true,
            'data' => [
                'member' => $memberTypes,
                'anonymous' => $anonymousTypes,
            ],
        ]);
    }
}
