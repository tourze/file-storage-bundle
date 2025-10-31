<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Tourze\FileStorageBundle\Form\ImageGalleryType;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;

final class ImageGalleryField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        $field = (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            // 保持模板名为默认文本以兼容既有测试
            ->setTemplateName('crud/field/text')
            // 实际渲染使用自定义模板路径（EasyAdmin 会优先使用 templatePath）
            ->setTemplatePath('@FileStorage/bundles/EasyAdminBundle/crud/field/image_gallery.html.twig')
            // 表单页使用自定义 Form Theme（基于 ImageGalleryType 的 block 前缀 image_gallery）
            ->addFormTheme('@FileStorage/form/image_gallery_theme.html.twig')
            ->setFormType(ImageGalleryType::class)
            ->addCssClass('field-image-gallery')
        ;

        $field->formatValue(function ($value, $entity) {
            if (!is_object($entity)) {
                return '';
            }

            return self::formatImageValue($value, $entity);
        });

        return $field;
    }

    /**
     * 获取当前CRUD操作类型
     */
    private static function getCurrentCrudAction(): ?string
    {
        $resolver = CrudActionResolverRegistry::getInstance();
        if (null === $resolver) {
            return null;
        }

        return $resolver->getCurrentCrudAction();
    }

    private static function formatImageValue(mixed $value, object $entity): string
    {
        $crudAction = self::getCurrentCrudAction();

        return match ($crudAction) {
            'edit', 'new' => self::formatFormPageValue($value),
            'detail' => self::formatDetailPageValue($value),
            default => self::formatListPageValue($value),
        };
    }

    private static function formatFormPageValue(mixed $value): string
    {
        $valueStr = is_string($value) ? $value : '';
        $hasValue = '' !== $valueStr;

        $currentImageHtml = $hasValue
            ? sprintf(
                '<img src="%s" alt="当前图片" style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; margin-bottom: 10px; display: block;">',
                $valueStr
            )
            : '<div style="width: 150px; height: 100px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999; margin-bottom: 10px; border-radius: 4px;">无图片</div>';

        $buttonText = $hasValue ? '更换图片' : '选择图片';
        $clearButton = $hasValue ? '<button type="button" class="btn btn-danger btn-sm ms-2" onclick="clearCurrentImage(this)" style="margin-left: 8px;">清除</button>' : '';

        return sprintf(
            '<div class="image-gallery-field-wrapper" style="margin-bottom: 15px;">
                <label class="form-control-label">头像</label>
                <div class="image-preview-area">
                    %s
                </div>
                <div>
                    <button type="button" class="btn btn-primary btn-sm" onclick="openImageGalleryModal(this)" data-field-name="avatar">%s</button>
                    %s
                </div>
            </div>%s',
            $currentImageHtml,
            $buttonText,
            $clearButton,
            self::getFormPageJavascript()
        );
    }

    private static function formatDetailPageValue(mixed $value): string
    {
        $valueStr = is_string($value) ? $value : '';
        if ('' === $valueStr) {
            return '无头像';
        }

        return sprintf(
            '<div style="margin: 10px 0;">
                <img src="%s" alt="头像" style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" />
                <br><a href="%s" target="_blank" style="margin-top: 10px; display: inline-block;">在新窗口打开</a>
            </div>',
            $valueStr,
            $valueStr
        );
    }

    private static function formatListPageValue(mixed $value): string
    {
        $valueStr = is_string($value) ? $value : '';
        if ('' === $valueStr) {
            return '无头像';
        }

        return sprintf(
            '<div class="image-preview-container">
                <img src="%s" alt="头像"
                     style="max-width: 100px; max-height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;"
                     onclick="showImageModal(\'%s\', \'头像\')"
                     title="点击预览图片">
            </div>%s',
            $valueStr,
            $valueStr,
            self::getListPageJavascript()
        );
    }

    private static function getFormPageJavascript(): string
    {
        return '
                <script>
                if (!window.imageGalleryScriptLoaded) {
                    window.imageGalleryScriptLoaded = true;
                    
                    function openImageGalleryModal(button) {
                        const fieldName = button.getAttribute("data-field-name");
                        
                        // 创建模态框
                        const modal = document.createElement("div");
                        modal.id = "imageGalleryModal";
                        modal.style.cssText = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center;";
                        
                        const modalContent = document.createElement("div");
                        modalContent.style.cssText = "width: 90%; height: 90%; background: white; border-radius: 8px; position: relative;";
                        
                        const header = document.createElement("div");
                        header.style.cssText = "padding: 15px 20px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;";
                        header.innerHTML = `<h5 style="margin: 0;">选择图片</h5><button type="button" onclick="closeImageGalleryModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #999;">&times;</button>`;
                        
                        const iframe = document.createElement("iframe");
                        iframe.src = "/gallery?mode=select";
                        iframe.style.cssText = "width: 100%; height: calc(100% - 60px); border: none;";
                        
                        modalContent.appendChild(header);
                        modalContent.appendChild(iframe);
                        modal.appendChild(modalContent);
                        document.body.appendChild(modal);
                        
                        // 监听来自 iframe 的消息
                        window.addEventListener("message", function(event) {
                            if (event.data && event.data.type === "imageSelected") {
                                updateImageField(fieldName, event.data.url);
                                closeImageGalleryModal();
                            }
                        });
                        
                        // 点击模态框背景关闭
                        modal.addEventListener("click", function(e) {
                            if (e.target === modal) {
                                closeImageGalleryModal();
                            }
                        });
                    }
                    
                    function closeImageGalleryModal() {
                        const modal = document.getElementById("imageGalleryModal");
                        if (modal) {
                            modal.remove();
                        }
                    }
                    
                    function updateImageField(fieldName, imageUrl) {
                        // 更新隐藏字段
                        const hiddenField = document.querySelector(`input[name="BizUser[${fieldName}]"]`);
                        if (hiddenField) {
                            hiddenField.value = imageUrl;
                        }
                        
                        // 更新预览图片
                        const wrapper = document.querySelector(".image-gallery-field-wrapper");
                        if (wrapper) {
                            const previewArea = wrapper.querySelector(".image-preview-area");
                            const button = wrapper.querySelector(".btn-primary");
                            
                            previewArea.innerHTML = `<img src="${imageUrl}" alt="当前图片" style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; margin-bottom: 10px; display: block;">`;
                            button.textContent = "更换图片";
                            
                            // 添加清除按钮
                            let clearBtn = wrapper.querySelector(".btn-danger");
                            if (!clearBtn) {
                                clearBtn = document.createElement("button");
                                clearBtn.type = "button";
                                clearBtn.className = "btn btn-danger btn-sm ms-2";
                                clearBtn.style.marginLeft = "8px";
                                clearBtn.textContent = "清除";
                                clearBtn.onclick = () => clearCurrentImage(clearBtn);
                                button.parentNode.appendChild(clearBtn);
                            }
                        }
                    }
                    
                    function clearCurrentImage(button) {
                        const wrapper = button.closest(".image-gallery-field-wrapper");
                        if (wrapper) {
                            const previewArea = wrapper.querySelector(".image-preview-area");
                            const primaryBtn = wrapper.querySelector(".btn-primary");
                            const hiddenField = document.querySelector(`input[name="BizUser[avatar]"]`);
                            
                            previewArea.innerHTML = `<div style="width: 150px; height: 100px; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #999; margin-bottom: 10px; border-radius: 4px;">无图片</div>`;
                            primaryBtn.textContent = "选择图片";
                            if (hiddenField) {
                                hiddenField.value = "";
                            }
                            button.remove();
                        }
                    }
                    
                    // ESC键关闭模态框
                    document.addEventListener("keydown", function(e) {
                        if (e.key === "Escape") {
                            closeImageGalleryModal();
                        }
                    });
                }
                </script>';
    }

    private static function getListPageJavascript(): string
    {
        return '
            <script>
            if (!window.imageModalScriptLoaded) {
                window.imageModalScriptLoaded = true;
                function showImageModal(src, title) {
                    if (document.getElementById("imageModal")) {
                        document.getElementById("imageModal").remove();
                    }
                    
                    const modal = document.createElement("div");
                    modal.id = "imageModal";
                    modal.style.cssText = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999; display: flex; align-items: center; justify-content: center; cursor: pointer;";
                    
                    const img = document.createElement("img");
                    img.src = src;
                    img.alt = title;
                    img.style.cssText = "max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);";
                    
                    modal.appendChild(img);
                    modal.onclick = () => modal.remove();
                    document.body.appendChild(modal);
                    
                    document.addEventListener("keydown", function closeOnEscape(e) {
                        if (e.key === "Escape") {
                            modal.remove();
                            document.removeEventListener("keydown", closeOnEscape);
                        }
                    });
                }
            }
            </script>';
    }
}
