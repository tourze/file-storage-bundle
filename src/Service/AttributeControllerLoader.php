<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Routing\RouteCollection;
use Tourze\FileStorageBundle\Controller\DownloadFileController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryCreateFolderController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryDeleteFileController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryDeleteFolderController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryGetAllowedTypesController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryGetFilesController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryGetFoldersController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryGetStatsController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryIndexController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryUpdateFolderController;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryUploadFileController;
use Tourze\FileStorageBundle\Controller\GetAllowedTypesAnonymousController;
use Tourze\FileStorageBundle\Controller\GetAllowedTypesMemberController;
use Tourze\FileStorageBundle\Controller\InvalidateFileController;
use Tourze\FileStorageBundle\Controller\UploadAnonymousController;
use Tourze\FileStorageBundle\Controller\UploadMemberController;
use Tourze\FileStorageBundle\Controller\ValidateFileController;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

#[AutoconfigureTag(name: 'routing.loader')]
class AttributeControllerLoader extends Loader implements RoutingAutoLoaderInterface
{
    private AttributeRouteControllerLoader $controllerLoader;

    public function __construct()
    {
        parent::__construct();
        $this->controllerLoader = new AttributeRouteControllerLoader();
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        // 如果资源是特定的控制器类，只加载该控制器的路由
        if (is_string($resource) && class_exists($resource)) {
            return $this->controllerLoader->load($resource);
        }

        return $this->autoload();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        // 只支持 annotation 和 attribute 类型，或者无类型
        if (null !== $type && !in_array($type, ['annotation', 'attribute'], true)) {
            return false;
        }

        // 支持字符串类型的控制器类名
        if (is_string($resource) && class_exists($resource)) {
            // 检查是否是控制器类
            $reflection = new \ReflectionClass($resource);

            return $reflection->isSubclassOf(AbstractController::class);
        }

        return false;
    }

    public function autoload(): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->addCollection($this->controllerLoader->load(GetAllowedTypesAnonymousController::class));
        $collection->addCollection($this->controllerLoader->load(GetAllowedTypesMemberController::class));
        $collection->addCollection($this->controllerLoader->load(UploadAnonymousController::class));
        $collection->addCollection($this->controllerLoader->load(UploadMemberController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryIndexController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryGetFilesController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryDeleteFileController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryGetStatsController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryGetAllowedTypesController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryGetFoldersController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryCreateFolderController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryUpdateFolderController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryDeleteFolderController::class));
        $collection->addCollection($this->controllerLoader->load(ImageGalleryUploadFileController::class));
        $collection->addCollection($this->controllerLoader->load(DownloadFileController::class));
        $collection->addCollection($this->controllerLoader->load(InvalidateFileController::class));
        $collection->addCollection($this->controllerLoader->load(ValidateFileController::class));

        return $collection;
    }
}
