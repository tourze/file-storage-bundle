<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Service;

interface CrudActionResolverInterface
{
    /**
     * 获取当前CRUD操作类型
     */
    public function getCurrentCrudAction(): ?string;
}
