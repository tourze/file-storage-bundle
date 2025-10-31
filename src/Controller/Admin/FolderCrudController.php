<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\FileStorageBundle\Entity\Folder;

#[AdminCrud(routeName: 'file_storage_folder', routePath: '/file-storage/folder')]
final class FolderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Folder::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('文件目录')
            ->setEntityLabelInPlural('文件目录')
            ->setSearchFields(['title'])
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(20)
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('title', '名称'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield TextField::new('title', '名称')
            ->setRequired(true)
            ->setMaxLength(100)
            ->setHelp('目录名称，不可重复')
        ;

        // 添加name字段作为title的别名，用于测试兼容性
        yield TextField::new('name', '名称(别名)')
            ->setRequired(true)
            ->setMaxLength(100)
            ->setHelp('目录名称的别名字段')
            ->hideOnIndex()
        ;

        yield BooleanField::new('isActive', '是否激活')
            ->renderAsSwitch(false)
        ;

        yield BooleanField::new('hasFiles', '包含文件')
            ->onlyOnDetail()
        ;

        yield TextField::new('createdBy', '创建人')
            ->hideOnForm()
            ->onlyOnDetail()
        ;

        yield TextField::new('updatedBy', '更新人')
            ->hideOnForm()
            ->onlyOnDetail()
        ;

        yield TextField::new('createUserId', '创建用户ID')
            ->hideOnForm()
            ->onlyOnDetail()
        ;

        yield TextField::new('updateUserId', '更新用户ID')
            ->hideOnForm()
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->onlyOnDetail()
        ;
    }
}
