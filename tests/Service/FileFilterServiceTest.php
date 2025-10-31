<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Service\FileFilterService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(FileFilterService::class)]
#[RunTestsInSeparateProcesses]
final class FileFilterServiceTest extends AbstractIntegrationTestCase
{
    private FileFilterService $service;

    protected function onSetUp(): void
    {
        $this->service = self::getService(FileFilterService::class);
    }

    public function testServiceIsAvailable(): void
    {
        $service = self::getService(FileFilterService::class);
        $this->assertInstanceOf(FileFilterService::class, $service);
    }

    public function testApplyFiltersWithEmptyArray(): void
    {
        $files = [];
        $filters = ['year' => null, 'month' => null, 'filename' => null, 'folder' => 'all'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertEmpty($result);
    }

    public function testApplyFiltersWithAllFilter(): void
    {
        $file1 = new File();
        $file1->setFileName('test1.jpg');
        $file1->setType('image/jpeg');

        $file2 = new File();
        $file2->setFileName('test2.pdf');
        $file2->setType('application/pdf');

        $files = [$file1, $file2];
        $filters = ['year' => null, 'month' => null, 'filename' => null, 'folder' => 'all'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(2, $result);
    }

    public function testApplyFiltersWithYearFilter(): void
    {
        $file1 = new File();
        $file1->setFileName('test1.jpg');
        $file1->setCreateTime(new \DateTimeImmutable('2023-06-15'));

        $file2 = new File();
        $file2->setFileName('test2.jpg');
        $file2->setCreateTime(new \DateTimeImmutable('2024-06-15'));

        $files = [$file1, $file2];
        $filters = ['year' => '2023', 'month' => null, 'filename' => null, 'folder' => 'all'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertSame($file1, $result[0]);
    }

    public function testApplyFiltersWithMonthFilter(): void
    {
        $file1 = new File();
        $file1->setFileName('test1.jpg');
        $file1->setCreateTime(new \DateTimeImmutable('2023-06-15'));

        $file2 = new File();
        $file2->setFileName('test2.jpg');
        $file2->setCreateTime(new \DateTimeImmutable('2023-07-15'));

        $files = [$file1, $file2];
        $filters = ['year' => null, 'month' => '06', 'filename' => null, 'folder' => 'all'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertSame($file1, $result[0]);
    }

    public function testApplyFiltersWithFilenameFilter(): void
    {
        $file1 = new File();
        $file1->setFileName('avatar.jpg');

        $file2 = new File();
        $file2->setFileName('document.pdf');

        $files = [$file1, $file2];
        $filters = ['year' => null, 'month' => null, 'filename' => 'avatar', 'folder' => 'all'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertSame($file1, $result[0]);
    }

    public function testApplyFiltersWithCaseInsensitiveFilenameFilter(): void
    {
        $file1 = new File();
        $file1->setFileName('Avatar.JPG');

        $file2 = new File();
        $file2->setFileName('document.pdf');

        $files = [$file1, $file2];
        $filters = ['year' => null, 'month' => null, 'filename' => 'avatar', 'folder' => 'all'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertSame($file1, $result[0]);
    }

    public function testApplyFiltersWithImagesFolder(): void
    {
        $file1 = new File();
        $file1->setFileName('test1.jpg');
        $file1->setType('image/jpeg');

        $file2 = new File();
        $file2->setFileName('test2.pdf');
        $file2->setType('application/pdf');

        $files = [$file1, $file2];
        $filters = ['year' => null, 'month' => null, 'filename' => null, 'folder' => 'images'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertSame($file1, $result[0]);
    }

    public function testApplyFiltersWithDocumentsFolder(): void
    {
        $file1 = new File();
        $file1->setFileName('test1.jpg');
        $file1->setType('image/jpeg');

        $file2 = new File();
        $file2->setFileName('test2.pdf');
        $file2->setType('application/pdf');

        $files = [$file1, $file2];
        $filters = ['year' => null, 'month' => null, 'filename' => null, 'folder' => 'documents'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertContains($file2, $result);
    }

    public function testApplyFiltersWithRecentFolder(): void
    {
        $recentFile = new File();
        $recentFile->setFileName('recent.jpg');
        $recentFile->setCreateTime(new \DateTimeImmutable('-3 days'));

        $oldFile = new File();
        $oldFile->setFileName('old.jpg');
        $oldFile->setCreateTime(new \DateTimeImmutable('-10 days'));

        $files = [$recentFile, $oldFile];
        $filters = ['year' => null, 'month' => null, 'filename' => null, 'folder' => 'recent'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertSame($recentFile, $result[0]);
    }

    public function testApplyFiltersWithMultipleFilters(): void
    {
        $file1 = new File();
        $file1->setFileName('avatar.jpg');
        $file1->setType('image/jpeg');
        $file1->setCreateTime(new \DateTimeImmutable('2023-06-15'));

        $file2 = new File();
        $file2->setFileName('avatar.pdf');
        $file2->setType('application/pdf');
        $file2->setCreateTime(new \DateTimeImmutable('2023-06-15'));

        $file3 = new File();
        $file3->setFileName('document.jpg');
        $file3->setType('image/jpeg');
        $file3->setCreateTime(new \DateTimeImmutable('2023-06-15'));

        $files = [$file1, $file2, $file3];
        $filters = ['year' => '2023', 'month' => '06', 'filename' => 'avatar', 'folder' => 'images'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertSame($file1, $result[0]);
    }

    public function testApplyFiltersWithNullCreateTime(): void
    {
        $file1 = new File();
        $file1->setFileName('test.jpg');
        // createTime is null

        $files = [$file1];
        $filters = ['year' => '2023', 'month' => null, 'filename' => null, 'folder' => 'all'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertEmpty($result);
    }

    public function testApplyFiltersWithNullFileName(): void
    {
        $file1 = new File();
        // fileName is null

        $files = [$file1];
        $filters = ['year' => null, 'month' => null, 'filename' => 'test', 'folder' => 'all'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertEmpty($result);
    }

    public function testApplyFiltersWithImageTypeAsString(): void
    {
        $file1 = new File();
        $file1->setFileName('test1.jpg');
        $file1->setType('image'); // Simple string type

        $file2 = new File();
        $file2->setFileName('test2.pdf');
        $file2->setType('application/pdf');

        $files = [$file1, $file2];
        $filters = ['year' => null, 'month' => null, 'filename' => null, 'folder' => 'images'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result);
        $this->assertSame($file1, $result[0]);
    }

    public function testApplyFiltersWithNullFileType(): void
    {
        $file1 = new File();
        $file1->setFileName('test1.jpg');
        // type is null

        $files = [$file1];
        $filters = ['year' => null, 'month' => null, 'filename' => null, 'folder' => 'images'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertEmpty($result);
    }

    public function testApplyFiltersWithUnknownFolder(): void
    {
        $file1 = new File();
        $file1->setFileName('test1.jpg');
        $file1->setType('image/jpeg');

        $files = [$file1];
        $filters = ['year' => null, 'month' => null, 'filename' => null, 'folder' => 'unknown'];

        $result = $this->service->applyFilters($files, $filters);

        $this->assertCount(1, $result); // Unknown folder defaults to true
    }
}
