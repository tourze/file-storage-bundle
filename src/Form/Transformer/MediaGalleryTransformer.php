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
        if (!$this->isValidInput($value)) {
            return [];
        }

        try {
            assert(is_string($value)); // $value is validated by isValidInput
            $data = $this->decodeJsonValue($value);

            return $this->normalizeMediaItems($data);
        } catch (\JsonException $e) {
            throw new TransformationFailedException('Failed to decode media gallery data: ' . $e->getMessage(), 0, $e);
        }
    }

    private function isValidInput(mixed $value): bool
    {
        return null !== $value && '' !== $value && is_string($value);
    }

    /**
     * @return array<mixed>
     */
    private function decodeJsonValue(string $value): array
    {
        $data = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * @param array<mixed> $data
     * @return array<array{url: string, type: string}>
     */
    private function normalizeMediaItems(array $data): array
    {
        $normalized = [];

        foreach ($data as $item) {
            $normalizedItem = $this->normalizeMediaItem($item);
            if (null !== $normalizedItem) {
                $normalized[] = $normalizedItem;
            }
        }

        return $normalized;
    }

    /**
     * @param mixed $item
     * @return array{url: string, type: string}|null
     */
    private function normalizeMediaItem($item): ?array
    {
        if (is_string($item)) {
            return [
                'url' => $item,
                'type' => $this->guessMediaType($item),
            ];
        }

        if (is_array($item) && isset($item['url']) && is_string($item['url'])) {
            return [
                'url' => $item['url'],
                'type' => (isset($item['type']) && is_string($item['type'])) ? $item['type'] : $this->guessMediaType($item['url']),
            ];
        }

        return null;
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
