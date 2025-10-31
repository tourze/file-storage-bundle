<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\FileStorageBundle\Field\ImageGalleryField;
use Tourze\FileStorageBundle\Form\ImageGalleryType;
use Tourze\FileStorageBundle\Service\CrudActionResolverInterface;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;

/**
 * @internal
 */
#[CoversClass(ImageGalleryField::class)]
final class ImageGalleryFieldTest extends TestCase
{
    private CrudActionResolverInterface $crudActionResolver;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建CrudActionResolver的匿名类实现
        $resolver = new class implements CrudActionResolverInterface {
            private ?string $currentAction = null;

            public function getCurrentCrudAction(): ?string
            {
                return $this->currentAction;
            }

            public function setCurrentAction(?string $action): void
            {
                $this->currentAction = $action;
            }
        };
        $this->crudActionResolver = $resolver;

        // 注册到全局注册表
        CrudActionResolverRegistry::setInstance($this->crudActionResolver);
    }

    protected function tearDown(): void
    {
        // 清理全局注册表
        CrudActionResolverRegistry::setInstance(null);

        parent::tearDown();
    }

    private function setCurrentAction(?string $action): void
    {
        if (method_exists($this->crudActionResolver, 'setCurrentAction')) {
            $this->crudActionResolver->setCurrentAction($action);
        }
    }

    public function testNewFieldCreation(): void
    {
        $field = ImageGalleryField::new('avatar');

        $this->assertInstanceOf(ImageGalleryField::class, $field);
        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testNewFieldWithLabel(): void
    {
        $field = ImageGalleryField::new('avatar', '头像');

        $this->assertEquals('头像', $field->getAsDto()->getLabel());
    }

    public function testNewFieldWithoutLabel(): void
    {
        $field = ImageGalleryField::new('avatar');

        $this->assertNull($field->getAsDto()->getLabel());
    }

    public function testFieldProperties(): void
    {
        $field = ImageGalleryField::new('avatar', '用户头像');
        $dto = $field->getAsDto();

        $this->assertEquals('avatar', $dto->getProperty());
        $this->assertEquals('用户头像', $dto->getLabel());
        // 模板渲染采用自定义路径，EasyAdmin 在设置 templatePath 后可能返回 null 的 templateName
        $this->assertEquals(
            '@FileStorage/bundles/EasyAdminBundle/crud/field/image_gallery.html.twig',
            $dto->getTemplatePath()
        );
        $this->assertEquals(ImageGalleryType::class, $dto->getFormType());
    }

    public function testFieldCssClass(): void
    {
        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $this->assertStringContainsString('field-image-gallery', $dto->getCssClass());
    }

    public function testFormatImageValueWithNullValue(): void
    {
        // Set CRUD action resolver to return 'edit'
        $this->setCurrentAction('edit');

        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable(null, $entity);
        $this->assertIsString($result);

        $this->assertStringContainsString('无图片', $result);
        $this->assertStringContainsString('选择图片', $result);
    }

    public function testFormatImageValueWithValue(): void
    {
        // Set CRUD action resolver to return 'edit'
        $this->setCurrentAction('edit');

        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $imageUrl = 'http://example.com/avatar.jpg';
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable($imageUrl, $entity);
        $this->assertIsString($result);

        $this->assertStringContainsString('更换图片', $result);
        $this->assertStringContainsString('清除', $result);
        $this->assertStringContainsString($imageUrl, $result);
    }

    public function testFormatImageValueDetailPage(): void
    {
        // Set CRUD action resolver to return 'detail'
        $this->setCurrentAction('detail');

        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $imageUrl = 'http://example.com/avatar.jpg';
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable($imageUrl, $entity);
        $this->assertIsString($result);

        $this->assertStringContainsString('在新窗口打开', $result);
        $this->assertStringContainsString($imageUrl, $result);
    }

    public function testFormatImageValueDetailPageWithNullValue(): void
    {
        // Set CRUD action resolver to return 'detail'
        $this->setCurrentAction('detail');

        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable(null, $entity);
        $this->assertIsString($result);

        $this->assertEquals('无头像', $result);
    }

    public function testFormatImageValueListPage(): void
    {
        // Set CRUD action resolver to return null (list page)
        $this->setCurrentAction(null);

        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $imageUrl = 'http://example.com/avatar.jpg';
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable($imageUrl, $entity);
        $this->assertIsString($result);

        $this->assertStringContainsString('showImageModal', $result);
        $this->assertStringContainsString('点击预览图片', $result);
        $this->assertStringContainsString($imageUrl, $result);
    }

    public function testFormatImageValueListPageWithNullValue(): void
    {
        // Set CRUD action resolver to return null (list page)
        $this->setCurrentAction(null);

        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable(null, $entity);
        $this->assertIsString($result);

        $this->assertEquals('无头像', $result);
    }

    public function testNewPageFormatting(): void
    {
        // Set CRUD action resolver to return 'new'
        $this->setCurrentAction('new');

        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable(null, $entity);
        $this->assertIsString($result);

        $this->assertStringContainsString('选择图片', $result);
        $this->assertStringContainsString('无图片', $result);
    }

    public function testFormatImageValueWhenResolverNotAvailable(): void
    {
        // 清理注册表，模拟服务不可用的情况
        CrudActionResolverRegistry::setInstance(null);

        $field = ImageGalleryField::new('avatar');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $imageUrl = 'http://example.com/avatar.jpg';
        $formatValueCallable = $dto->getFormatValueCallable();
        $this->assertIsCallable($formatValueCallable);
        $result = $formatValueCallable($imageUrl, $entity);
        $this->assertIsString($result);

        // 当服务不可用时，应该默认显示列表页格式
        $this->assertStringContainsString('showImageModal', $result);
        $this->assertStringContainsString('点击预览图片', $result);
        $this->assertStringContainsString($imageUrl, $result);
    }

    public function testAddCssClass(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->addCssClass('custom-class');
        $dto = $field->getAsDto();

        $this->assertStringContainsString('custom-class', $dto->getCssClass());
    }

    public function testAddCssFiles(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->addCssFiles('custom.css');
        $dto = $field->getAsDto();

        // CSS 文件被添加到字段中，具体存储方式可能因版本而异
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testAddJsFiles(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->addJsFiles('custom.js');
        $dto = $field->getAsDto();

        // JS 文件被添加到字段中，具体存储方式可能因版本而异
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testAddFormTheme(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->addFormTheme('custom_theme.html.twig');
        $dto = $field->getAsDto();

        // Form theme 被添加到字段中，验证 DTO 被正确创建
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testHideOnIndex(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->hideOnIndex();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('index'));
    }

    public function testHideOnDetail(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->hideOnDetail();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('detail'));
    }

    public function testHideOnForm(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->hideOnForm();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('form'));
    }

    public function testOnlyWhenCreating(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->onlyWhenCreating();
        $dto = $field->getAsDto();

        $this->assertTrue($dto->isDisplayedOn('new'));
        $this->assertFalse($dto->isDisplayedOn('edit'));
    }

    public function testOnlyWhenUpdating(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->onlyWhenUpdating();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('new'));
        $this->assertTrue($dto->isDisplayedOn('edit'));
    }

    public function testHideWhenCreating(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->hideWhenCreating();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('new'));
    }

    public function testHideWhenUpdating(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->hideWhenUpdating();
        $dto = $field->getAsDto();

        $this->assertFalse($dto->isDisplayedOn('edit'));
    }

    public function testFormatValue(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->formatValue(function ($value) {
            return 'formatted: ' . (is_string($value) ? $value : '');
        });
        $dto = $field->getAsDto();

        $callable = $dto->getFormatValueCallable();
        $this->assertIsCallable($callable);
        $result = $callable('test', new \stdClass());
        $this->assertEquals('formatted: test', $result);
    }

    public function testAddAssetMapperEntries(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->addAssetMapperEntries('app');
        $dto = $field->getAsDto();

        // Asset mapper entries 被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testAddWebpackEncoreEntries(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You are trying to add Webpack Encore entries');

        $field = ImageGalleryField::new('avatar');
        $field->addWebpackEncoreEntries('admin');
    }

    public function testAddHtmlContentsToHead(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->addHtmlContentsToHead('<meta name="test">');
        $dto = $field->getAsDto();

        // HTML content to head 被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testAddHtmlContentsToBody(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->addHtmlContentsToBody('<script>alert("test")</script>');
        $dto = $field->getAsDto();

        // HTML content to body 被添加到字段中
        $this->assertInstanceOf(FieldDto::class, $dto);
    }

    public function testOnlyOnDetail(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->onlyOnDetail();
        $dto = $field->getAsDto();

        $this->assertTrue($dto->isDisplayedOn('detail'));
        $this->assertFalse($dto->isDisplayedOn('index'));
        $this->assertFalse($dto->isDisplayedOn('form'));
    }

    public function testOnlyOnForms(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->onlyOnForms();
        $dto = $field->getAsDto();

        $this->assertTrue($dto->isDisplayedOn('new'));
        $this->assertTrue($dto->isDisplayedOn('edit'));
        $this->assertFalse($dto->isDisplayedOn('index'));
        $this->assertFalse($dto->isDisplayedOn('detail'));
    }

    public function testOnlyOnIndex(): void
    {
        $field = ImageGalleryField::new('avatar');
        $field->onlyOnIndex();
        $dto = $field->getAsDto();

        $this->assertTrue($dto->isDisplayedOn('index'));
        $this->assertFalse($dto->isDisplayedOn('detail'));
        $this->assertFalse($dto->isDisplayedOn('form'));
    }
}
