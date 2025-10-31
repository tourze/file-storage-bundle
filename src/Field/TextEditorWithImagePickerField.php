<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\TextEditorType;
use Symfony\Contracts\Translation\TranslatableInterface;
use Tourze\FileStorageBundle\Exception\FieldParameterException;

/**
 * 通用富文本字段（集成图片选择器）
 *
 * - 基于 EasyAdmin 自带的 TextEditorField
 * - 自动注入 Trix 与图片选择器所需 JS/CSS 资源
 * - 兼容 setNumOfRows / setTrixEditorConfig 等常用 API
 */
final class TextEditorWithImagePickerField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_NUM_OF_ROWS = 'numOfRows';
    public const OPTION_TRIX_EDITOR_CONFIG = 'trixEditorConfig';

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        // 与 EasyAdmin TextEditorField 对齐的基础设置
        $self = (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text_editor')
            ->setFormType(TextEditorType::class)
            ->addCssFiles(Asset::fromEasyAdminAssetPackage('field-text-editor.css')->onlyOnForms())
            ->addJsFiles(Asset::fromEasyAdminAssetPackage('field-text-editor.js')->onlyOnForms())
            ->setDefaultColumns('col-md-9 col-xxl-7')
            ->setCustomOption(self::OPTION_NUM_OF_ROWS, null)
            ->setCustomOption(self::OPTION_TRIX_EDITOR_CONFIG, null)
        ;

        // 自动注入图片选择器资源（来源于本 bundle 的公开资源）
        $self
            ->addJsFiles('/bundles/filestorage/js/trix-image-picker.js')
            ->addCssFiles('/bundles/filestorage/css/trix-image-picker.css')
        ;

        return $self;
    }

    public function setNumOfRows(int $rows): void
    {
        if ($rows < 1) {
            throw new FieldParameterException(sprintf('The argument of the "%s()" method must be 1 or higher (%d given).', __METHOD__, $rows));
        }

        $this->setCustomOption(self::OPTION_NUM_OF_ROWS, $rows);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function setTrixEditorConfig(array $config): void
    {
        $this->setCustomOption(self::OPTION_TRIX_EDITOR_CONFIG, $config);
    }
}
