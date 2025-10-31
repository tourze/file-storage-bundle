<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<array<array{url: string, type: string}>, string>
 */
class MediaGalleryTransformer implements DataTransformerInterface
{
    /**
     * 将模型数据（数组）转换为表单数据（JSON字符串）
     *
     * @param array<array{url: string, type: string}>|null $value
     */
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '[]';
        }

        if (!is_array($value)) {
            return '[]';
        }

        try {
            return json_encode($value, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new TransformationFailedException('Failed to encode media gallery data: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 将表单数据（JSON字符串）转换为模型数据（数组）
     *
     * @param string|null $value
     *
     * @return array<array{url: string, type: string}>
     */
    public function reverseTransform(mixed $value): array
    {
        if (null === $value || '' === $value) {
            return [];
        }

        if (!is_string($value)) {
            return [];
        }

        try {
            $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                return [];
            }

            // 规范化数据，确保每个项都有 url 和 type
            $normalized = [];
            foreach ($data as $item) {
                if (is_string($item)) {
                    // 简单字符串，自动判断类型
                    $normalized[] = [
                        'url' => $item,
                        'type' => $this->guessMediaType($item),
                    ];
                } elseif (is_array($item) && isset($item['url'])) {
                    $normalized[] = [
                        'url' => $item['url'],
                        'type' => $item['type'] ?? $this->guessMediaType($item['url']),
                    ];
                }
            }

            return $normalized;
        } catch (\JsonException $e) {
            throw new TransformationFailedException('Failed to decode media gallery data: ' . $e->getMessage(), 0, $e);
        }
    }

    private function guessMediaType(string $url): string
    {
        // 通过文件扩展名判断类型
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        return match ($extension) {
            'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v' => 'video',
            default => 'image',
        };
    }
}
