<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\FileStorageBundle\Service\FileService;

final class DownloadFileController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
    ) {
    }

    #[Route(path: '/file/{id}/download', name: 'file_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id, Request $request): Response
    {
        $file = $this->fileService->getFile($id);

        if (null === $file) {
            throw $this->createNotFoundException('File not found');
        }

        try {
            $content = $this->fileService->getFileContent($file);

            $response = new Response($content);
            $response->headers->set('Content-Type', 'application/octet-stream');
            $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $file->getOriginalName() ?? 'download'));
            $response->headers->set('Content-Length', (string) $file->getFileSize());

            return $response;
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('下载文件失败：%s', $e->getMessage()));

            $referer = $request->headers->get('referer');
            if (null !== $referer) {
                return $this->redirect($referer);
            }

            throw $this->createNotFoundException('Unable to download file');
        }
    }
}
