<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Exception\FileValidationException;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\FileStorageBundle\Service\FileService;

#[WithMonologChannel(channel: 'file_storage')]
final class ImageGalleryUploadFileController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly FolderRepository $folderRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/gallery/api/upload', name: 'file_gallery_api_upload', methods: ['POST'])]
    public function __invoke(Request $request, #[CurrentUser] ?UserInterface $user): JsonResponse
    {
        $uploadedFile = $request->files->get('file');
        $this->logUploadInfo($uploadedFile, $user);

        $fileValidationResponse = $this->validateUploadedFile($uploadedFile);
        if (null !== $fileValidationResponse) {
            return $fileValidationResponse;
        }

        /** @var UploadedFile $uploadedFile */
        $folderValidationResponse = $this->validateAndGetFolder($request);
        if ($folderValidationResponse instanceof JsonResponse) {
            return $folderValidationResponse;
        }

        return $this->processFileUpload($uploadedFile, $user, $request, $folderValidationResponse);
    }

    private function logUploadInfo(mixed $uploadedFile, ?UserInterface $user): void
    {
        $this->logger->info('上传文件信息', [
            'uploaded_file' => $uploadedFile,
            'original_name' => $uploadedFile instanceof UploadedFile ? $uploadedFile->getClientOriginalName() : null,
            'mime_type' => $uploadedFile instanceof UploadedFile ? $uploadedFile->getClientMimeType() : null,
            'file_extension' => $uploadedFile instanceof UploadedFile ? $uploadedFile->getClientOriginalExtension() : null,
            'size' => $uploadedFile instanceof UploadedFile ? $uploadedFile->getSize() : null,
            'error' => $uploadedFile instanceof UploadedFile ? $uploadedFile->getError() : null,
            'user' => $user?->getUserIdentifier(),
        ]);
    }

    private function validateUploadedFile(mixed $uploadedFile): ?JsonResponse
    {
        if (!$uploadedFile instanceof UploadedFile) {
            return $this->json([
                'success' => false,
                'error' => '未上传文件',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!$uploadedFile->isValid()) {
            return $this->json([
                'success' => false,
                'error' => '文件上传无效',
            ], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    private function validateAndGetFolder(Request $request): mixed
    {
        $folderId = $request->request->get('folderId') ?? $request->query->get('folderId');
        if (null === $folderId || '' === $folderId || !is_numeric($folderId)) {
            return $this->json([
                'success' => false,
                'error' => '文件夹ID是必需的',
            ], Response::HTTP_BAD_REQUEST);
        }

        $folder = $this->folderRepository->find((int) $folderId);
        if (null === $folder) {
            return $this->json([
                'success' => false,
                'error' => 'Folder not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return $folder;
    }

    private function processFileUpload(UploadedFile $uploadedFile, ?UserInterface $user, Request $request, mixed $folder): JsonResponse
    {
        try {
            $uploadType = null !== $user ? 'member' : 'anonymous';
            $this->fileService->validateFileForUpload($uploadedFile, $uploadType);
            $validFolder = $folder instanceof Folder ? $folder : null;
            $file = $this->fileService->uploadFile($uploadedFile, $user, $request, $validFolder);

            return $this->json([
                'success' => true,
                'data' => $this->buildFileResponseData($file),
                'message' => '文件上传成功',
            ], Response::HTTP_CREATED);
        } catch (FileValidationException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => '上传失败: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFileResponseData(File $file): array
    {
        return [
            'id' => $file->getId(),
            'originalName' => $file->getOriginalName(),
            'fileName' => $file->getFileName(),
            'filePath' => $file->getFilePath(),
            'publicUrl' => $file->getPublicUrl(),
            'mimeType' => $file->getType(),
            'fileSize' => $file->getFileSize(),
            'formattedSize' => $this->formatFileSize($file->getFileSize() ?? 0),
            'createTime' => $file->getCreateTime()?->format('Y-m-d H:i'),
            'isImage' => $this->isImageFile($file),
            'folder' => null !== $file->getFolder() ? [
                'id' => $file->getFolder()->getId(),
                'name' => $file->getFolder()->getName(),
            ] : null,
        ];
    }

    private function isImageFile(File $file): bool
    {
        $fileType = $file->getType();

        return str_starts_with($fileType ?? '', 'image/') || 'image' === $fileType;
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
