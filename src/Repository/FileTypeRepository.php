<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\FileStorageBundle\Entity\FileType;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<FileType>
 */
#[AsRepository(entityClass: FileType::class)]
class FileTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileType::class);
    }

    /**
     * 按显示顺序查找所有文件类型
     *
     * @return list<FileType>
     * @phpstan-return list<FileType>
     */
    public function findAll(): array
    {
        $result = $this->createQueryBuilder('ft')
            ->orderBy('ft.displayOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var list<FileType> $result */
        return $result;
    }

    /**
     * 查找匿名上传的活动文件类型
     *
     * @return FileType[]
     * @phpstan-return array<FileType>
     */
    public function findActiveForAnonymous(): array
    {
        $result = $this->createQueryBuilder('ft')
            ->andWhere('ft.isActive = :active')
            ->andWhere('ft.uploadType IN (:types)')
            ->setParameter('active', true)
            ->setParameter('types', ['anonymous', 'both'])
            ->orderBy('ft.displayOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var FileType[] $result */
        return $result;
    }

    /**
     * 查找成员上传的活动文件类型
     *
     * @return FileType[]
     * @phpstan-return array<FileType>
     */
    public function findActiveForMember(): array
    {
        $result = $this->createQueryBuilder('ft')
            ->andWhere('ft.isActive = :active')
            ->andWhere('ft.uploadType IN (:types)')
            ->setParameter('active', true)
            ->setParameter('types', ['member', 'both'])
            ->orderBy('ft.displayOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var FileType[] $result */
        return $result;
    }

    /**
     * 根据文件扩展名查找文件类型
     *
     * @phpstan-return FileType|null
     */
    public function findByExtension(string $extension, string $uploadType): ?FileType
    {
        $types = match ($uploadType) {
            'anonymous' => ['anonymous', 'both'],
            'member' => ['member', 'both'],
            default => ['both'],
        };

        $result = $this->createQueryBuilder('ft')
            ->andWhere('ft.extension = :extension')
            ->andWhere('ft.isActive = :active')
            ->andWhere('ft.uploadType IN (:types)')
            ->setParameter('extension', strtolower($extension))
            ->setParameter('active', true)
            ->setParameter('types', $types)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof FileType ? $result : null;
    }

    /**
     * 根据 MIME 类型查找文件类型
     *
     * @phpstan-return FileType|null
     */
    public function findByMimeType(string $mimeType, string $uploadType): ?FileType
    {
        $types = match ($uploadType) {
            'anonymous' => ['anonymous', 'both'],
            'member' => ['member', 'both'],
            default => ['both'],
        };

        $result = $this->createQueryBuilder('ft')
            ->andWhere('ft.mimeType = :mimeType')
            ->andWhere('ft.isActive = :active')
            ->andWhere('ft.uploadType IN (:types)')
            ->setParameter('mimeType', $mimeType)
            ->setParameter('active', true)
            ->setParameter('types', $types)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof FileType ? $result : null;
    }

    /**
     * 获取给定上传类型的所有活动 MIME 类型
     *
     * @return string[]
     */
    public function getActiveMimeTypes(string $uploadType): array
    {
        $types = match ($uploadType) {
            'anonymous' => ['anonymous', 'both'],
            'member' => ['member', 'both'],
            default => ['both'],
        };

        $result = $this->createQueryBuilder('ft')
            ->select('ft.mimeType')
            ->andWhere('ft.isActive = :active')
            ->andWhere('ft.uploadType IN (:types)')
            ->setParameter('active', true)
            ->setParameter('types', $types)
            ->getQuery()
            ->getArrayResult()
        ;

        /** @var array<string> */
        return array_column($result, 'mimeType');
    }

    /**
     * 获取给定文件类型的最大文件大小
     */
    public function getMaxSizeForMimeType(string $mimeType, string $uploadType): ?int
    {
        $fileType = $this->findByMimeType($mimeType, $uploadType);

        return $fileType?->getMaxSize();
    }

    /**
     * 保存文件类型实体
     */
    public function save(FileType $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除文件类型实体
     */
    public function remove(FileType $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
