<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\FileStorageBundle\Service\FileService;

final class GetAllowedTypesMemberController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    #[Route(path: '/allowed-types/member', name: 'file_allowed_types_member', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $fileTypes = $this->fileService->getFileTypeRepository()->findActiveForMember();
        $types = [];

        foreach ($fileTypes as $fileType) {
            $types[] = [
                'name' => $fileType->getName(),
                'mimeType' => $fileType->getMimeType(),
                'extension' => $fileType->getExtension(),
                'maxSize' => $fileType->getMaxSize(),
            ];
        }

        return $this->json([
            'allowedTypes' => $types,
        ]);
    }
}
