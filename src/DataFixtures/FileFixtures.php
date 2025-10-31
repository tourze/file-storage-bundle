<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;

#[When(env: 'test')]
#[When(env: 'dev')]
class FileFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        // 图片素材文件 - 使用真实可访问的图片
        $imageFiles = [
            [
                'url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1920&h=1080',
                'fileName' => 'landscape.jpg',
                'size' => 2 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 1920,
                'height' => 1080,
                'fileKey' => 'images/landscape-001.jpg',
                'md5File' => md5('landscape.jpg'),
                'exif' => ['camera' => 'Canon EOS R5', 'iso' => 100, 'f_number' => 2.8],
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=2560&h=1440',
                'fileName' => 'city-night.jpg',
                'size' => 3 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 2560,
                'height' => 1440,
                'fileKey' => 'images/city-night-002.jpg',
                'md5File' => md5('city-night.jpg'),
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=1024&h=1024',
                'fileName' => 'abstract-art.jpg',
                'size' => 512 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 1024,
                'height' => 1024,
                'fileKey' => 'images/abstract-art-003.jpg',
                'md5File' => md5('abstract-art.jpg'),
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=1600&h=1200',
                'fileName' => 'forest-path.jpg',
                'size' => 1.8 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 1600,
                'height' => 1200,
                'fileKey' => 'images/forest-path-004.jpg',
                'md5File' => md5('forest-path.jpg'),
                'exif' => ['camera' => 'Nikon D850', 'iso' => 200, 'f_number' => 4.0],
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1447752875215-b2761acb3c5d?w=2048&h=1365',
                'fileName' => 'ocean-sunset.jpg',
                'size' => 2.5 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 2048,
                'height' => 1365,
                'fileKey' => 'images/ocean-sunset-005.jpg',
                'md5File' => md5('ocean-sunset.jpg'),
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=2400&h=1800',
                'fileName' => 'mountain-peak.jpg',
                'size' => 3.2 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 2400,
                'height' => 1800,
                'fileKey' => 'images/mountain-peak-006.jpg',
                'md5File' => md5('mountain-peak.jpg'),
                'exif' => ['camera' => 'Sony A7R IV', 'iso' => 64, 'f_number' => 8.0],
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1433086966358-54859d0ed716?w=1200&h=1800',
                'fileName' => 'flower-macro.jpg',
                'size' => 1.2 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 1200,
                'height' => 1800,
                'fileKey' => 'images/flower-macro-007.jpg',
                'md5File' => md5('flower-macro.jpg'),
                'exif' => ['camera' => 'Canon EOS R6', 'iso' => 400, 'f_number' => 2.8],
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1426604966848-d7adac402bff?w=1440&h=960',
                'fileName' => 'architecture-modern.jpg',
                'size' => 800 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 1440,
                'height' => 960,
                'fileKey' => 'images/architecture-modern-008.jpg',
                'md5File' => md5('architecture-modern.jpg'),
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1920&h=1280',
                'fileName' => 'street-art.jpg',
                'size' => 2.1 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 1920,
                'height' => 1280,
                'fileKey' => 'images/street-art-009.jpg',
                'md5File' => md5('street-art.jpg'),
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1465146344425-f00d5f5c8f07?w=1600&h=1067',
                'fileName' => 'coffee-beans.jpg',
                'size' => 1.5 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 1600,
                'height' => 1067,
                'fileKey' => 'images/coffee-beans-010.jpg',
                'md5File' => md5('coffee-beans.jpg'),
                'exif' => ['camera' => 'Fujifilm X-T4', 'iso' => 320, 'f_number' => 3.5],
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1508193638397-1c4234db14d8?w=3840&h=2160',
                'fileName' => 'galaxy-stars.jpg',
                'size' => 4.2 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 3840,
                'height' => 2160,
                'fileKey' => 'images/galaxy-stars-011.jpg',
                'md5File' => md5('galaxy-stars.jpg'),
                'exif' => ['camera' => 'Canon EOS Ra', 'iso' => 1600, 'f_number' => 1.4],
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?w=2000&h=1333',
                'fileName' => 'vintage-car.jpg',
                'size' => 2.8 * 1024 * 1024,
                'type' => 'image',
                'ext' => 'jpg',
                'width' => 2000,
                'height' => 1333,
                'fileKey' => 'images/vintage-car-012.jpg',
                'md5File' => md5('vintage-car.jpg'),
                'folder' => FolderFixtures::FOLDER_IMAGES,
            ],
        ];

        // 音频素材文件
        $audioFiles = [
            [
                'url' => 'https://www.soundjay.com/misc/sounds/bell-ringing-05.mp3',
                'fileName' => 'background-music.mp3',
                'size' => 5 * 1024 * 1024,
                'type' => 'audio',
                'ext' => 'mp3',
                'fileKey' => 'audio/background-music.mp3',
                'md5File' => md5('background-music.mp3'),
                'folder' => FolderFixtures::FOLDER_AUDIO,
            ],
        ];

        // 文档资料文件
        $documentFiles = [
            [
                'url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'fileName' => 'project-proposal.pdf',
                'size' => 1024 * 1024,
                'type' => 'document',
                'ext' => 'pdf',
                'fileKey' => 'documents/project-proposal.pdf',
                'md5File' => md5('project-proposal.pdf'),
                'folder' => FolderFixtures::FOLDER_DOCUMENTS,
            ],
            [
                'url' => 'https://file-examples.com/storage/fe86f21776fc66c6331f154/2017/10/file_example_DOCX_10KB.docx',
                'fileName' => 'meeting-notes.docx',
                'size' => 512 * 1024,
                'type' => 'document',
                'ext' => 'docx',
                'fileKey' => 'documents/meeting-notes.docx',
                'md5File' => md5('meeting-notes.docx'),
                'folder' => FolderFixtures::FOLDER_DOCUMENTS,
            ],
        ];

        // 压缩包文件
        $archiveFiles = [
            [
                'url' => 'https://file-examples.com/storage/fe86f21776fc66c6331f154/2017/10/file_example_ZIP_500KB.zip',
                'fileName' => 'project-backup.zip',
                'size' => 100 * 1024 * 1024,
                'type' => 'archive',
                'ext' => 'zip',
                'fileKey' => 'archives/project-backup.zip',
                'md5File' => md5('project-backup.zip'),
                'folder' => FolderFixtures::FOLDER_ARCHIVE,
            ],
        ];

        // 设计文件
        $designFiles = [
            [
                'url' => 'https://file-examples.com/storage/fe86f21776fc66c6331f154/2017/10/file_example_PSD_1MB.psd',
                'fileName' => 'logo-design.psd',
                'size' => 20 * 1024 * 1024,
                'type' => 'design',
                'ext' => 'psd',
                'width' => 2048,
                'height' => 2048,
                'fileKey' => 'design/logo-design.psd',
                'md5File' => md5('logo-design.psd'),
                'folder' => FolderFixtures::FOLDER_DESIGN,
            ],
        ];

        // 软件工具文件
        $softwareFiles = [
            [
                'url' => 'https://file-examples.com/storage/fe86f21776fc66c6331f154/2017/10/file_example_EXE_1MB.exe',
                'fileName' => 'app-installer.exe',
                'size' => 50 * 1024 * 1024,
                'type' => 'software',
                'ext' => 'exe',
                'fileKey' => 'software/app-installer.exe',
                'md5File' => md5('app-installer.exe'),
                'folder' => FolderFixtures::FOLDER_SOFTWARE,
            ],
        ];

        // 临时文件
        $tempFiles = [
            [
                'url' => 'https://file-examples.com/storage/fe86f21776fc66c6331f154/2017/10/file_example_TXT_10KB.txt',
                'fileName' => 'cache-file.tmp',
                'size' => 64 * 1024,
                'type' => 'temp',
                'ext' => 'tmp',
                'fileKey' => 'temp/cache-file.tmp',
                'md5File' => md5('cache-file.tmp'),
                'folder' => FolderFixtures::FOLDER_TEMP,
            ],
        ];

        // 未分类文件
        $uncategorizedFiles = [
            [
                'url' => 'https://file-examples.com/storage/fe86f21776fc66c6331f154/2017/10/file_example_TXT_1KB.txt',
                'fileName' => 'anonymous-upload.txt',
                'size' => 1024,
                'type' => 'text',
                'ext' => 'txt',
                'fileKey' => 'uploads/anonymous-upload.txt',
                'md5File' => md5('anonymous-upload.txt'),
                'folder' => null,
            ],
        ];

        // 合并所有文件
        $allFiles = array_merge(
            $imageFiles,
            $audioFiles,
            $documentFiles,
            $archiveFiles,
            $designFiles,
            $softwareFiles,
            $tempFiles,
            $uncategorizedFiles
        );

        foreach ($allFiles as $index => $data) {
            $file = new File();
            $file->setId(strval(10000 + $index + 1)); // 设置从10001开始的ID
            $file->setUrl($data['url']);
            $file->setFileName($data['fileName']);
            $file->setSize((int) $data['size']);
            $file->setType($data['type']);
            $file->setExt($data['ext']);
            $file->setFileKey($data['fileKey']);
            $file->setMd5File($data['md5File']);
            $file->setYear($currentYear);
            $file->setMonth($currentMonth);
            $file->setValid(true);

            // 设置图片尺寸
            if (isset($data['width'], $data['height'])) {
                $file->setWidth($data['width']);
                $file->setHeight($data['height']);
            }

            // 设置EXIF信息
            if (isset($data['exif'])) {
                $file->setExif($data['exif']);
            }

            // 模拟访问和下载次数
            $file->setViewCount(rand(10, 500));
            $file->setDownloadCount(rand(5, 100));

            // 设置文件夹关联
            if (null !== $data['folder']) {
                $folder = $this->getReference($data['folder'], Folder::class);
                $file->setFolder($folder);
            }

            $manager->persist($file);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'file-storage',
        ];
    }

    public function getDependencies(): array
    {
        return [
            FolderFixtures::class,
        ];
    }
}
