<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Tourze\FileStorageBundle\Form\MediaGalleryType;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;

final class MediaGalleryField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        $field = (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text')
            ->setTemplatePath('@FileStorage/bundles/EasyAdminBundle/crud/field/media_gallery.html.twig')
            ->addFormTheme('@FileStorage/form/media_gallery_theme.html.twig')
            ->setFormType(MediaGalleryType::class)
            ->addCssClass('field-media-gallery')
        ;

        $field->formatValue(function ($value, $entity) {
            return self::formatValueForPage($value, is_object($entity) ? $entity : new \stdClass());
        });

        return $field;
    }

    private static function formatValueForPage(mixed $value, object $entity): string
    {
        $crudAction = self::getCurrentCrudAction();

        return match ($crudAction) {
            'detail' => self::formatDetail($value),
            'edit', 'new' => self::formatForm($value),
            default => self::formatIndex($value),
        };
    }

    private static function getCurrentCrudAction(): ?string
    {
        $resolver = CrudActionResolverRegistry::getInstance();
        if (null === $resolver) {
            return null;
        }

        return $resolver->getCurrentCrudAction();
    }

    private static function formatDetail(mixed $value): string
    {
        $items = self::normalizeItems($value);
        if ([] === $items) {
            return '无媒体文件';
        }

        $html = '<div style="display:flex; flex-wrap:wrap; gap: 10px;">';

        foreach ($items as $item) {
            $url = htmlspecialchars($item['url']);
            $type = $item['type'] ?? 'image';

            if ('video' === $type) {
                $html .= sprintf('<div style="position: relative; width: 150px; height: 100px;"><video src="%s" style="width: 100%%; height: 100%%; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" muted></video><div style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.7); color: white; padding: 2px 6px; border-radius: 4px; font-size: 11px;"><i class="bi bi-camera-video"></i> 视频</div></div>', $url);
            } else {
                $html .= sprintf('<img src="%s" style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" />', $url);
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @return array<array{url: string, type: string}>
     */
    private static function normalizeItems(mixed $value): array
    {
        if (is_array($value)) {
            return self::normalizeArrayItems($value);
        }

        if (is_string($value)) {
            return self::normalizeStringItems($value);
        }

        return [];
    }

    /**
     * @param array<mixed> $value
     * @return array<array{url: string, type: string}>
     */
    private static function normalizeArrayItems(array $value): array
    {
        $items = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                // 简单字符串，通过URL判断类型
                $items[] = [
                    'url' => $item,
                    'type' => self::guessMediaType($item),
                ];
            } elseif (is_array($item) && isset($item['url'])) {
                $items[] = [
                    'url' => $item['url'],
                    'type' => $item['type'] ?? self::guessMediaType($item['url']),
                ];
            } elseif (is_object($item) && property_exists($item, 'url')) {
                $items[] = [
                    'url' => $item->url,
                    'type' => (property_exists($item, 'type') ? $item->type : null) ?? self::guessMediaType($item->url),
                ];
            }
        }

        return array_filter($items, static fn (array $v) => '' !== $v['url']);
    }

    /**
     * @return array<array{url: string, type: string}>
     */
    private static function normalizeStringItems(string $value): array
    {
        $trim = trim($value);
        if ('' === $trim) {
            return [];
        }

        try {
            $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($data)) {
                return self::normalizeArrayItems($data);
            }
        } catch (\Throwable) {
            // 退化为单个 URL
        }

        return [[
            'url' => $value,
            'type' => self::guessMediaType($value),
        ]];
    }

    private static function guessMediaType(string $url): string
    {
        // 通过文件扩展名判断类型
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v' => 'video',
            default => 'image',
        };
    }

    private static function formatForm(mixed $value): string
    {
        // 由表单主题渲染，小部件仅需存在即可
        return '';
    }

    private static function formatIndex(mixed $value): string
    {
        $items = self::normalizeItems($value);
        if ([] === $items) {
            return '—';
        }

        $count = count($items);
        $thumbs = array_slice($items, 0, 3);

        $html = '<div style="display:flex; align-items:center; gap: 3px;">';

        foreach ($thumbs as $item) {
            $url = htmlspecialchars($item['url']);
            $type = $item['type'] ?? 'image';

            if ('video' === $type) {
                $html .= sprintf('<div style="position: relative; width: 34px; height: 34px;"><video src="%s" style="width: 100%%; height: 100%%; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; cursor: pointer;" muted onclick="showMediaModal(\'%s\', \'视频\', \'video\')"></video><div style="position: absolute; top: -2px; right: -2px; background: #e03131; color: white; width: 12px; height: 12px; border-radius: 50%%; font-size: 8px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-play-fill" style="font-size: 6px;"></i></div></div>', $url, $url);
            } else {
                $html .= sprintf('<img src="%s" style="width: 34px; height: 34px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; margin-right: 3px; cursor: pointer;" onclick="showMediaModal(\'%s\', \'图片\', \'image\')" />', $url, $url);
            }
        }

        if ($count > 3) {
            $html .= sprintf('<span style="color:#666; font-size:12px; margin-left:4px;">(+%d)</span>', $count - 3);
        }

        $html .= '</div>';

        return $html . self::getListPageJavascript();
    }

    private static function getListPageJavascript(): string
    {
        return '
            <script>
            if (!window.mediaModalScriptLoaded) {
                window.mediaModalScriptLoaded = true;
                function showMediaModal(src, title, type) {
                    var existing = document.getElementById("mediaModal");
                    if (existing) existing.remove();
                    var modal = document.createElement("div");
                    modal.id = "mediaModal";
                    modal.style.cssText = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2147483647; display: flex; align-items: center; justify-content: center; cursor: pointer;";
                    
                    if (type === "video") {
                        var video = document.createElement("video");
                        video.src = src; 
                        video.controls = true;
                        video.autoplay = true;
                        video.style.cssText = "max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);";
                        modal.appendChild(video);
                    } else {
                        var img = document.createElement("img");
                        img.src = src; 
                        img.alt = title; 
                        img.style.cssText = "max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);";
                        modal.appendChild(img);
                    }
                    
                    modal.onclick = function(){ modal.remove(); };
                    document.body.appendChild(modal);
                    document.addEventListener("keydown", function closeOnEscape(e){ 
                        if (e.key === "Escape") { 
                            modal.remove(); 
                            document.removeEventListener("keydown", closeOnEscape); 
                        } 
                    });
                }
                window.showMediaModal = showMediaModal;
            }
            </script>';
    }
}
