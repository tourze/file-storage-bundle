<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\FileStorageBundle\Service\FileService;

final class GetAllowedTypesAnonymousController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    #[Route(path: '/allowed-types/anonymous', name: 'file_allowed_types_anonymous', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $fileTypes = $this->fileService->getFileTypeRepository()->findActiveForAnonymous();
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
