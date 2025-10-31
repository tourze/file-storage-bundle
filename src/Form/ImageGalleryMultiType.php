<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Form;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\FileStorageBundle\Form\Transformer\ImageGalleryMultiTransformer;

#[Autoconfigure(public: true)]
class ImageGalleryMultiType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 使用文本字段保存 JSON 字符串（URL 数组），并在模型/视图间转换
        $builder->addModelTransformer(new ImageGalleryMultiTransformer());
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['row_attr'] = array_merge(is_array($view->vars['row_attr'] ?? null) ? $view->vars['row_attr'] : [], ['class' => 'image-gallery-multi-field-row']);

        $view->vars['attr'] = array_merge(is_array($view->vars['attr'] ?? null) ? $view->vars['attr'] : [], ['class' => 'image-gallery-multi-url-field']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => false,
            'label' => false,
            // 统一以 JSON 字符串持久化数组，空为 []
            'empty_data' => '[]',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
