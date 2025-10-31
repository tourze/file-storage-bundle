<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\Arrayable;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\CreatedFromIpAware;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\FileStorageBundle\Repository\FileRepository;

/**
 * @implements Arrayable<string, mixed>
 */
#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\Table(name: 'upload_file', options: ['comment' => '素材管理'])]
class File implements \Stringable, Arrayable
{
    use SnowflakeKeyAware;
    use TimestampableAware;
    use CreatedFromIpAware;
    use CreatedByAware;

    #[ORM\ManyToOne(targetEntity: Folder::class, inversedBy: 'files')]
    #[ORM\JoinColumn(name: 'folder_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Folder $folder = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => 'URL'])]
    #[Assert\Length(max: 2000, maxMessage: 'URL长度不能超过 {{ limit }} 个字符')]
    private ?string $url = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '年'])]
    #[Assert\Range(min: 1900, max: 2100, notInRangeMessage: '年份必须在 {{ min }} 到 {{ max }} 之间')]
    private ?int $year = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '月'])]
    #[Assert\Range(min: 1, max: 12, notInRangeMessage: '月份必须在 {{ min }} 到 {{ max }} 之间')]
    private ?int $month = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '文件类型'])]
    #[Assert\Length(max: 255, maxMessage: '文件类型长度不能超过 {{ limit }} 个字符')]
    private ?string $type = null;

    #[ORM\Column(name: 'file_name', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '生成的文件名'])]
    #[Assert\Length(max: 255, maxMessage: '生成的文件名长度不能超过 {{ limit }} 个字符')]
    #[IndexColumn]
    private ?string $fileName = null;

    #[ORM\Column(name: 'origin_file_name', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '原始文件名'])]
    #[Assert\Length(max: 255, maxMessage: '原始文件名长度不能超过 {{ limit }} 个字符')]
    #[IndexColumn]
    private ?string $originFileName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '大小'])]
    #[Assert\PositiveOrZero(message: '文件大小必须为非负数')]
    private ?int $size = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '图片高度'])]
    #[Assert\PositiveOrZero(message: '图片高度必须为非负数')]
    private ?int $height = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '图片宽度'])]
    #[Assert\PositiveOrZero(message: '图片宽度必须为非负数')]
    private ?int $width = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['comment' => '文件扩展名'])]
    #[Assert\Length(max: 20, maxMessage: '文件扩展名长度不能超过 {{ limit }} 个字符')]
    private ?string $ext = null;

    #[ORM\Column(name: 'file_key', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '完整文件KEY'])]
    #[Assert\Length(max: 255, maxMessage: '完整文件KEY长度不能超过 {{ limit }} 个字符')]
    #[IndexColumn]
    private ?string $fileKey = null;

    #[ORM\Column(name: 'view_count', type: Types::INTEGER, nullable: true, options: ['comment' => '访问次数'])]
    #[Assert\PositiveOrZero(message: '访问次数必须为非负数')]
    private ?int $viewCount = null;

    #[ORM\Column(name: 'download_count', type: Types::INTEGER, nullable: true, options: ['comment' => '下载次数'])]
    #[Assert\PositiveOrZero(message: '下载次数必须为非负数')]
    private ?int $downloadCount = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '有效'])]
    #[Assert\Type(type: 'bool', message: '有效字段必须为布尔值')]
    #[IndexColumn]
    private bool $valid = true;

    #[ORM\Column(name: 'md5_file', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'MD5文件哈希值'])]
    #[Assert\Length(max: 255, maxMessage: 'MD5值长度不能超过 {{ limit }} 个字符')]
    private ?string $md5File = null;

    #[ORM\Column(name: 'sha1_file', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'SHA1文件哈希值'])]
    #[Assert\Length(max: 255, maxMessage: 'SHA1值长度不能超过 {{ limit }} 个字符')]
    private ?string $sha1File = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => 'EXIF数据'])]
    #[Assert\Type(type: 'array', message: 'EXIF数据必须为数组')]
    private ?array $exif = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户标识'])]
    #[Assert\Length(max: 255, maxMessage: '用户标识长度不能超过 {{ limit }} 个字符')]
    private ?string $userIdentifier = null;

    // 临时存储用户对象，用于测试兼容性（不持久化）
    #[Assert\Type(type: UserInterface::class, message: '临时用户必须为UserInterface实例')]
    private ?UserInterface $tempUser = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    #[Assert\Type(type: 'array', message: '元数据必须为数组')]
    private ?array $metadata = null;

    public function getFolder(): ?Folder
    {
        return $this->folder;
    }

    public function setFolder(?Folder $folder): void
    {
        $this->folder = $folder;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): void
    {
        $this->year = $year;
    }

    public function getMonth(): ?int
    {
        return $this->month;
    }

    public function setMonth(?int $month): void
    {
        $this->month = $month;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getOriginFileName(): ?string
    {
        return $this->originFileName;
    }

    public function setOriginFileName(?string $originFileName): void
    {
        $this->originFileName = $originFileName;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): void
    {
        $this->size = $size;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): void
    {
        $this->height = $height;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): void
    {
        $this->width = $width;
    }

    public function getExt(): ?string
    {
        return $this->ext;
    }

    public function setExt(?string $ext): void
    {
        $this->ext = $ext;
    }

    public function getFileKey(): ?string
    {
        return $this->fileKey;
    }

    public function setFileKey(?string $fileKey): void
    {
        $this->fileKey = $fileKey;
    }

    public function getViewCount(): ?int
    {
        return $this->viewCount;
    }

    public function setViewCount(?int $viewCount): void
    {
        $this->viewCount = $viewCount;
    }

    public function getDownloadCount(): ?int
    {
        return $this->downloadCount;
    }

    public function setDownloadCount(?int $downloadCount): void
    {
        $this->downloadCount = $downloadCount;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
    }

    /**
     * 别名方法：获取原始文件名
     */
    public function getOriginalName(): ?string
    {
        return $this->originFileName;
    }

    /**
     * 别名方法：获取文件大小
     */
    public function getFileSize(): ?int
    {
        return $this->size;
    }

    /**
     * 别名方法：检查文件是否活跃/有效
     */
    public function isActive(): bool
    {
        return $this->valid;
    }

    /**
     * 别名方法：设置原始文件名
     */
    public function setOriginalName(?string $originalName): void
    {
        $this->originFileName = $originalName;
    }

    /**
     * 获取文件路径（基于URL或fileKey构建）
     */
    public function getFilePath(): string
    {
        if (null !== $this->fileKey && '' !== $this->fileKey) {
            return $this->fileKey;
        }

        // 从URL中提取路径部分
        if (null !== $this->url) {
            $parsedUrl = parse_url($this->url);

            return $parsedUrl['path'] ?? '';
        }

        return $this->fileName ?? '';
    }

    /**
     * 获取MIME类型（别名方法）
     */
    public function getMimeType(): ?string
    {
        return $this->type;
    }

    /**
     * 获取MD5哈希值（别名方法）
     */
    public function getMd5Hash(): ?string
    {
        return $this->md5File;
    }

    /**
     * 获取SHA1哈希值（别名方法）
     */
    public function getSha1Hash(): ?string
    {
        return $this->sha1File;
    }

    /**
     * 获取公共访问URL
     */
    public function getPublicUrl(): ?string
    {
        return $this->url;
    }

    /**
     * 设置公共访问URL
     */
    public function setPublicUrl(?string $publicUrl): void
    {
        $this->url = $publicUrl;
    }

    /**
     * 设置文件路径（更新fileKey）
     */
    public function setFilePath(string $filePath): void
    {
        $this->fileKey = $filePath;
    }

    /**
     * 设置激活状态
     */
    public function setIsActive(bool $isActive): void
    {
        $this->valid = $isActive;
    }

    /**
     * 设置MIME类型
     */
    public function setMimeType(string $mimeType): void
    {
        $this->type = $mimeType;
    }

    /**
     * 设置文件大小
     */
    public function setFileSize(?int $fileSize): void
    {
        $this->size = $fileSize;
    }

    /**
     * 设置MD5哈希值
     */
    public function setMd5Hash(?string $md5Hash): void
    {
        $this->md5File = $md5Hash;
    }

    public function getMd5File(): ?string
    {
        return $this->md5File;
    }

    public function setMd5File(?string $md5File): void
    {
        $this->md5File = $md5File;
    }

    public function getSha1File(): ?string
    {
        return $this->sha1File;
    }

    public function setSha1File(?string $sha1File): void
    {
        $this->sha1File = $sha1File;
    }

    /**
     * 设置SHA1哈希值（别名方法）
     */
    public function setSha1Hash(?string $sha1Hash): void
    {
        $this->sha1File = $sha1Hash;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExif(): ?array
    {
        return $this->exif;
    }

    /**
     * @param array<string, mixed>|null $exif
     */
    public function setExif(?array $exif): void
    {
        $this->exif = $exif;
    }

    public function getUser(): ?UserInterface
    {
        return $this->tempUser;
    }

    public function setUser(?UserInterface $user): void
    {
        $this->tempUser = $user;
        $this->userIdentifier = $user?->getUserIdentifier();
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): void
    {
        $this->userIdentifier = $userIdentifier;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function isAnonymous(): bool
    {
        return null === $this->userIdentifier;
    }

    public function __toString(): string
    {
        return sprintf('#%s %s', $this->id ?? 'new', $this->fileName ?? 'unknown');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'url' => $this->getUrl(),
            'originalName' => $this->getOriginalName(),
            'originFileName' => $this->getOriginFileName(),
            'fileName' => $this->getFileName(),
            'filePath' => $this->getFilePath(),
            'mimeType' => $this->getMimeType(),
            'fileSize' => $this->getFileSize(),
            'size' => $this->getSize(),
            'type' => $this->getType(),
            'ext' => $this->getExt(),
            'width' => $this->getWidth(),
            'height' => $this->getHeight(),
            'year' => $this->getYear(),
            'month' => $this->getMonth(),
            'fileKey' => $this->getFileKey(),
            'viewCount' => $this->getViewCount(),
            'downloadCount' => $this->getDownloadCount(),
            'valid' => $this->isValid(),
            'md5File' => $this->getMd5File(),
            'md5Hash' => $this->getMd5Hash(),
            'publicUrl' => $this->getPublicUrl(),
            'metadata' => $this->getMetadata(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }
}
