<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Exception;

class FolderCreationException extends \Exception
{
    public function __construct(string $message = 'Failed to create folder', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
