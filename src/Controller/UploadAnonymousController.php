<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\FileStorageBundle\Exception\FileValidationException;
use Tourze\FileStorageBundle\Service\FileService;

final class UploadAnonymousController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    #[Route(path: '/upload/anonymous', name: 'file_upload_anonymous', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $uploadedFile = $request->files->get('file');

        if (null === $uploadedFile) {
            return $this->json([
                'error' => 'No file was uploaded',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$uploadedFile instanceof UploadedFile) {
            return $this->json([
                'error' => 'Invalid file upload',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Validate file using database-driven file types
            $this->fileService->validateFileForUpload($uploadedFile, 'anonymous');

            // 匿名上传，userId 为 null，传递 Request 用于生成完整 URL
            $file = $this->fileService->uploadFile($uploadedFile, null, $request);

            return $this->json([
                'success' => true,
                'file' => $file->toArray(),
            ], Response::HTTP_CREATED);
        } catch (FileValidationException $e) {
            return $this->json([
                'error' => 'File validation failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (FileException $e) {
            return $this->json([
                'error' => 'File upload failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
