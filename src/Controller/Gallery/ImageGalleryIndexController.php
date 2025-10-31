<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Gallery;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImageGalleryIndexController extends AbstractController
{
    #[Route(path: '/gallery', name: 'file_gallery', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('@FileStorage/image_gallery.html.twig');
    }
}
