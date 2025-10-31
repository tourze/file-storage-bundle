<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\TextEditorType;
use Symfony\Contracts\Translation\TranslatableInterface;
use Tourze\FileStorageBundle\Exception\FieldParameterException;

/**
 * 基于 TinyMCE 的富文本字段，内置媒体库选择器（图片/视频）。
 */
final class TinyMceEditorWithMediaPickerField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_NUM_OF_ROWS = 'numOfRows';
    public const OPTION_TINYMCE_CONFIG = 'tinyMceEditorConfig';
    public const OPTION_TINYMCE_LOADER_URL = 'tinyMceLoaderUrl';

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text_editor')
            ->setFormType(TextEditorType::class)
            ->setDefaultColumns('col-md-9 col-xxl-7')
            ->addFormTheme('@FileStorage/form/tiny_editor_theme.html.twig')
            ->addJsFiles('/bundles/filestorage/js/tinymce-image-picker.js')
            ->addCssFiles('/bundles/filestorage/css/tinymce-image-picker.css')
            ->setCustomOption(self::OPTION_NUM_OF_ROWS, null)
            ->setCustomOption(self::OPTION_TINYMCE_CONFIG, null)
            ->setCustomOption(self::OPTION_TINYMCE_LOADER_URL, null)
        ;
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
    public function setTinyMceConfig(array $config): void
    {
        $this->setCustomOption(self::OPTION_TINYMCE_CONFIG, $config);
    }

    public function setTinyMceLoaderUrl(string $loaderUrl): void
    {
        $this->setCustomOption(self::OPTION_TINYMCE_LOADER_URL, $loaderUrl);
    }
}
