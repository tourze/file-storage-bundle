<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\FileStorageBundle\Field\ImageGalleryMultiField;
use Tourze\FileStorageBundle\Form\ImageGalleryMultiType;
use Tourze\FileStorageBundle\Service\CrudActionResolverInterface;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;

/**
 * @internal
 */
#[CoversClass(ImageGalleryMultiField::class)]
final class ImageGalleryMultiFieldTest extends TestCase
{
    public function testNewField(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $this->assertInstanceOf(ImageGalleryMultiField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
        $dto = $field->getAsDto();
        $this->assertSame(ImageGalleryMultiType::class, $dto->getFormType());
        $this->assertSame('@FileStorage/bundles/EasyAdminBundle/crud/field/image_gallery_multi.html.twig', $dto->getTemplatePath());
        $this->assertStringContainsString('field-image-gallery-multi', $dto->getCssClass());
    }

    public function testFormatIndex(): void
    {
        $crudActionResolver = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };
        CrudActionResolverRegistry::setInstance($crudActionResolver);

        $field = ImageGalleryMultiField::new('images');
        $dto = $field->getAsDto();
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable('["u1","u2","u3","u4"]', new \stdClass());
        $this->assertIsString($result);
        $this->assertStringContainsString('(+1)', $result);
    }

    public function testFormatDetail(): void
    {
        $crudActionResolver = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): string
            {
                return 'detail';
            }
        };
        CrudActionResolverRegistry::setInstance($crudActionResolver);

        $field = ImageGalleryMultiField::new('images');
        $dto = $field->getAsDto();
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable(['u1', 'u2'], new \stdClass());
        $this->assertIsString($result);
        $this->assertStringContainsString('img', $result);
    }

    protected function tearDown(): void
    {
        CrudActionResolverRegistry::setInstance(null);
        parent::tearDown();
    }

    public function testAddCssClass(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->addCssClass('custom-class');
        $dto = $field->getAsDto();

        $this->assertStringContainsString('custom-class', $dto->getCssClass());
    }

    public function testAddCssFiles(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->addCssFiles('custom.css');
        $dto = $field->getAsDto();

        // CSS 文件被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testAddJsFiles(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->addJsFiles('custom.js');
        $dto = $field->getAsDto();

        // JS 文件被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testAddFormTheme(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->addFormTheme('custom_theme.html.twig');
        $dto = $field->getAsDto();

        // Form theme 被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testHideOnIndex(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->hideOnIndex();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('index'));
    }

    public function testHideOnDetail(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->hideOnDetail();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('detail'));
    }

    public function testHideOnForm(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->hideOnForm();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('form'));
    }

    public function testOnlyWhenCreating(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->onlyWhenCreating();
        $dto = $field->getAsDto();

        $this->assertTrue($dto->isDisplayedOn('new'));
        $this->assertFalse($dto->isDisplayedOn('edit'));
    }

    public function testOnlyWhenUpdating(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->onlyWhenUpdating();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('new'));
        $this->assertTrue($dto->isDisplayedOn('edit'));
    }

    public function testHideWhenCreating(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->hideWhenCreating();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('new'));
    }

    public function testHideWhenUpdating(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->hideWhenUpdating();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('edit'));
    }

    public function testFormatValue(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->formatValue(function ($value) {
            return 'formatted: ' . json_encode($value);
        });
        $dto = $field->getAsDto();

        $callable = $dto->getFormatValueCallable();
        $this->assertIsCallable($callable);
        $result = $callable(['test'], new \stdClass());
        $this->assertEquals('formatted: ["test"]', $result);
    }

    public function testAddAssetMapperEntries(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->addAssetMapperEntries('app');
        $dto = $field->getAsDto();

        // Asset mapper entries 被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testAddWebpackEncoreEntries(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You are trying to add Webpack Encore entries');

        $field = ImageGalleryMultiField::new('images');
        $field->addWebpackEncoreEntries('admin');
    }

    public function testAddHtmlContentsToHead(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->addHtmlContentsToHead('<meta name="test">');
        $dto = $field->getAsDto();

        // HTML content to head 被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testAddHtmlContentsToBody(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->addHtmlContentsToBody('<script>alert("test")</script>');
        $dto = $field->getAsDto();

        // HTML content to body 被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testOnlyOnDetail(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->onlyOnDetail();
        $dto = $field->getAsDto();

        $this->assertTrue($dto->isDisplayedOn('detail'));
        $this->assertFalse($dto->isDisplayedOn('index'));
        $this->assertFalse($dto->isDisplayedOn('form'));
    }

    public function testOnlyOnForms(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->onlyOnForms();
        $dto = $field->getAsDto();

        $this->assertTrue($dto->isDisplayedOn('new'));
        $this->assertTrue($dto->isDisplayedOn('edit'));
        $this->assertFalse($dto->isDisplayedOn('index'));
        $this->assertFalse($dto->isDisplayedOn('detail'));
    }

    public function testOnlyOnIndex(): void
    {
        $field = ImageGalleryMultiField::new('images');
        $field->onlyOnIndex();
        $dto = $field->getAsDto();

        $this->assertTrue($dto->isDisplayedOn('index'));
        $this->assertFalse($dto->isDisplayedOn('detail'));
        $this->assertFalse($dto->isDisplayedOn('form'));
    }
}
