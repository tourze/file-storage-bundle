<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Command;

use ChrisUllyott\FileSize;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Service\FileService;

#[AsCommand(
    name: self::NAME,
    description: 'Clean up anonymous files older than specified hours',
)]
final class CleanAnonymousFilesCommand extends Command
{
    public const NAME = 'file-storage:clean-anonymous';

    public function __construct(
        private readonly FileService $fileService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'hours',
                null,
                InputOption::VALUE_REQUIRED,
                'Delete anonymous files older than this many hours',
                '1'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run the command in dry-run mode (no files will be deleted)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hoursOption = $input->getOption('hours');
        $hours = is_numeric($hoursOption) ? (int) $hoursOption : 0;
        $dryRun = $input->getOption('dry-run');

        if ($hours < 1) {
            $io->error('Hours must be at least 1');

            return Command::FAILURE;
        }

        $this->displayTitle($io, $hours);
        $olderThan = new \DateTimeImmutable(sprintf('-%d hours', $hours));

        if ((bool) $dryRun) {
            return $this->executeDryRun($io, $olderThan);
        }

        return $this->executeCleanup($io, $olderThan);
    }

    private function displayTitle(SymfonyStyle $io, int $hours): void
    {
        $io->title('Cleaning Anonymous Files');
        $io->text(sprintf('Looking for anonymous files older than %d hour(s)', $hours));
    }

    private function executeDryRun(SymfonyStyle $io, \DateTimeImmutable $olderThan): int
    {
        $io->note('Running in dry-run mode - no files will be deleted');
        $io->text(sprintf('Would delete files created before: %s', $olderThan->format('Y-m-d H:i:s')));

        $files = $this->fileService->getFileRepository()->findAnonymousFilesOlderThan($olderThan);
        $io->text(sprintf('Found %d anonymous file(s) to delete', count($files)));

        $this->displayFilesInVerboseMode($io, $files);

        return Command::SUCCESS;
    }

    /**
     * @param File[] $files
     */
    private function displayFilesInVerboseMode(SymfonyStyle $io, array $files): void
    {
        if (count($files) > 0 && $io->isVerbose()) {
            $io->section('Files that would be deleted:');
            foreach ($files as $file) {
                $io->text(sprintf(
                    '- %s (created: %s, size: %s)',
                    $file->getOriginalName(),
                    $file->getCreateTime()?->format('Y-m-d H:i:s') ?? 'Unknown',
                    (new FileSize($file->getFileSize() ?? 0))->asAuto()
                ));
            }
        }
    }

    private function executeCleanup(SymfonyStyle $io, \DateTimeImmutable $olderThan): int
    {
        try {
            $deletedCount = $this->fileService->cleanupAnonymousFiles($olderThan);

            if ($deletedCount > 0) {
                $io->success(sprintf('Successfully deleted %d anonymous file(s)', $deletedCount));
            } else {
                $io->info('No anonymous files found to delete');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('Error cleaning files: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
