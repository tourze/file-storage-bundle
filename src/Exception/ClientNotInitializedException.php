<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Exception;

class ClientNotInitializedException extends \RuntimeException
{
    public function __construct(string $message = 'Client not initialized', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
