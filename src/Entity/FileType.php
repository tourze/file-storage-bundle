<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\FileStorageBundle\Exception\InvalidUploadTypeException;
use Tourze\FileStorageBundle\Repository\FileTypeRepository;

#[ORM\Entity(repositoryClass: FileTypeRepository::class)]
#[ORM\Table(name: 'file_type', options: ['comment' => '文件类型配置表'])]
#[ORM\UniqueConstraint(name: 'uniq_mime_type_upload_type', columns: ['mime_type', 'upload_type'])]
#[ORM\UniqueConstraint(name: 'uniq_extension_upload_type', columns: ['extension', 'upload_type'])]
class FileType implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '文件类型名称'])]
    #[Assert\NotBlank(message: '文件类型名称不能为空')]
    #[Assert\Length(max: 50, maxMessage: '文件类型名称长度不能超过 {{ limit }} 个字符')]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => 'MIME类型'])]
    #[Assert\NotBlank(message: 'MIME类型不能为空')]
    #[Assert\Length(max: 255, maxMessage: 'MIME类型长度不能超过 {{ limit }} 个字符')]
    private string $mimeType;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '文件扩展名'])]
    #[Assert\NotBlank(message: '文件扩展名不能为空')]
    #[Assert\Length(max: 20, maxMessage: '文件扩展名长度不能超过 {{ limit }} 个字符')]
    private string $extension;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '最大文件大小(字节)'])]
    #[Assert\PositiveOrZero(message: '最大文件大小必须为非负数')]
    private int $maxSize;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '上传类型: anonymous, member, both'])]
    #[Assert\NotBlank(message: '上传类型不能为空')]
    #[Assert\Choice(choices: ['anonymous', 'member', 'both'], message: '上传类型必须为: anonymous, member, both 中的一个')]
    #[Assert\Length(max: 20, maxMessage: '上传类型长度不能超过 {{ limit }} 个字符')]
    private string $uploadType = 'both'; // 'anonymous', 'member', 'both'

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    #[Assert\Length(max: 1000, maxMessage: '描述长度不能超过 {{ limit }} 个字符')]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '显示顺序'])]
    #[Assert\GreaterThanOrEqual(value: 0, message: '显示顺序必须大于或等于0')]
    private int $displayOrder = 0;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否激活'])]
    #[Assert\Type(type: 'bool', message: '是否激活必须为布尔值')]
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function getMaxSize(): int
    {
        return $this->maxSize;
    }

    public function setMaxSize(int $maxSize): void
    {
        $this->maxSize = $maxSize;
    }

    public function getUploadType(): string
    {
        return $this->uploadType;
    }

    public function setUploadType(string $uploadType): void
    {
        if (!in_array($uploadType, ['anonymous', 'member', 'both'], true)) {
            throw new InvalidUploadTypeException('Invalid upload type');
        }

        $this->uploadType = $uploadType;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): void
    {
        $this->displayOrder = $displayOrder;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isAllowedForAnonymous(): bool
    {
        return in_array($this->uploadType, ['anonymous', 'both'], true);
    }

    public function isAllowedForMember(): bool
    {
        return in_array($this->uploadType, ['member', 'both'], true);
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->mimeType);
    }
}
