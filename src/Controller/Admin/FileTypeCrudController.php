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
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\FileStorageBundle\Entity\FileType;

#[AdminCrud(routePath: '/file-storage/file-type', routeName: 'file_storage_file_type')]
final class FileTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FileType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('文件类型')
            ->setEntityLabelInPlural('文件类型')
            ->setPageTitle('index', '文件类型列表')
            ->setPageTitle('new', '新建文件类型')
            ->setPageTitle('edit', '编辑文件类型')
            ->setPageTitle('detail', '文件类型详情')
            ->setHelp('index', '配置系统允许上传的文件类型、大小限制和权限控制')
            ->setDefaultSort(['displayOrder' => 'ASC', 'id' => 'DESC'])
            ->setSearchFields(['id', 'name', 'mimeType', 'extension'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 基本字段
        yield IdField::new('id', 'ID')->setMaxLength(9999)->onlyOnIndex();

        yield TextField::new('name', '文件类型名称')
            ->setRequired(true)
            ->setHelp('例如：JPEG图片、PDF文档等')
        ;

        yield TextField::new('mimeType', 'MIME类型')
            ->setHelp('例如：image/jpeg、application/pdf等')
        ;

        yield TextField::new('extension', '文件扩展名')
            ->setHelp('不带点号，例如：jpg、pdf等')
        ;

        yield IntegerField::new('maxSize', '最大文件大小')
            ->setHelp('单位：字节')
            ->formatValue(function ($value) {
                return $this->formatFileSize(is_int($value) ? $value : null);
            })
        ;

        yield ChoiceField::new('uploadType', '上传类型')
            ->setChoices([
                '仅匿名' => 'anonymous',
                '仅会员' => 'member',
                '两者都可' => 'both',
            ])
            ->setHelp('控制该文件类型允许的上传用户类型')
        ;

        yield IntegerField::new('displayOrder', '显示顺序')
            ->setHelp('数值越小越靠前')
        ;

        yield BooleanField::new('isActive', '是否激活')
            ->renderAsSwitch(false)
        ;

        yield TextareaField::new('description', '描述')
            ->hideOnIndex()
        ;

        // 时间戳字段
        yield DateTimeField::new('createTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnDetail()
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnDetail()
        ;
    }

    /**
     * 格式化文件大小
     */
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
        $uploadTypeChoices = [
            '仅匿名' => 'anonymous',
            '仅会员' => 'member',
            '两者都可' => 'both',
        ];

        return $filters
            ->add(TextFilter::new('name', '文件类型名称'))
            ->add(TextFilter::new('mimeType', 'MIME类型'))
            ->add(TextFilter::new('extension', '文件扩展名'))
            ->add(ChoiceFilter::new('uploadType', '上传类型')->setChoices($uploadTypeChoices))
            ->add(BooleanFilter::new('isActive', '是否激活'))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }
}
