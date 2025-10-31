<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Tourze\FileStorageBundle\Form\ImageGalleryMultiType;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;

final class ImageGalleryMultiField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        $field = (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text')
            ->setTemplatePath('@FileStorage/bundles/EasyAdminBundle/crud/field/image_gallery_multi.html.twig')
            ->addFormTheme('@FileStorage/form/image_gallery_multi_theme.html.twig')
            ->setFormType(ImageGalleryMultiType::class)
            ->addCssClass('field-image-gallery-multi')
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
        $urls = self::normalizeUrls($value);
        if ([] === $urls) {
            return '无图片';
        }
        $imgs = array_map(static fn (string $u) => sprintf('<img src="%s" style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; margin: 4px;" />', htmlspecialchars($u)), $urls);

        return '<div style="display:flex; flex-wrap:wrap;">' . implode('', $imgs) . '</div>';
    }

    /**
     * @return array<string>
     */
    private static function normalizeUrls(mixed $value): array
    {
        if (is_array($value)) {
            return self::normalizeArrayUrls($value);
        }

        if (is_string($value)) {
            return self::normalizeStringUrls($value);
        }

        return [];
    }

    /**
     * @param array<mixed> $value
     * @return array<string>
     */
    private static function normalizeArrayUrls(array $value): array
    {
        $urls = array_map(static fn ($it) => self::toUrl($it), $value);
        $urls = array_filter($urls, static fn ($v) => null !== $v && '' !== $v);

        return array_values(array_map(static fn ($v) => $v, $urls));
    }

    /**
     * @return array<string>
     */
    private static function normalizeStringUrls(string $value): array
    {
        $trim = trim($value);
        if ('' === $trim) {
            return [];
        }

        return self::parseJsonOrReturnSingle($trim);
    }

    /**
     * @return array<string>
     */
    private static function parseJsonOrReturnSingle(string $value): array
    {
        try {
            $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($data)) {
                return self::convertArrayItemsToStrings($data);
            }
        } catch (\Throwable) {
            // 退化为单个 URL
        }

        return [$value];
    }

    /**
     * @param array<mixed> $data
     * @return array<string>
     */
    private static function convertArrayItemsToStrings(array $data): array
    {
        $converter = static fn ($item): string => is_string($item) ? $item : (is_scalar($item) ? (string) $item : '');
        $filter = static fn ($v) => '' !== $v;

        return array_values(array_filter(array_map($converter, $data), $filter));
    }

    private static function toUrl(mixed $item): ?string
    {
        if (is_string($item)) {
            return $item;
        }

        if (is_array($item) && isset($item['url'])) {
            return self::convertToUrlString($item['url']);
        }

        if (is_object($item) && property_exists($item, 'url')) {
            return self::convertToUrlString($item->url);
        }

        return null;
    }

    private static function convertToUrlString(mixed $url): ?string
    {
        if (is_string($url)) {
            return $url;
        }

        if (is_scalar($url)) {
            return (string) $url;
        }

        return null;
    }

    private static function formatForm(mixed $value): string
    {
        // 由表单主题渲染，小部件仅需存在即可
        return '';
    }

    private static function formatIndex(mixed $value): string
    {
        $urls = self::normalizeUrls($value);
        if ([] === $urls) {
            return '—';
        }
        $count = count($urls);
        $thumbs = array_slice($urls, 0, 3);
        $imgs = array_map(static fn (string $u) => sprintf('<img src="%s" style="width: 34px; height: 34px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; margin-right: 3px; cursor: pointer;" onclick="showImageModal(\'%s\', \'图片\')" />', htmlspecialchars($u), htmlspecialchars($u)), $thumbs);

        $suffix = '';
        if ($count > 3) {
            $suffix = sprintf('<span style="color:#666; font-size:12px; margin-left:4px;">(+%d)</span>', $count - 3);
        }

        return '<div style="display:flex; align-items:center;">' . implode('', $imgs) . $suffix . '</div>' . self::getListPageJavascript();
    }

    private static function getListPageJavascript(): string
    {
        return '
            <script>
            if (!window.imageModalScriptLoaded) {
                window.imageModalScriptLoaded = true;
                function showImageModal(src, title) {
                    var existing = document.getElementById("imageModal");
                    if (existing) existing.remove();
                    var modal = document.createElement("div");
                    modal.id = "imageModal";
                    modal.style.cssText = "position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2147483647; display: flex; align-items: center; justify-content: center; cursor: pointer;";
                    var img = document.createElement("img");
                    img.src = src; img.alt = title; img.style.cssText = "max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);";
                    modal.appendChild(img);
                    modal.onclick = function(){ modal.remove(); };
                    document.body.appendChild(modal);
                    document.addEventListener("keydown", function closeOnEscape(e){ if (e.key === "Escape") { modal.remove(); document.removeEventListener("keydown", closeOnEscape); } });
                }
                window.showImageModal = showImageModal;
            }
            </script>';
    }
}
