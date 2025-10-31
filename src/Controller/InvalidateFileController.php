<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\FileStorageBundle\Service\FileService;

final class InvalidateFileController extends AbstractController
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(path: '/file/{id}/invalidate', name: 'file_invalidate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function __invoke(int $id, Request $request): Response
    {
        $file = $this->fileService->getFile($id);

        if (null === $file) {
            throw $this->createNotFoundException('File not found');
        }

        $file->setIsActive(false);
        $this->entityManager->flush();

        $this->addFlash('success', sprintf('文件 %s 已设置为无效', $file->getOriginalName() ?? 'unknown'));

        $referer = $request->headers->get('referer');
        if (null !== $referer) {
            return $this->redirect($referer);
        }

        return $this->json(['success' => true, 'message' => 'File invalidated successfully']);
    }
}
