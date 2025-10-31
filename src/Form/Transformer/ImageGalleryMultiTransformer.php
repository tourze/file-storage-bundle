<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<array<array{url: string}>, string>
 */
final class ImageGalleryMultiTransformer implements DataTransformerInterface
{
    /**
     * @param mixed $value
     */
    public function transform($value): string
    {
        if (is_array($value)) {
            return $this->transformArrayValue($value);
        }

        if (is_string($value)) {
            return $value;
        }

        return '[]';
    }

    /**
     * @param mixed $value
     * @return array<array{url: string}>
     */
    public function reverseTransform($value): array
    {
        if (is_string($value)) {
            return $this->decodeUrlsFromJson($value);
        }

        if (is_array($value)) {
            /** @var array<array{url: string}> $value */
            return $value;
        }

        return [];
    }

    /**
     * @return array<array{url: string}>
     */
    private function decodeUrlsFromJson(string $json): array
    {
        $json = trim($json);
        if ('' === $json) {
            return [];
        }
        try {
            $arr = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }
        if (!is_array($arr)) {
            return [];
        }
        $res = [];
        foreach ($arr as $u) {
            if (is_string($u) && '' !== $u) {
                $res[] = ['url' => $u];
            }
        }

        return $res;
    }

    /**
     * 转换数组值为JSON字符串
     *
     * @param array<mixed> $value
     */
    private function transformArrayValue(array $value): string
    {
        $urls = $this->extractUrlsFromArray($value);
        $encoded = json_encode($urls, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return false !== $encoded ? $encoded : '[]';
    }

    /**
     * 从数组中提取URL
     *
     * @param array<mixed> $value
     * @return string[]
     */
    private function extractUrlsFromArray(array $value): array
    {
        $urls = [];
        foreach ($value as $item) {
            $url = $this->extractUrlFromItem($item);
            if (null !== $url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    /**
     * 从单个项目中提取URL
     */
    private function extractUrlFromItem(mixed $item): ?string
    {
        if (is_array($item) && isset($item['url'])) {
            return is_string($item['url']) ? $item['url'] : (is_scalar($item['url']) ? (string) $item['url'] : null);
        }

        if (is_string($item)) {
            return $item;
        }

        return null;
    }
}
