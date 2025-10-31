<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Field;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\FileStorageBundle\Exception\FieldParameterException;
use Tourze\FileStorageBundle\Field\TinyMceEditorWithMediaPickerField;

/**
 * @internal
 */
#[CoversClass(TinyMceEditorWithMediaPickerField::class)]
final class TinyMceEditorWithMediaPickerFieldTest extends TestCase
{
    public function testNew(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');

        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $field);
    }

    public function testNewWithLabel(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content', '内容');

        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $field);
    }

    public function testSetNumOfRows(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $field->setNumOfRows(8);

        $dto = $field->getAsDto();
        $this->assertSame(8, $dto->getCustomOption(TinyMceEditorWithMediaPickerField::OPTION_NUM_OF_ROWS));
    }

    public function testSetNumOfRowsWithInvalidValue(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');

        $this->expectException(FieldParameterException::class);
        $this->expectExceptionMessage('The argument of the "Tourze\FileStorageBundle\Field\TinyMceEditorWithMediaPickerField::setNumOfRows()" method must be 1 or higher (0 given).');

        $field->setNumOfRows(0);
    }

    public function testSetTinyMceConfig(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $config = ['toolbar' => 'bold italic', 'plugins' => 'code'];

        $field->setTinyMceConfig($config);

        $dto = $field->getAsDto();
        $this->assertSame($config, $dto->getCustomOption(TinyMceEditorWithMediaPickerField::OPTION_TINYMCE_CONFIG));
    }

    public function testSetTinyMceLoaderUrl(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $url = '/assets/tinymce/tinymce.min.js';

        $field->setTinyMceLoaderUrl($url);

        $dto = $field->getAsDto();
        $this->assertSame($url, $dto->getCustomOption(TinyMceEditorWithMediaPickerField::OPTION_TINYMCE_LOADER_URL));
    }

    public function testFluentInterfaceMethods(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');

        $result = $field
            ->hideWhenCreating()
            ->hideWhenUpdating()
            ->onlyOnDetail()
            ->onlyOnForms()
        ;

        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testOnlyOnDetail(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->onlyOnDetail();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testOnlyOnForms(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->onlyOnForms();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testOnlyOnIndex(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->onlyOnIndex();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testOnlyWhenCreating(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->onlyWhenCreating();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testOnlyWhenUpdating(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->onlyWhenUpdating();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testHideOnDetail(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->hideOnDetail();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testHideOnForm(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->hideOnForm();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testHideOnIndex(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->hideOnIndex();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testHideWhenCreating(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->hideWhenCreating();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testHideWhenUpdating(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->hideWhenUpdating();
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testFormatValue(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');

        $result = $field->formatValue(function ($value) {
            return htmlspecialchars((string) $value, ENT_QUOTES);
        });

        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testAddCssClass(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->addCssClass('custom-editor');
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testAddCssFiles(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->addCssFiles('/css/custom-editor.css');
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testAddJsFiles(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->addJsFiles('/js/custom-editor.js');
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testAddFormTheme(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->addFormTheme('form/custom-theme.html.twig');
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testAddHtmlContentsToHead(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->addHtmlContentsToHead('<meta name="editor-config" content="test">');
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testAddHtmlContentsToBody(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');
        $result = $field->addHtmlContentsToBody('<div class="editor-wrapper">');
        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testAddAssetMapperEntries(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');

        $result = $field->addAssetMapperEntries('tinymce', 'image-picker');

        $this->assertInstanceOf(TinyMceEditorWithMediaPickerField::class, $result);
        $this->assertSame($field, $result);
    }

    public function testAddWebpackEncoreEntries(): void
    {
        $field = TinyMceEditorWithMediaPickerField::new('content');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You are trying to add Webpack Encore entries in a field but Webpack Encore is not installed in your project');

        $field->addWebpackEncoreEntries('app', 'editor');
    }
}
