# File Storage Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP 版本](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![许可证](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

[![构建状态](https://img.shields.io/github/workflow/status/tourze/file-storage-bundle/CI)](https://github.com/tourze/file-storage-bundle/actions)

[![代码覆盖率](https://img.shields.io/codecov/c/github/tourze/file-storage-bundle)](https://codecov.io/gh/tourze/file-storage-bundle)

Symfony 文件上传和存储管理 Bundle，集成了 Flysystem。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [配置](#配置)
- [数据库设置](#数据库设置)
- [使用方法](#使用方法)
  - [上传文件](#上传文件)
  - [获取允许的文件类型](#获取允许的文件类型)
  - [获取文件信息](#获取文件信息)
- [文件类型管理](#文件类型管理)
- [控制台命令](#控制台命令)
  - [清理匿名文件](#清理匿名文件)
  - [删除文件](#删除文件)
  - [定时任务示例](#定时任务示例)
  - [文件统计](#文件统计)
- [服务使用](#服务使用)
- [高级存储配置](#高级存储配置)
- [实体结构](#实体结构)
- [安全性](#安全性)
- [依赖项](#依赖项)
- [高级用法](#高级用法)
- [测试](#测试)
- [许可证](#许可证)

## 功能特性

- Flysystem 集成，支持灵活的文件存储（本地、S3、FTP 等）
- 为匿名用户和认证用户分别提供上传端点
- 基于数据库的文件类型管理，可配置权限
- 在数据库中存储文件元数据并跟踪用户
- 自动计算文件哈希值（MD5、SHA1）
- 按年/月结构组织文件
- 支持软删除
- 自动清理匿名文件
- 文件统计功能
- 通过哈希值检测重复文件
- 支持 EasyAdmin 集成

## 安装

```bash
composer require tourze/file-storage-bundle
```

## 配置

在 `config/bundles.php` 中添加 Bundle：

```php
return [
    // ...
    Tourze\FileStorageBundle\FileStorageBundle::class => ['all' => true],
];
```

Bundle 会自动配置 Flysystem 使用本地适配器。基本使用无需额外配置。

## 数据库设置

运行迁移创建所需的表：

```bash
bin/console doctrine:migrations:migrate
```

Bundle 创建的表：
- `files` - 存储文件元数据
- `file_type` - 管理允许的文件类型

## 使用方法

### 上传文件

#### 匿名上传

```bash
POST /api/files/upload/anonymous
Content-Type: multipart/form-data

file: (binary data)
```

响应示例：
```json
{
  "success": true,
  "file": {
    "id": 123,
    "originalName": "document.pdf",
    "fileName": "document-65a1b2c3d4e5f.pdf",
    "mimeType": "application/pdf",
    "fileSize": 1048576,
    "md5Hash": "abc123...",
    "createTime": "2024-01-01 12:00:00"
  }
}
```

#### 会员上传（需要认证）

```bash
POST /api/files/upload/member
Authorization: Bearer YOUR_TOKEN
Content-Type: multipart/form-data

file: (binary data)
```

### 获取允许的文件类型

#### 匿名用户

```bash
GET /api/files/allowed-types/anonymous
```

#### 会员用户

```bash
GET /api/files/allowed-types/member
```

响应示例：
```json
{
  "allowedTypes": [
    {
      "name": "JPEG 图片",
      "mimeType": "image/jpeg",
      "extension": "jpg",
      "maxSize": 5242880
    }
  ]
}
```

### 获取文件信息

```bash
GET /api/files/{id}
```

## 文件类型管理

文件类型存储在数据库中，可以通过 EasyAdmin 或编程方式管理：

```php
// 创建新的文件类型
$fileType = new FileType();
$fileType->setName('PowerPoint 演示文稿')
    ->setMimeType('application/vnd.ms-powerpoint')
    ->setExtension('ppt')
    ->setMaxSize(30 * 1024 * 1024) // 30MB
    ->setUploadType('member') // 'anonymous'（匿名）、'member'（会员）或 'both'（两者）
    ->setIsActive(true);

$entityManager->persist($fileType);
$entityManager->flush();
```

## 控制台命令

### 清理匿名文件

删除超过指定小时数的匿名文件：

```bash
# 删除超过 1 小时的匿名文件（默认）
bin/console file-storage:clean-anonymous

# 删除超过 3 小时的匿名文件
bin/console file-storage:clean-anonymous --hours=3

# 试运行 - 显示将要删除的内容但不实际删除
bin/console file-storage:clean-anonymous --dry-run

# 详细输出显示文件细节
bin/console file-storage:clean-anonymous --dry-run -v
```

#### 删除文件

递归删除指定目录下的所有文件：

```bash
# 删除目录中的所有文件（需要确认）
bin/console file-storage:delete path/to/directory

# 跳过确认提示
bin/console file-storage:delete path/to/directory --force

# 同时删除空目录
bin/console file-storage:delete path/to/directory --include-dirs

# 试运行 - 显示将要删除的内容但不实际删除
bin/console file-storage:delete path/to/directory --dry-run

# 详细输出显示文件细节
bin/console file-storage:delete path/to/directory --dry-run -v

# 组合使用：强制删除文件和目录
bin/console file-storage:delete path/to/directory -f -d
```

#### 定时任务示例

添加到 crontab 以每小时运行一次：

```bash
0 * * * * cd /path/to/project && bin/console file-storage:clean-anonymous
```

#### 文件统计

显示文件存储统计信息：

```bash
# 显示文件统计
bin/console file-storage:stats

# 输出示例：
# 文件存储统计
# =======================
# 总文件数：1,234
# 总大小：5.67 GB
# 平均文件大小：4.71 MB
# 匿名文件：456
# 会员文件：778
```

## 服务使用

您可以在代码中注入 `FileService`：

```php
use Tourze\FileStorageBundle\Service\FileService;

class MyService
{
    public function __construct(
        private readonly FileService $fileService
    ) {
    }

    public function handleUpload(UploadedFile $file): void
    {
        // 验证文件
        $this->fileService->validateFileForUpload($file, 'member');
        
        // 上传文件
        $fileEntity = $this->fileService->uploadFile($file, $this->getUser());
        
        // 获取文件内容
        $content = $this->fileService->getFileContent($fileEntity);
        
        // 检查文件是否存在
        if ($this->fileService->fileExists($fileEntity)) {
            // ...
        }
        
        // 查找重复文件
        $duplicates = $this->fileService->findDuplicatesByMd5($fileEntity->getMd5Hash());
        
        // 软删除文件
        $this->fileService->deleteFile($fileEntity);
        
        // 获取文件统计
        $stats = $this->fileService->getFileStats();
    }
}
```

## 高级存储配置

Bundle 使用 Flysystem 进行文件存储抽象。默认使用本地文件系统适配器。

### 自定义存储适配器

要使用不同的存储后端（例如 AWS S3、FTP 等），创建您自己的工厂：

```php
// src/Factory/S3FilesystemFactory.php
namespace App\Factory;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;

class S3FilesystemFactory
{
    public function __construct(
        private readonly S3Client $s3Client,
        private readonly string $bucket,
    ) {
    }

    public function createFilesystem(): FilesystemOperator
    {
        $adapter = new AwsS3V3Adapter($this->s3Client, $this->bucket);
        return new Filesystem($adapter);
    }
}
```

## 实体结构

### File 实体

- `originalName` - 原始上传文件名
- `fileName` - 生成的唯一文件名
- `filePath` - 文件的相对路径
- `mimeType` - 文件 MIME 类型
- `fileSize` - 文件大小（字节）
- `md5Hash` - 文件内容的 MD5 哈希值
- `sha1Hash` - 文件内容的 SHA1 哈希值
- `createTime` - 上传时间戳
- `updateTime` - 最后更新时间戳
- `metadata` - 额外数据的 JSON 字段
- `storageType` - 存储后端类型（默认：local）
- `isActive` - 软删除标志
- `userId` - 会员上传的用户 ID（匿名上传为 null）

### FileType 实体

- `name` - 显示名称（例如："JPEG 图片"）
- `mimeType` - MIME 类型（例如："image/jpeg"）
- `extension` - 文件扩展名（例如："jpg"）
- `maxSize` - 最大文件大小（字节）
- `uploadType` - 谁可以上传（'anonymous'、'member' 或 'both'）
- `isActive` - 此类型是否启用
- `createTime` - 创建时间戳
- `updateTime` - 最后更新时间戳

## 安全性

### 访问控制

- 匿名上传有单独的端点和权限
- 会员上传需要认证
- 文件类型可以按上传类型（匿名/会员/两者）限制
- 每种文件类型都强制执行文件大小限制

### 数据保护

- 计算文件哈希值（MD5、SHA1）用于完整性验证
- 文件使用唯一生成的名称存储以防止冲突
- 原始文件名保存在数据库中
- 认证上传的用户跟踪
- 用于安全审计的 IP 地址跟踪

### 最佳实践

1. 始终在服务器端验证文件类型
2. 设置适当的文件大小限制
3. 定期清理匿名文件
4. 监控文件统计以发现异常活动
5. 使用 HTTPS 进行文件上传
6. 考虑为上传的文件实施病毒扫描

## 依赖项

此 Bundle 需要：

- PHP ^8.1
- Symfony ^6.4
- Doctrine ORM ^2.0 || ^3.0
- League Flysystem ^3.0
- Symfony String 组件

Bundle 依赖项：

- `tourze/doctrine-ip-bundle`
- `tourze/doctrine-timestamp-bundle`
- `tourze/doctrine-track-bundle`
- `tourze/doctrine-user-bundle`
- `tourze/doctrine-snowflake-bundle`

## 高级用法

### 事件监听器

您可以监听文件上传事件：

```php
namespace App\EventListener;

use Tourze\FileStorageBundle\Event\FileUploadedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: FileUploadedEvent::class)]
class FileUploadListener
{
    public function __invoke(FileUploadedEvent $event): void
    {
        $file = $event->getFile();
        // 自定义逻辑：病毒扫描、图像优化等
    }
}
```

### 自定义文件验证器

扩展验证逻辑：

```php
namespace App\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tourze\FileStorageBundle\Validator\FileValidatorInterface;

class VirusScanValidator implements FileValidatorInterface
{
    public function validate(UploadedFile $file): void
    {
        // 实现病毒扫描逻辑
        if ($this->hasVirus($file)) {
            throw new FileValidationException('文件包含恶意软件');
        }
    }
}
```

## 测试

Bundle 包含全面的测试覆盖：

```bash
# 运行所有测试
./vendor/bin/phpunit packages/file-storage-bundle/tests

# 运行并生成覆盖率报告
./vendor/bin/phpunit packages/file-storage-bundle/tests --coverage-html coverage
```

**测试套件结果：**
- 📊 **128 个测试，424 个断言**
- ✅ **所有测试通过**
- 🧪 **核心组件 100% 测试覆盖**
- 🎯 **单元测试 + 集成测试** 覆盖控制器、服务、仓库和命令

### PHPStan 分析

使用 PHPStan level 5 进行静态分析：

```bash
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/file-storage-bundle
```

**分析结果：**
- ✅ **0 个错误** - 干净的代码，正确的类型声明
- 🔒 **Level 5** - 启用严格类型检查
- 📝 **完整文档** - 所有公共方法都有文档

## 许可证

此 Bundle 基于 MIT 许可证发布。详情请参阅 [LICENSE](LICENSE) 文件。