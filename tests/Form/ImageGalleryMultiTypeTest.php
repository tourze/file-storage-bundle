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
use Tourze\FileStorageBundle\Form\ImageGalleryMultiType;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryMultiType::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryMultiTypeTest extends AbstractIntegrationTestCase
{
    private ImageGalleryMultiType $formType;

    public function testServiceIsAvailable(): void
    {
        $this->assertInstanceOf(ImageGalleryMultiType::class, self::getService(ImageGalleryMultiType::class));
    }

    public function testInstanceOfAbstractType(): void
    {
        $this->assertInstanceOf(AbstractType::class, $this->formType);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())->method('add');
        $this->formType->buildForm($builder, []);
    }

    public function testBuildView(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $this->formType->buildView($view, $form, []);

        $this->assertArrayHasKey('row_attr', $view->vars);
        $this->assertEquals('image-gallery-multi-field-row', $view->vars['row_attr']['class']);
        $this->assertArrayHasKey('attr', $view->vars);
        $this->assertEquals('image-gallery-multi-url-field', $view->vars['attr']['class']);
    }

    public function testConfigureOptions(): void
    {
        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);
        $opts = $resolver->resolve([]);
        $this->assertFalse($opts['compound']);
        $this->assertFalse($opts['label']);
        $this->assertSame('[]', $opts['empty_data']);
    }

    public function testGetParent(): void
    {
        $this->assertSame(TextType::class, $this->formType->getParent());
    }

    protected function onSetUp(): void
    {
        $this->formType = self::getService(ImageGalleryMultiType::class);
    }
}
