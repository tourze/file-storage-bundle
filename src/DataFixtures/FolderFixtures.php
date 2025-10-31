<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\FileStorageBundle\Entity\Folder;

class FolderFixtures extends Fixture implements FixtureGroupInterface
{
    public const FOLDER_DOCUMENTS = 'folder-documents';
    public const FOLDER_IMAGES = 'folder-images';
    public const FOLDER_VIDEOS = 'folder-videos';
    public const FOLDER_AUDIO = 'folder-audio';
    public const FOLDER_ARCHIVE = 'folder-archive';
    public const FOLDER_DESIGN = 'folder-design';
    public const FOLDER_SOFTWARE = 'folder-software';
    public const FOLDER_TEMP = 'folder-temp';

    public function load(ObjectManager $manager): void
    {
        $documentsFolder = new Folder();
        $documentsFolder->setTitle('文档资料');
        $manager->persist($documentsFolder);
        $this->addReference(self::FOLDER_DOCUMENTS, $documentsFolder);

        $imagesFolder = new Folder();
        $imagesFolder->setTitle('图片素材');
        $manager->persist($imagesFolder);
        $this->addReference(self::FOLDER_IMAGES, $imagesFolder);

        $videosFolder = new Folder();
        $videosFolder->setTitle('视频素材');
        $manager->persist($videosFolder);
        $this->addReference(self::FOLDER_VIDEOS, $videosFolder);

        $audioFolder = new Folder();
        $audioFolder->setTitle('音频素材');
        $manager->persist($audioFolder);
        $this->addReference(self::FOLDER_AUDIO, $audioFolder);

        $archiveFolder = new Folder();
        $archiveFolder->setTitle('压缩包');
        $manager->persist($archiveFolder);
        $this->addReference(self::FOLDER_ARCHIVE, $archiveFolder);

        $designFolder = new Folder();
        $designFolder->setTitle('设计文件');
        $manager->persist($designFolder);
        $this->addReference(self::FOLDER_DESIGN, $designFolder);

        $softwareFolder = new Folder();
        $softwareFolder->setTitle('软件工具');
        $manager->persist($softwareFolder);
        $this->addReference(self::FOLDER_SOFTWARE, $softwareFolder);

        $tempFolder = new Folder();
        $tempFolder->setTitle('临时文件');
        $manager->persist($tempFolder);
        $this->addReference(self::FOLDER_TEMP, $tempFolder);

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'file-storage',
        ];
    }
}
