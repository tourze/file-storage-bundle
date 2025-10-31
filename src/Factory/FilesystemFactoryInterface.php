<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Factory;

use League\Flysystem\FilesystemOperator;

/**
 * 文件系统工厂接口
 *
 * 用于创建 Flysystem 文件系统实例
 */
interface FilesystemFactoryInterface
{
    /**
     * 创建文件系统操作器
     *
     * @return FilesystemOperator 文件系统操作器实例
     */
    public function createFilesystem(): FilesystemOperator;
}
