<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\FileStorageBundle\Entity\FileType;

#[When(env: 'test')]
#[When(env: 'dev')]
class FileTypeFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $fileTypes = [
            // Images
            ['name' => 'JPEG Image', 'mimeType' => 'image/jpeg', 'extension' => 'jpg', 'maxSize' => 10 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'PNG Image', 'mimeType' => 'image/png', 'extension' => 'png', 'maxSize' => 10 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'GIF Image', 'mimeType' => 'image/gif', 'extension' => 'gif', 'maxSize' => 5 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'WebP Image', 'mimeType' => 'image/webp', 'extension' => 'webp', 'maxSize' => 10 * 1024 * 1024, 'uploadType' => 'both'],

            // Documents
            ['name' => 'PDF Document', 'mimeType' => 'application/pdf', 'extension' => 'pdf', 'maxSize' => 20 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'Word Document', 'mimeType' => 'application/msword', 'extension' => 'doc', 'maxSize' => 15 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'Word Document (OOXML)', 'mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'extension' => 'docx', 'maxSize' => 15 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'Excel Spreadsheet', 'mimeType' => 'application/vnd.ms-excel', 'extension' => 'xls', 'maxSize' => 15 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'Excel Spreadsheet (OOXML)', 'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'extension' => 'xlsx', 'maxSize' => 15 * 1024 * 1024, 'uploadType' => 'both'],

            // Text
            ['name' => 'Plain Text', 'mimeType' => 'text/plain', 'extension' => 'txt', 'maxSize' => 5 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'CSV File', 'mimeType' => 'text/csv', 'extension' => 'csv', 'maxSize' => 10 * 1024 * 1024, 'uploadType' => 'both'],

            // Archives (Member only)
            ['name' => 'ZIP Archive', 'mimeType' => 'application/zip', 'extension' => 'zip', 'maxSize' => 50 * 1024 * 1024, 'uploadType' => 'member'],
            ['name' => 'RAR Archive', 'mimeType' => 'application/x-rar-compressed', 'extension' => 'rar', 'maxSize' => 50 * 1024 * 1024, 'uploadType' => 'member'],
            ['name' => '7-Zip Archive', 'mimeType' => 'application/x-7z-compressed', 'extension' => '7z', 'maxSize' => 50 * 1024 * 1024, 'uploadType' => 'member'],

            // Video
            ['name' => 'MP4 Video', 'mimeType' => 'video/mp4', 'extension' => 'mp4', 'maxSize' => 100 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'AVI Video', 'mimeType' => 'video/x-msvideo', 'extension' => 'avi', 'maxSize' => 100 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'WebM Video', 'mimeType' => 'video/webm', 'extension' => 'webm', 'maxSize' => 100 * 1024 * 1024, 'uploadType' => 'both'],
            ['name' => 'MOV Video', 'mimeType' => 'video/quicktime', 'extension' => 'mov', 'maxSize' => 100 * 1024 * 1024, 'uploadType' => 'both'],

            // Audio (Member only)
            ['name' => 'MP3 Audio', 'mimeType' => 'audio/mpeg', 'extension' => 'mp3', 'maxSize' => 20 * 1024 * 1024, 'uploadType' => 'member'],
            ['name' => 'WAV Audio', 'mimeType' => 'audio/wav', 'extension' => 'wav', 'maxSize' => 50 * 1024 * 1024, 'uploadType' => 'member'],
        ];

        foreach ($fileTypes as $data) {
            $fileType = new FileType();
            $fileType->setName($data['name']);
            $fileType->setMimeType($data['mimeType']);
            $fileType->setExtension($data['extension']);
            $fileType->setMaxSize($data['maxSize']);
            $fileType->setUploadType($data['uploadType']);
            $fileType->setIsActive(true);

            $manager->persist($fileType);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return [
            'file-storage',
        ];
    }
}
