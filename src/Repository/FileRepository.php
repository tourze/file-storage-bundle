<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\FileStorageBundle\Entity\File;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<File>
 */
#[AsRepository(entityClass: File::class)]
class FileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, File::class);
    }

    /**
     * 根据文件名模式查找文件
     *
     * @return File[]
     */
    public function findByFileNamePattern(string $pattern): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.fileName LIKE :pattern')
            ->andWhere('f.valid = :valid')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->setParameter('valid', true)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        /** @var File[] $result */
        return is_array($result) ? $result : [];
    }

    /**
     * 根据文件类型查找文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findByType(string $type): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.type = :type')
            ->andWhere('f.valid = :valid')
            ->setParameter('type', $type)
            ->setParameter('valid', true)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 根据文件扩展名查找文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findByExt(string $ext): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.ext = :ext')
            ->andWhere('f.valid = :valid')
            ->setParameter('ext', $ext)
            ->setParameter('valid', true)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 根据 MD5 值查找文件
     */
    public function findByMd5File(string $md5File): ?File
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.md5File = :md5File')
            ->andWhere('f.valid = :valid')
            ->setParameter('md5File', $md5File)
            ->setParameter('valid', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof File ? $result : null;
    }

    /**
     * 根据 fileKey 查找文件
     */
    public function findByFileKey(string $fileKey): ?File
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.fileKey = :fileKey')
            ->andWhere('f.valid = :valid')
            ->setParameter('fileKey', $fileKey)
            ->setParameter('valid', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof File ? $result : null;
    }

    /**
     * 根据文件夹查找文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findByFolder(?Folder $folder): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.valid = :valid')
            ->setParameter('valid', true)
        ;

        if (null === $folder) {
            $qb->andWhere('f.folder IS NULL');
        } else {
            $qb->andWhere('f.folder = :folder')
                ->setParameter('folder', $folder)
            ;
        }

        $result = $qb->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 根据年月查找文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findByYearMonth(?int $year, ?int $month): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.valid = :valid')
            ->setParameter('valid', true)
        ;

        if (null !== $year) {
            $qb->andWhere('f.year = :year')
                ->setParameter('year', $year)
            ;
        }

        if (null !== $month) {
            $qb->andWhere('f.month = :month')
                ->setParameter('month', $month)
            ;
        }

        $result = $qb->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 查找在指定日期范围内创建的有效文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.createTime >= :startDate')
            ->andWhere('f.createTime <= :endDate')
            ->andWhere('f.valid = :valid')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('valid', true)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 获取所有有效文件的总大小
     */
    public function getTotalValidFilesSize(): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('SUM(f.size) as totalSize')
            ->andWhere('f.valid = :valid')
            ->setParameter('valid', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * 查找热门文件（按访问次数排序）
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findMostViewed(int $limit = 10): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.valid = :valid')
            ->andWhere('f.viewCount > 0')
            ->setParameter('valid', true)
            ->orderBy('f.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 查找最近下载的文件（按下载次数排序）
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findMostDownloaded(int $limit = 10): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.valid = :valid')
            ->andWhere('f.downloadCount > 0')
            ->setParameter('valid', true)
            ->orderBy('f.downloadCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 查找图片文件（有宽度和高度的文件）
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findImages(): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.width IS NOT NULL')
            ->andWhere('f.height IS NOT NULL')
            ->andWhere('f.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 查找指定日期之前的匿名文件（无文件夹关联）
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findAnonymousFilesOlderThan(\DateTimeInterface $date): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.folder IS NULL')
            ->andWhere('f.createTime < :date')
            ->andWhere('f.valid = :valid')
            ->setParameter('date', $date)
            ->setParameter('valid', true)
            ->orderBy('f.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 根据MD5哈希值查找文件
     */
    public function findByMd5Hash(string $md5Hash): ?File
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.md5File = :md5Hash')
            ->andWhere('f.valid = :valid')
            ->setParameter('md5Hash', $md5Hash)
            ->setParameter('valid', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof File ? $result : null;
    }

    /**
     * 获取所有活跃文件的总大小
     */
    public function getTotalActiveFilesSize(): int
    {
        $result = $this->createQueryBuilder('f')
            ->select('SUM(f.size) as totalSize')
            ->andWhere('f.valid = :valid')
            ->setParameter('valid', true)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) ($result ?? 0);
    }

    /**
     * 增加文件访问次数
     */
    public function incrementViewCount(File $file): void
    {
        $this->createQueryBuilder('f')
            ->update()
            ->set('f.viewCount', 'COALESCE(f.viewCount, 0) + 1')
            ->andWhere('f.id = :id')
            ->setParameter('id', $file->getId())
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 增加文件下载次数
     */
    public function incrementDownloadCount(File $file): void
    {
        $this->createQueryBuilder('f')
            ->update()
            ->set('f.downloadCount', 'COALESCE(f.downloadCount, 0) + 1')
            ->andWhere('f.id = :id')
            ->setParameter('id', $file->getId())
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * 保存文件实体
     */
    public function save(File $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除文件实体
     */
    public function remove(File $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 刷新所有待处理的更改
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * 根据用户查找文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findByUser(?UserInterface $user): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.valid = :valid')
            ->setParameter('valid', true)
        ;

        if (null === $user) {
            $qb->andWhere('f.userIdentifier IS NULL');
        } else {
            $qb->andWhere('f.userIdentifier = :userIdentifier')
                ->setParameter('userIdentifier', $user->getUserIdentifier())
            ;
        }

        $result = $qb->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 根据文件名模式查找文件（别名方法）
     *
     * @return File[]
     */
    public function findByNamePattern(string $pattern): array
    {
        return $this->findByFileNamePattern($pattern);
    }

    /**
     * 根据文件类型查找文件（别名方法）
     *
     * @return File[]
     */
    public function findByFiletype(string $filetype): array
    {
        return $this->findByType($filetype);
    }

    /**
     * 查找公共文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findPublicFiles(): array
    {
        $result = $this->createQueryBuilder('f')
            ->leftJoin('f.folder', 'folder')
            ->andWhere('f.valid = :valid')
            ->andWhere('(folder.isPublic = :isPublic OR f.folder IS NULL)')
            ->setParameter('valid', true)
            ->setParameter('isPublic', true)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 根据原始文件名模式查找文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findByOriginalNamePattern(string $pattern): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.fileName LIKE :pattern')
            ->andWhere('f.valid = :valid')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->setParameter('valid', true)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }

    /**
     * 根据MIME类型查找文件
     *
     * @return File[]
     * @phpstan-return array<File>
     */
    public function findByMimeType(string $mimeType): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.type = :mimeType')
            ->andWhere('f.valid = :valid')
            ->setParameter('mimeType', $mimeType)
            ->setParameter('valid', true)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var File[] $result */
        return $result;
    }
}
