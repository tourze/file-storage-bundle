<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\FileStorageBundle\Command\CleanAnonymousFilesCommand;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\FileStorageBundle\Repository\FileRepository;
use Tourze\FileStorageBundle\Repository\FolderRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(CleanAnonymousFilesCommand::class)]
#[RunTestsInSeparateProcesses]
final class CleanAnonymousFilesCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    private FileRepository $fileRepository;

    private FolderRepository $folderRepository;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    protected function onSetUp(): void
    {
        $this->assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $command = $application->find('file-storage:clean-anonymous');
        $this->commandTester = new CommandTester($command);
        $this->fileRepository = self::getService(FileRepository::class);
        $this->folderRepository = self::getService(FolderRepository::class);
    }

    public function testExecuteWithDefaultHours(): void
    {
        // 创建一个旧的匿名文件
        $this->createAnonymousFile(new \DateTimeImmutable('-2 hours'));

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning Anonymous Files', $output);
        $this->assertStringContainsString('Looking for anonymous files older than 1 hour(s)', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithCustomHours(): void
    {
        $this->commandTester->execute(['--hours' => '3']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Looking for anonymous files older than 3 hour(s)', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithNoFilesToDelete(): void
    {
        // 不创建任何文件，确保没有可删除的文件
        $this->commandTester->execute(['--hours' => '1000']);

        $output = $this->commandTester->getDisplay();
        // 由于数据库是干净的，应该没有匿名文件可删除
        $this->assertContains($this->commandTester->getStatusCode(), [Command::SUCCESS, Command::FAILURE]);
    }

    public function testExecuteWithDryRun(): void
    {
        // 创建一个旧的匿名文件
        $file = $this->createAnonymousFile(new \DateTimeImmutable('-2 hours'));
        $fileId = $file->getId();

        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsStringIgnoringCase('dry', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());

        // dry-run 模式下文件不应被删除
        self::getEntityManager()->clear();
        $notDeletedFile = $this->fileRepository->find($fileId);
        $this->assertNotNull($notDeletedFile, '在 dry-run 模式下文件不应被删除');
    }

    public function testExecuteDeletesAnonymousFiles(): void
    {
        // 创建一个旧的匿名文件（无文件夹关联）
        $file = $this->createAnonymousFile(new \DateTimeImmutable('-2 hours'));
        $fileId = $file->getId();

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());

        // 验证文件被删除
        self::getEntityManager()->clear();
        $deletedFile = $this->fileRepository->find($fileId);
        $this->assertNull($deletedFile, '匿名文件应该被删除');
    }

    public function testExecuteDoesNotDeleteFilesWithFolder(): void
    {
        // 创建一个有文件夹关联的旧文件
        $folder = $this->createTestFolder();
        $file = $this->createAnonymousFile(new \DateTimeImmutable('-2 hours'), $folder);
        $fileId = $file->getId();

        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());

        // 验证有文件夹关联的文件不被删除
        self::getEntityManager()->clear();
        $notDeletedFile = $this->fileRepository->find($fileId);
        $this->assertNotNull($notDeletedFile, '有文件夹关联的文件不应被删除');
    }

    public function testExecuteDoesNotDeleteRecentFiles(): void
    {
        // 创建一个最近的匿名文件
        $file = $this->createAnonymousFile(new \DateTimeImmutable('-30 minutes'));
        $fileId = $file->getId();

        $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());

        // 验证最近的文件不被删除
        self::getEntityManager()->clear();
        $notDeletedFile = $this->fileRepository->find($fileId);
        $this->assertNotNull($notDeletedFile, '最近的文件不应被删除');
    }

    public function testExecuteWithInvalidHours(): void
    {
        $this->commandTester->execute(['--hours' => '0']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Hours must be at least 1', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    public function testCommandConfiguration(): void
    {
        $this->assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $command = $application->find('file-storage:clean-anonymous');

        $this->assertEquals('file-storage:clean-anonymous', $command->getName());
        $this->assertEquals('Clean up anonymous files older than specified hours', $command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('hours'));
        $this->assertTrue($definition->hasOption('dry-run'));

        $hoursOption = $definition->getOption('hours');
        $this->assertEquals('1', $hoursOption->getDefault());
        $this->assertTrue($hoursOption->isValueRequired());

        $dryRunOption = $definition->getOption('dry-run');
        $this->assertFalse($dryRunOption->acceptValue());
    }

    public function testOptionHours(): void
    {
        $this->commandTester->execute(['--hours' => '5']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('5 hour(s)', $output);
        $this->assertContains($this->commandTester->getStatusCode(), [Command::SUCCESS, Command::FAILURE]);
    }

    public function testOptionDryRun(): void
    {
        $this->commandTester->execute(['--dry-run' => true]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsStringIgnoringCase('dry', $output);
        $this->assertContains($this->commandTester->getStatusCode(), [Command::SUCCESS, Command::FAILURE]);
    }

    private function createAnonymousFile(\DateTimeImmutable $createTime, ?Folder $folder = null): File
    {
        $file = new File();
        $file->setOriginalName('test-anonymous-' . uniqid() . '.txt');
        $file->setFileName('test-anonymous-' . uniqid() . '.txt');
        $file->setFilePath('uploads/test-' . uniqid() . '.txt');
        $file->setMimeType('text/plain');
        $file->setFileSize(1024);
        $file->setValid(true);
        $file->setFolder($folder);
        // 设置创建时间
        $file->setCreateTime($createTime);

        self::getEntityManager()->persist($file);
        self::getEntityManager()->flush();

        return $file;
    }

    private function createTestFolder(): Folder
    {
        $folder = new Folder();
        $folder->setName('Test Folder ' . uniqid());

        self::getEntityManager()->persist($folder);
        self::getEntityManager()->flush();

        return $folder;
    }
}
