<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\FileType;
use Tourze\FileStorageBundle\Entity\Folder;

/**
 * 文件存储管理菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('文件管理')) {
            $item->addChild('文件管理');
        }

        $fileMenu = $item->getChild('文件管理');

        if (null === $fileMenu) {
            return;
        }

        // 文件列表菜单
        $fileMenu->addChild('文件列表')
            ->setUri($this->linkGenerator->getCurdListPage(File::class))
            ->setAttribute('icon', 'fas fa-file')
        ;

        // 文件类型菜单
        $fileMenu->addChild('文件类型')
            ->setUri($this->linkGenerator->getCurdListPage(FileType::class))
            ->setAttribute('icon', 'fas fa-file-alt')
        ;

        // 文件目录菜单
        $fileMenu->addChild('文件目录')
            ->setUri($this->linkGenerator->getCurdListPage(Folder::class))
            ->setAttribute('icon', 'fas fa-folder')
        ;
    }
}
