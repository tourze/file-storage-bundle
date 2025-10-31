<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\EasyAdminImagePreviewFieldBundle\Field\ImagePreviewField;
use Tourze\FileStorageBundle\Entity\File;

#[AdminCrud(routePath: '/file-storage/file', routeName: 'file_storage_file')]
final class FileCrudController extends AbstractCrudController
{
    public function __construct()
    {
    }

    public static function getEntityFqcn(): string
    {
        return File::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('素材管理')
            ->setEntityLabelInPlural('素材管理')
            ->setPageTitle('index', '素材列表')
            ->setPageTitle('new', '上传素材')
            ->setPageTitle('edit', '编辑素材')
            ->setPageTitle('detail', '素材详情')
            ->setHelp('index', '管理系统中上传的所有素材文件')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'originFileName', 'fileName', 'url', 'fileKey', 'md5File'])
            ->showEntityActionsInlined()
            ->overrideTemplate('crud/index', '@FileStorage/admin/file_crud_index.html.twig')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield AssociationField::new('folder', '文件目录')
            ->formatValue(static function ($value) {
                return $value && is_object($value) && method_exists($value, 'getTitle') ? $value->getTitle() : '未分类';
            })
        ;

        yield ImagePreviewField::new('publicUrl', 'URL/预览')
            ->hideOnForm()
        ;

        yield IntegerField::new('year', '年')
            ->onlyOnIndex()
        ;

        yield IntegerField::new('month', '月')
            ->onlyOnIndex()
        ;

        yield TextField::new('originFileName', '原始文件名')
            ->setHelp('用户上传时的原始文件名')
        ;

        yield TextField::new('fileName', '生成文件名')
            ->setHelp('系统生成的唯一文件名')
            ->onlyOnDetail()
        ;

        yield IntegerField::new('size', '大小')
            ->formatValue(function ($value) {
                return $this->formatFileSize(is_int($value) ? $value : null);
            })
            ->hideOnForm()
        ;

        yield TextField::new('ext', '后缀')
            ->onlyOnIndex()
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
        ;

        if (Crud::PAGE_DETAIL === $pageName) {
            yield TextField::new('fileKey', '文件KEY')
                ->setHelp('完整文件KEY')
            ;

            yield IntegerField::new('width', '宽度')
                ->setHelp('图片宽度（像素）')
            ;

            yield IntegerField::new('height', '高度')
                ->setHelp('图片高度（像素）')
            ;

            yield IntegerField::new('viewCount', '访问次数')
                ->formatValue(function ($value) {
                    return $value ?? 0;
                })
            ;

            yield IntegerField::new('downloadCount', '下载次数')
                ->formatValue(function ($value) {
                    return $value ?? 0;
                })
            ;

            yield BooleanField::new('valid', '有效')
                ->renderAsSwitch(false)
            ;

            yield TextField::new('md5File', 'MD5值');

            yield TextField::new('createdBy', '创建人')
                ->hideOnForm()
            ;

            yield TextField::new('createdFromIp', '创建 IP');
        }
    }

    private function formatFileSize(?int $bytes): string
    {
        if (null === $bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            ++$i;
        }

        return sprintf('%.2f %s', $size, $units[$i]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('originFileName', '原始文件名'))
            ->add(TextFilter::new('fileName', '生成文件名'))
            ->add(DateTimeFilter::new('createTime', '上传时间'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW)
        ;
    }
}
