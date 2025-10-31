<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\FileStorageBundle\Field\MediaGalleryField;
use Tourze\FileStorageBundle\Form\MediaGalleryType;
use Tourze\FileStorageBundle\Service\CrudActionResolverInterface;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;

/**
 * @internal
 */
#[CoversClass(MediaGalleryField::class)]
final class MediaGalleryFieldTest extends TestCase
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

    public function testNewCreatesFieldInstanceWithCustomLabel(): void
    {
        $field = MediaGalleryField::new('gallery', 'Media Gallery');

        self::assertEquals('Media Gallery', $field->getAsDto()->getLabel());
        self::assertEquals('gallery', $field->getAsDto()->getProperty());
    }

    public function testNewFieldWithLabel(): void
    {
        $field = MediaGalleryField::new('gallery', '媒体库');

        self::assertEquals('媒体库', $field->getAsDto()->getLabel());
    }

    public function testNewFieldWithoutLabel(): void
    {
        $field = MediaGalleryField::new('gallery');

        self::assertNull($field->getAsDto()->getLabel());
    }

    public function testFieldProperties(): void
    {
        $field = MediaGalleryField::new('gallery', '媒体文件');
        $dto = $field->getAsDto();

        self::assertEquals('gallery', $dto->getProperty());
        self::assertEquals('媒体文件', $dto->getLabel());
        self::assertEquals(
            '@FileStorage/bundles/EasyAdminBundle/crud/field/media_gallery.html.twig',
            $dto->getTemplatePath()
        );
        self::assertEquals(MediaGalleryType::class, $dto->getFormType());
    }

    public function testFieldCssClass(): void
    {
        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        self::assertStringContainsString('field-media-gallery', $dto->getCssClass());
    }

    public function testFormatValueWithEmptyArrayValue(): void
    {
        $this->setCurrentAction('index');

        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $formatValueCallable = $dto->getFormatValueCallable();
        self::assertIsCallable($formatValueCallable);
        $result = $formatValueCallable([], $entity);
        self::assertIsString($result);

        self::assertStringContainsString('—', $result);
    }

    public function testFormatValueWithSingleImageUrl(): void
    {
        $this->setCurrentAction('index');

        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $imageUrl = 'https://example.com/image.jpg';
        $formatValueCallable = $dto->getFormatValueCallable();
        self::assertIsCallable($formatValueCallable);
        $result = $formatValueCallable($imageUrl, $entity);
        self::assertIsString($result);

        self::assertStringContainsString($imageUrl, $result);
        self::assertStringContainsString('showMediaModal', $result);
    }

    public function testFormatValueWithMultipleMedia(): void
    {
        $this->setCurrentAction('index');

        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $mediaList = [
            ['url' => 'https://example.com/image1.jpg', 'type' => 'image'],
            ['url' => 'https://example.com/video1.mp4', 'type' => 'video'],
            ['url' => 'https://example.com/image2.jpg', 'type' => 'image'],
            ['url' => 'https://example.com/image3.jpg', 'type' => 'image'],
        ];
        $formatValueCallable = $dto->getFormatValueCallable();
        self::assertIsCallable($formatValueCallable);
        $result = $formatValueCallable($mediaList, $entity);
        self::assertIsString($result);

        // 应该包含计数 (+1)，因为有4个媒体，显示前3个
        self::assertStringContainsString('(+1)', $result);
    }

    public function testFormatValueDetailPageWithImages(): void
    {
        $this->setCurrentAction('detail');

        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $mediaList = [
            ['url' => 'https://example.com/image1.jpg', 'type' => 'image'],
            ['url' => 'https://example.com/video1.mp4', 'type' => 'video'],
        ];
        $formatValueCallable = $dto->getFormatValueCallable();
        self::assertIsCallable($formatValueCallable);
        $result = $formatValueCallable($mediaList, $entity);
        self::assertIsString($result);

        self::assertStringContainsString('https://example.com/image1.jpg', $result);
        self::assertStringContainsString('https://example.com/video1.mp4', $result);
        self::assertStringContainsString('视频', $result);
    }

    public function testFormatValueDetailPageWithEmptyValue(): void
    {
        $this->setCurrentAction('detail');

        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $formatValueCallable = $dto->getFormatValueCallable();
        self::assertIsCallable($formatValueCallable);
        $result = $formatValueCallable(null, $entity);
        self::assertIsString($result);

        self::assertEquals('无媒体文件', $result);
    }

    public function testFormatValueFormPage(): void
    {
        $this->setCurrentAction('edit');

        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $formatValueCallable = $dto->getFormatValueCallable();
        self::assertIsCallable($formatValueCallable);
        $result = $formatValueCallable('test', $entity);
        self::assertIsString($result);

        // 表单页格式化返回空字符串
        self::assertEquals('', $result);
    }

    public function testFormatValueNewPage(): void
    {
        $this->setCurrentAction('new');

        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $formatValueCallable = $dto->getFormatValueCallable();
        self::assertIsCallable($formatValueCallable);
        $result = $formatValueCallable(null, $entity);
        self::assertIsString($result);

        // 新建页格式化返回空字符串
        self::assertEquals('', $result);
    }

    public function testFormatValueWhenResolverNotAvailable(): void
    {
        // 清理注册表，模拟服务不可用的情况
        CrudActionResolverRegistry::setInstance(null);

        $field = MediaGalleryField::new('gallery');
        $dto = $field->getAsDto();

        $entity = new \stdClass();
        $mediaUrl = 'https://example.com/image.jpg';
        $formatValueCallable = $dto->getFormatValueCallable();
        self::assertIsCallable($formatValueCallable);
        $result = $formatValueCallable($mediaUrl, $entity);
        self::assertIsString($result);

        // 当服务不可用时，应该默认显示列表页格式
        self::assertStringContainsString('showMediaModal', $result);
        self::assertStringContainsString($mediaUrl, $result);
    }

    public function testAddCssClass(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->addCssClass('custom-class');
        $dto = $field->getAsDto();

        self::assertStringContainsString('custom-class', $dto->getCssClass());
    }

    public function testAddCssFiles(): void
    {
        $field = MediaGalleryField::new('gallery');
        $initialCssClass = $field->getAsDto()->getCssClass();
        $field->addCssFiles('custom.css');

        // 验证方法调用成功（通过验证字段状态未被破坏）
        self::assertStringContainsString('field-media-gallery', $field->getAsDto()->getCssClass());
    }

    public function testAddJsFiles(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->addJsFiles('custom.js');

        // 验证方法调用成功（通过验证字段状态未被破坏）
        self::assertStringContainsString('field-media-gallery', $field->getAsDto()->getCssClass());
    }

    public function testAddFormTheme(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->addFormTheme('custom_theme.html.twig');

        // 验证方法调用成功（通过验证字段状态未被破坏）
        self::assertStringContainsString('field-media-gallery', $field->getAsDto()->getCssClass());
    }

    public function testHideOnIndex(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->hideOnIndex();
        $dto = $field->getAsDto();

        self::assertFalse($dto->isDisplayedOn('index'));
    }

    public function testHideOnDetail(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->hideOnDetail();
        $dto = $field->getAsDto();

        self::assertFalse($dto->isDisplayedOn('detail'));
    }

    public function testHideOnForm(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->hideOnForm();
        $dto = $field->getAsDto();

        self::assertFalse($dto->isDisplayedOn('form'));
    }

    public function testOnlyWhenCreating(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->onlyWhenCreating();
        $dto = $field->getAsDto();

        self::assertTrue($dto->isDisplayedOn('new'));
        self::assertFalse($dto->isDisplayedOn('edit'));
    }

    public function testOnlyWhenUpdating(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->onlyWhenUpdating();
        $dto = $field->getAsDto();

        self::assertFalse($dto->isDisplayedOn('new'));
        self::assertTrue($dto->isDisplayedOn('edit'));
    }

    public function testHideWhenCreating(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->hideWhenCreating();
        $dto = $field->getAsDto();

        self::assertFalse($dto->isDisplayedOn('new'));
    }

    public function testHideWhenUpdating(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->hideWhenUpdating();
        $dto = $field->getAsDto();

        self::assertFalse($dto->isDisplayedOn('edit'));
    }

    public function testFormatValue(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->formatValue(function ($value) {
            return 'formatted: ' . (is_string($value) ? $value : '');
        });
        $dto = $field->getAsDto();

        $callable = $dto->getFormatValueCallable();
        self::assertIsCallable($callable);
        $result = $callable('test', new \stdClass());
        self::assertEquals('formatted: test', $result);
    }

    public function testAddAssetMapperEntries(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->addAssetMapperEntries('app');

        // 验证方法调用成功（通过验证字段状态未被破坏）
        self::assertStringContainsString('field-media-gallery', $field->getAsDto()->getCssClass());
    }

    public function testAddWebpackEncoreEntries(): void
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('You are trying to add Webpack Encore entries');

        $field = MediaGalleryField::new('gallery');
        $field->addWebpackEncoreEntries('admin');
    }

    public function testAddHtmlContentsToHead(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->addHtmlContentsToHead('<meta name="test">');

        // 验证方法调用成功（通过验证字段状态未被破坏）
        self::assertStringContainsString('field-media-gallery', $field->getAsDto()->getCssClass());
    }

    public function testAddHtmlContentsToBody(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->addHtmlContentsToBody('<script>alert("test")</script>');

        // 验证方法调用成功（通过验证字段状态未被破坏）
        self::assertStringContainsString('field-media-gallery', $field->getAsDto()->getCssClass());
    }

    public function testOnlyOnDetail(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->onlyOnDetail();
        $dto = $field->getAsDto();

        self::assertTrue($dto->isDisplayedOn('detail'));
        self::assertFalse($dto->isDisplayedOn('index'));
        self::assertFalse($dto->isDisplayedOn('form'));
    }

    public function testOnlyOnForms(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->onlyOnForms();
        $dto = $field->getAsDto();

        self::assertTrue($dto->isDisplayedOn('new'));
        self::assertTrue($dto->isDisplayedOn('edit'));
        self::assertFalse($dto->isDisplayedOn('index'));
        self::assertFalse($dto->isDisplayedOn('detail'));
    }

    public function testOnlyOnIndex(): void
    {
        $field = MediaGalleryField::new('gallery');
        $field->onlyOnIndex();
        $dto = $field->getAsDto();

        self::assertTrue($dto->isDisplayedOn('index'));
        self::assertFalse($dto->isDisplayedOn('detail'));
        self::assertFalse($dto->isDisplayedOn('form'));
    }
}
