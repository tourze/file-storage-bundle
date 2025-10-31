<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\Arrayable;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\CreatedByAware;
use Tourze\FileStorageBundle\Repository\FolderRepository;

/**
 * @implements Arrayable<string, mixed>
 */
#[ORM\Entity(repositoryClass: FolderRepository::class)]
#[ORM\Table(name: 'upload_folder', options: ['comment' => '文件目录'])]
#[ORM\UniqueConstraint(name: 'uniq_title_is_active', columns: ['title', 'is_active'])]
class Folder implements \Stringable, Arrayable
{
    use TimestampableAware;
    use CreatedByAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '名称'])]
    #[Assert\NotBlank(message: '名称不能为空')]
    #[Assert\Length(max: 100, maxMessage: '名称长度不能超过 {{ limit }} 个字符')]
    #[IndexColumn]
    private string $title;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Folder $parent = null;

    /**
     * @var Collection<int, Folder>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '路径'])]
    #[Assert\Length(max: 500, maxMessage: '路径长度不能超过 {{ limit }} 个字符')]
    private ?string $path = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    #[Assert\Length(max: 1000, maxMessage: '描述长度不能超过 {{ limit }} 个字符')]
    private ?string $description = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否公开'])]
    #[Assert\Type(type: 'bool', message: '公开状态必须为布尔值')]
    private bool $isPublic = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否活跃'])]
    #[Assert\Type(type: 'bool', message: '活跃状态必须为布尔值')]
    private bool $isActive = true;

    /**
     * @var Collection<int, File>
     */
    #[ORM\OneToMany(mappedBy: 'folder', targetEntity: File::class)]
    private Collection $files;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户标识'])]
    #[Assert\Length(max: 255, maxMessage: '用户标识长度不能超过 {{ limit }} 个字符')]
    private ?string $userIdentifier = null;

    // 临时存储用户对象，用于测试兼容性（不持久化）
    #[Assert\Type(type: UserInterface::class, message: '临时用户必须为UserInterface实例')]
    private ?UserInterface $tempUser = null;

    public function __construct()
    {
        $this->files = new ArrayCollection();
        $this->children = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * 别名方法：获取名称
     */
    public function getName(): string
    {
        return $this->title;
    }

    /**
     * 别名方法：设置名称
     */
    public function setName(string $name): void
    {
        $this->title = $name;
    }

    public function getParent(): ?Folder
    {
        return $this->parent;
    }

    public function setParent(?Folder $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * @return Collection<int, Folder>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(Folder $child): void
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
    }

    public function removeChild(Folder $child): void
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    public function getFullPath(): string
    {
        $path = $this->path ?? $this->title ?? '';

        if (null !== $this->parent) {
            return $this->parent->getFullPath() . '/' . $path;
        }

        return $path;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): void
    {
        $this->isPublic = $isPublic;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function isRoot(): bool
    {
        return null === $this->parent;
    }

    public function hasChildren(): bool
    {
        return !$this->children->isEmpty();
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): void
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setFolder($this);
        }
    }

    public function removeFile(File $file): void
    {
        if ($this->files->removeElement($file)) {
            if ($file->getFolder() === $this) {
                $file->setFolder(null);
            }
        }
    }

    public function hasFiles(): bool
    {
        return !$this->files->isEmpty();
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

    public function isAnonymous(): bool
    {
        return null === $this->userIdentifier;
    }

    public function __toString(): string
    {
        return sprintf('#%s %s', $this->id ?? 'new', $this->title);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getTitle(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'path' => $this->getPath(),
            'fullPath' => $this->getFullPath(),
            'isPublic' => $this->isPublic(),
            'isRoot' => $this->isRoot(),
            'hasChildren' => $this->hasChildren(),
            'hasFiles' => $this->hasFiles(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
        ];
    }
}
