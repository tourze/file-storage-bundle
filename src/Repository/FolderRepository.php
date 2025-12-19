<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Folder>
 */
#[AsRepository(entityClass: Folder::class)]
final class FolderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Folder::class);
    }

    /**
     * 根据标题模式查找文件夹
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findByTitlePattern(string $pattern): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.title LIKE :pattern')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 根据标题查找文件夹
     */
    public function findByTitle(string $title): ?Folder
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.title = :title')
            ->setParameter('title', $title)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof Folder ? $result : null;
    }

    /**
     * 查找包含文件的文件夹
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findFoldersWithFiles(): array
    {
        $result = $this->createQueryBuilder('f')
            ->innerJoin('f.files', 'file')
            ->groupBy('f.id')
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 查找空文件夹（没有文件）
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findEmptyFolders(): array
    {
        $result = $this->createQueryBuilder('f')
            ->leftJoin('f.files', 'file')
            ->leftJoin('f.children', 'child')
            ->andWhere('file.id IS NULL')
            ->andWhere('child.id IS NULL')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 查找在指定日期范围内创建的文件夹
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.createTime >= :startDate')
            ->andWhere('f.createTime <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 获取所有文件夹，按创建时间排序
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findAllOrderByCreateTime(): array
    {
        $result = $this->createQueryBuilder('f')
            ->orderBy('f.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 获取文件夹树结构（根文件夹及其子文件夹）
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findFolderTree(?Folder $parent = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.children', 'c')
            ->addSelect('c')
            ->andWhere('f.isActive = true')
        ;

        if (null === $parent) {
            $qb->andWhere('f.parent IS NULL');
        } else {
            $qb->andWhere('f.parent = :parent')
                ->setParameter('parent', $parent)
            ;
        }

        $result = $qb->orderBy('f.title', 'ASC')
            ->addOrderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 根据路径查找文件夹
     */
    public function findByPath(string $path): ?Folder
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.path = :path')
            ->setParameter('path', $path)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof Folder ? $result : null;
    }

    /**
     * 保存文件夹实体
     */
    public function save(Folder $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除文件夹实体
     */
    public function remove(Folder $entity, bool $flush = true): void
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
     * 根据名称模式查找文件夹（别名方法）
     *
     * @return Folder[]
     */
    public function findByNamePattern(string $pattern): array
    {
        return $this->findByTitlePattern($pattern);
    }

    /**
     * 查找根文件夹（没有父级的文件夹）
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findRootFolders(): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.parent IS NULL')
            ->andWhere('f.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('f.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 根据父文件夹查找子文件夹
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findChildrenByParent(Folder $parent): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.parent = :parent')
            ->andWhere('f.isActive = :isActive')
            ->setParameter('parent', $parent)
            ->setParameter('isActive', true)
            ->orderBy('f.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 根据用户查找文件夹
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findByUser(?UserInterface $user): array
    {
        $qb = $this->createQueryBuilder('f')
            ->andWhere('f.isActive = :isActive')
            ->setParameter('isActive', true)
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

        /** @var Folder[] $result */
        return $result;
    }

    /**
     * 查找公共文件夹
     *
     * @return Folder[]
     * @phpstan-return array<Folder>
     */
    public function findPublicFolders(): array
    {
        $result = $this->createQueryBuilder('f')
            ->andWhere('f.isPublic = :isPublic')
            ->andWhere('f.isActive = :isActive')
            ->setParameter('isPublic', true)
            ->setParameter('isActive', true)
            ->orderBy('f.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        assert(is_array($result));

        /** @var Folder[] $result */
        return $result;
    }
}
