<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Factory;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[Autoconfigure(lazy: true)]
#[WithMonologChannel(channel: 'file_storage')]
class FilesystemFactory implements FilesystemFactoryInterface
{
    private string $uploadDirectory;

    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'kernel.project_dir')] private readonly string $projectDir,
    ) {
        $this->uploadDirectory = $this->projectDir . '/public/files';
    }

    public function createFilesystem(): FilesystemOperator
    {
        $this->logger->debug('Creating default file system');

        // Create local adapter with upload directory
        $adapter = new LocalFilesystemAdapter($this->uploadDirectory);

        // Create and return filesystem
        return new Filesystem($adapter);
    }
}
