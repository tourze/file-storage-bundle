<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\FileStorageBundle\Form\ImageGalleryType;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryType::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryTypeTest extends AbstractIntegrationTestCase
{
    private ImageGalleryType $formType;

    protected function onSetUp(): void
    {
        $this->formType = self::getService(ImageGalleryType::class);
    }

    public function testServiceIsAvailable(): void
    {
        $formType = self::getService(ImageGalleryType::class);
        $this->assertInstanceOf(ImageGalleryType::class, $formType);
    }

    public function testInstanceOfAbstractType(): void
    {
        $this->assertInstanceOf(AbstractType::class, $this->formType);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $options = [];

        // The buildForm method should not add any fields since it's empty
        $builder->expects($this->never())
            ->method('add')
        ;

        $this->formType->buildForm($builder, $options);
    }

    public function testBuildView(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayHasKey('row_attr', $view->vars);
        $this->assertArrayHasKey('class', $view->vars['row_attr']);
        $this->assertEquals('image-gallery-field-row', $view->vars['row_attr']['class']);

        $this->assertArrayHasKey('attr', $view->vars);
        $this->assertArrayHasKey('class', $view->vars['attr']);
        $this->assertEquals('image-gallery-url-field', $view->vars['attr']['class']);
    }

    public function testBuildViewWithExistingRowAttr(): void
    {
        $view = new FormView();
        $view->vars['row_attr'] = ['existing' => 'value'];
        $form = $this->createMock(FormInterface::class);
        $options = [];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayHasKey('existing', $view->vars['row_attr']);
        $this->assertEquals('value', $view->vars['row_attr']['existing']);
        $this->assertArrayHasKey('class', $view->vars['row_attr']);
        $this->assertEquals('image-gallery-field-row', $view->vars['row_attr']['class']);
    }

    public function testBuildViewWithExistingAttr(): void
    {
        $view = new FormView();
        $view->vars['attr'] = ['existing' => 'value'];
        $form = $this->createMock(FormInterface::class);
        $options = [];

        $this->formType->buildView($view, $form, $options);

        $this->assertArrayHasKey('existing', $view->vars['attr']);
        $this->assertEquals('value', $view->vars['attr']['existing']);
        $this->assertArrayHasKey('class', $view->vars['attr']);
        $this->assertEquals('image-gallery-url-field', $view->vars['attr']['class']);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        $this->assertArrayHasKey('compound', $resolvedOptions);
        $this->assertFalse($resolvedOptions['compound']);
        $this->assertArrayHasKey('label', $resolvedOptions);
        $this->assertFalse($resolvedOptions['label']);
    }

    public function testGetParent(): void
    {
        $parent = $this->formType->getParent();
        $this->assertEquals(TextType::class, $parent);
    }

    public function testConfigureOptionsWithEmptyOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        $this->assertArrayHasKey('compound', $resolvedOptions);
        $this->assertFalse($resolvedOptions['compound']);
        $this->assertArrayHasKey('label', $resolvedOptions);
        $this->assertFalse($resolvedOptions['label']);
    }

    public function testConfigureOptionsCanOverrideDefaults(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $customOptions = [
            'compound' => true,
            'label' => 'Custom Label',
        ];

        $resolvedOptions = $resolver->resolve($customOptions);

        $this->assertTrue($resolvedOptions['compound']);
        $this->assertEquals('Custom Label', $resolvedOptions['label']);
    }
}
