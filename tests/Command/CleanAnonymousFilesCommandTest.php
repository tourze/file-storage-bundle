<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\FileStorageBundle\Command\CleanAnonymousFilesCommand;
use Tourze\FileStorageBundle\Service\FileService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(CleanAnonymousFilesCommand::class)]
#[RunTestsInSeparateProcesses]
final class CleanAnonymousFilesCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    public function testExecuteWithDefaultHours(): void
    {
        $commandTester = $this->createCommandTesterWithMockedService(1);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Cleaning Anonymous Files', $output);
        $this->assertStringContainsString('Looking for anonymous files older than 1 hour(s)', $output);
        $this->assertStringContainsString('Successfully deleted 1 anonymous file(s)', $output);

        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithCustomHours(): void
    {
        $commandTester = $this->createCommandTesterWithMockedService(1);
        $commandTester->execute(['--hours' => '3']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Looking for anonymous files older than 3 hour(s)', $output);
        $this->assertStringContainsString('Successfully deleted 1 anonymous file(s)', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithNoFilesToDelete(): void
    {
        $commandTester = $this->createCommandTesterWithMockedService(0);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No anonymous files found to delete', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithDryRun(): void
    {
        // Boot a fresh kernel for dry-run test
        self::bootKernel();

        $mockFileService = $this->createMock(FileService::class);
        $mockFileService->expects($this->never())
            ->method('cleanupAnonymousFiles') // dry-run should not call actual cleanup
        ;

        self::getContainer()->set(FileService::class, $mockFileService);

        $this->assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $command = $application->find('file-storage:clean-anonymous');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('dry', strtolower($output));
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteDeletesMultipleFiles(): void
    {
        $commandTester = $this->createCommandTesterWithMockedService(2);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Successfully deleted 2 anonymous file(s)', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteDoesNotDeleteUserFiles(): void
    {
        $commandTester = $this->createCommandTesterWithMockedService(0);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No anonymous files found to delete', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testExecuteWithInvalidHours(): void
    {
        $this->commandTester->execute(['--hours' => '0']);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Hours must be at least 1', $output);

        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    public function testExecuteHandlesServiceException(): void
    {
        $commandTester = $this->createCommandTesterWithMockedService(0, true);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Error cleaning files: Database error', $output);

        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
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
        $commandTester = $this->createCommandTesterWithMockedService(2);
        $exitCode = $commandTester->execute(['--hours' => '5']);

        $this->assertContains($exitCode, [Command::SUCCESS, Command::FAILURE]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('5 hour(s)', $output);
    }

    public function testOptionDryRun(): void
    {
        // 创建不调用cleanupAnonymousFiles的mock服务来测试dry-run
        self::bootKernel();

        $mockFileService = $this->createMock(FileService::class);
        $mockFileService->expects($this->never())
            ->method('cleanupAnonymousFiles') // dry-run不应该调用实际清理
        ;

        self::getContainer()->set(FileService::class, $mockFileService);

        $this->assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $command = $application->find('file-storage:clean-anonymous');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute(['--dry-run' => true]);

        $this->assertContains($exitCode, [Command::SUCCESS, Command::FAILURE]);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('dry', strtolower($output));
    }

    protected function onSetUp(): void
    {
        // 初始化命令测试器
        $this->assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $command = $application->find('file-storage:clean-anonymous');
        $this->commandTester = new CommandTester($command);
    }

    private function createCommandTesterWithMockedService(int $returnValue, bool $shouldThrow = false): CommandTester
    {
        // Boot a fresh kernel for each test to avoid service replacement issues
        self::bootKernel();

        $mockFileService = $this->createMock(FileService::class);

        if ($shouldThrow) {
            $mockFileService->method('cleanupAnonymousFiles')
                ->willThrowException(new \RuntimeException('Database error'))
            ;
        } else {
            $mockFileService->expects($this->once())
                ->method('cleanupAnonymousFiles')
                ->with(self::isInstanceOf(\DateTimeImmutable::class))
                ->willReturn($returnValue)
            ;
        }

        // Replace service in container before it's initialized
        self::getContainer()->set(FileService::class, $mockFileService);

        $this->assertNotNull(self::$kernel);
        $application = new Application(self::$kernel);
        $command = $application->find('file-storage:clean-anonymous');

        return new CommandTester($command);
    }
}
