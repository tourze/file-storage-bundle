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

#[Autoconfigure(public: true)]
class ImageGalleryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // 直接作为简单字段，不需要添加子字段
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['row_attr'] = array_merge(
            is_array($view->vars['row_attr'] ?? null) ? $view->vars['row_attr'] : [],
            [
                'class' => 'image-gallery-field-row',
            ]
        );

        // 添加自定义CSS类和属性
        $view->vars['attr'] = array_merge(
            is_array($view->vars['attr'] ?? null) ? $view->vars['attr'] : [],
            [
                'class' => 'image-gallery-url-field',
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => false,
            'label' => false,
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }
}
