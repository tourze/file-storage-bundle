<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Field;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\FileStorageBundle\Exception\FieldParameterException;
use Tourze\FileStorageBundle\Field\TextEditorWithImagePickerField;

/**
 * @internal
 */
#[CoversClass(TextEditorWithImagePickerField::class)]
final class TextEditorWithImagePickerFieldTest extends TestCase
{
    public function testNew(): void
    {
        $field = TextEditorWithImagePickerField::new('content');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $field);
    }

    public function testNewWithLabel(): void
    {
        $field = TextEditorWithImagePickerField::new('content', 'Content Label');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $field);
    }

    public function testSetNumOfRows(): void
    {
        $field = TextEditorWithImagePickerField::new('content');

        // 主要验证方法调用不会抛出异常
        $field->setNumOfRows(5);

        // 验证设置的值被正确存储
        $dto = $field->getAsDto();
        $this->assertEquals(5, $dto->getCustomOption(TextEditorWithImagePickerField::OPTION_NUM_OF_ROWS));
    }

    public function testSetNumOfRowsWithZeroThrowsException(): void
    {
        $field = TextEditorWithImagePickerField::new('content');

        $this->expectException(FieldParameterException::class);
        $this->expectExceptionMessage('The argument of the "Tourze\FileStorageBundle\Field\TextEditorWithImagePickerField::setNumOfRows()" method must be 1 or higher (0 given).');

        $field->setNumOfRows(0);
    }

    public function testSetNumOfRowsWithNegativeThrowsException(): void
    {
        $field = TextEditorWithImagePickerField::new('content');

        $this->expectException(FieldParameterException::class);
        $this->expectExceptionMessage('The argument of the "Tourze\FileStorageBundle\Field\TextEditorWithImagePickerField::setNumOfRows()" method must be 1 or higher (-1 given).');

        $field->setNumOfRows(-1);
    }

    public function testSetTrixEditorConfig(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $config = ['toolbar' => 'bold'];

        // 主要验证方法调用不会抛出异常
        $field->setTrixEditorConfig($config);

        // 验证设置的配置被正确存储
        $dto = $field->getAsDto();
        $this->assertEquals($config, $dto->getCustomOption(TextEditorWithImagePickerField::OPTION_TRIX_EDITOR_CONFIG));
    }

    public function testSetTrixEditorConfigWithEmptyArray(): void
    {
        $field = TextEditorWithImagePickerField::new('content');

        // 主要验证方法调用不会抛出异常
        $field->setTrixEditorConfig([]);

        // 验证空配置被正确存储
        $dto = $field->getAsDto();
        $this->assertEquals([], $dto->getCustomOption(TextEditorWithImagePickerField::OPTION_TRIX_EDITOR_CONFIG));
    }

    public function testHideWhenCreating(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->hideWhenCreating();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'hideWhenCreating should return the same instance for fluent interface');
    }

    public function testHideWhenUpdating(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->hideWhenUpdating();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'hideWhenUpdating should return the same instance for fluent interface');
    }

    public function testOnlyOnDetail(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->onlyOnDetail();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'onlyOnDetail should return the same instance for fluent interface');
    }

    public function testOnlyOnForms(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->onlyOnForms();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'onlyOnForms should return the same instance for fluent interface');
    }

    public function testOnlyOnIndex(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->onlyOnIndex();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'onlyOnIndex should return the same instance for fluent interface');
    }

    public function testOnlyWhenCreating(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->onlyWhenCreating();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'onlyWhenCreating should return the same instance for fluent interface');
    }

    public function testOnlyWhenUpdating(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->onlyWhenUpdating();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'onlyWhenUpdating should return the same instance for fluent interface');
    }

    public function testFluentInterface(): void
    {
        $field = TextEditorWithImagePickerField::new('content', 'Content Label');

        // setNumOfRows and setTrixEditorConfig return void, so we can't chain them
        $field->setNumOfRows(10);
        $field->setTrixEditorConfig(['toolbar' => 'minimal']);

        // But these methods return the field instance and can be chained
        $result = $field
            ->hideWhenCreating()
            ->onlyOnForms()
        ;

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'Trait methods should return the same instance for fluent interface');

        // 验证设置的值
        $dto = $field->getAsDto();
        $this->assertEquals(10, $dto->getCustomOption(TextEditorWithImagePickerField::OPTION_NUM_OF_ROWS));
        $this->assertEquals(['toolbar' => 'minimal'], $dto->getCustomOption(TextEditorWithImagePickerField::OPTION_TRIX_EDITOR_CONFIG));
    }

    public function testAddCssFiles(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->addCssFiles('test.css');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'addCssFiles should return the same instance for fluent interface');
    }

    public function testAddFormTheme(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->addFormTheme('test_theme.html.twig');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'addFormTheme should return the same instance for fluent interface');
    }

    public function testAddHtmlContentsToBody(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->addHtmlContentsToBody('<script>console.log("body");</script>');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'addHtmlContentsToBody should return the same instance for fluent interface');
    }

    public function testAddHtmlContentsToHead(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->addHtmlContentsToHead('<meta name="test" content="test">');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'addHtmlContentsToHead should return the same instance for fluent interface');
    }

    public function testAddJsFiles(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->addJsFiles('test.js');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'addJsFiles should return the same instance for fluent interface');
    }

    public function testAddWebpackEncoreEntries(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You are trying to add Webpack Encore entries');

        $field = TextEditorWithImagePickerField::new('content');
        $field->addWebpackEncoreEntries('test-entry');
    }

    public function testFormatValue(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->formatValue(fn ($value) => strtoupper((string) $value));

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'formatValue should return the same instance for fluent interface');
    }

    public function testHideOnDetail(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->hideOnDetail();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'hideOnDetail should return the same instance for fluent interface');
    }

    public function testHideOnForm(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->hideOnForm();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'hideOnForm should return the same instance for fluent interface');
    }

    public function testHideOnIndex(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->hideOnIndex();

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'hideOnIndex should return the same instance for fluent interface');
    }

    public function testAddAssetMapperEntries(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->addAssetMapperEntries('test-entry');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'addAssetMapperEntries should return the same instance for fluent interface');
    }

    public function testAddCssClass(): void
    {
        $field = TextEditorWithImagePickerField::new('content');
        $result = $field->addCssClass('test-class');

        $this->assertInstanceOf(TextEditorWithImagePickerField::class, $result);
        $this->assertSame($field, $result, 'addCssClass should return the same instance for fluent interface');
    }
}
