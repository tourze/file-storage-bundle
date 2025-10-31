<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\FileStorageBundle\Form\MediaGalleryType;
use Tourze\FileStorageBundle\Form\Transformer\MediaGalleryTransformer;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(MediaGalleryType::class)]
#[RunTestsInSeparateProcesses]
final class MediaGalleryTypeTest extends AbstractIntegrationTestCase
{
    private MediaGalleryType $formType;

    protected function onSetUp(): void
    {
        $this->formType = self::getService(MediaGalleryType::class);
    }

    public function testServiceIsAvailable(): void
    {
        $formType = self::getService(MediaGalleryType::class);
        self::assertEquals(TextType::class, $formType->getParent());
    }

    public function testBuildFormAddsTransformer(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('addModelTransformer')
            ->with(self::isInstanceOf(MediaGalleryTransformer::class))
        ;

        $this->formType->buildForm($builder, []);
    }

    public function testBuildView(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [];

        $this->formType->buildView($view, $form, $options);

        self::assertArrayHasKey('row_attr', $view->vars);
        self::assertArrayHasKey('class', $view->vars['row_attr']);
        self::assertEquals('media-gallery-field-row', $view->vars['row_attr']['class']);

        self::assertArrayHasKey('attr', $view->vars);
        self::assertArrayHasKey('class', $view->vars['attr']);
        self::assertEquals('media-gallery-url-field', $view->vars['attr']['class']);
    }

    public function testBuildViewWithExistingRowAttr(): void
    {
        $view = new FormView();
        $view->vars['row_attr'] = ['existing' => 'value'];
        $form = $this->createMock(FormInterface::class);
        $options = [];

        $this->formType->buildView($view, $form, $options);

        self::assertArrayHasKey('existing', $view->vars['row_attr']);
        self::assertEquals('value', $view->vars['row_attr']['existing']);
        self::assertArrayHasKey('class', $view->vars['row_attr']);
        self::assertEquals('media-gallery-field-row', $view->vars['row_attr']['class']);
    }

    public function testBuildViewWithExistingAttr(): void
    {
        $view = new FormView();
        $view->vars['attr'] = ['existing' => 'value'];
        $form = $this->createMock(FormInterface::class);
        $options = [];

        $this->formType->buildView($view, $form, $options);

        self::assertArrayHasKey('existing', $view->vars['attr']);
        self::assertEquals('value', $view->vars['attr']['existing']);
        self::assertArrayHasKey('class', $view->vars['attr']);
        self::assertEquals('media-gallery-url-field', $view->vars['attr']['class']);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        self::assertArrayHasKey('compound', $resolvedOptions);
        self::assertFalse($resolvedOptions['compound']);
        self::assertArrayHasKey('label', $resolvedOptions);
        self::assertFalse($resolvedOptions['label']);
        self::assertArrayHasKey('empty_data', $resolvedOptions);
        self::assertEquals('[]', $resolvedOptions['empty_data']);
    }

    public function testGetParent(): void
    {
        $parent = $this->formType->getParent();
        self::assertEquals(TextType::class, $parent);
    }

    public function testConfigureOptionsWithEmptyOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        self::assertArrayHasKey('compound', $resolvedOptions);
        self::assertFalse($resolvedOptions['compound']);
        self::assertArrayHasKey('label', $resolvedOptions);
        self::assertFalse($resolvedOptions['label']);
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

        self::assertTrue($resolvedOptions['compound']);
        self::assertEquals('Custom Label', $resolvedOptions['label']);
    }
}
