<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller\Gallery;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryIndexController;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryIndexController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryIndexControllerTest extends AbstractWebTestCase
{
    protected function onSetUp(): void
    {
        // No additional setup needed
    }

    public function testGalleryIndexPageRenders(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/gallery');

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testGalleryIndexUsesCorrectTemplate(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/gallery');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // 验证响应是HTML
        $this->assertStringContainsString('text/html', $client->getResponse()->headers->get('content-type') ?? '');

        // 获取响应内容
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $this->assertNotEmpty($content);
    }

    public function testGalleryIndexRouteParameters(): void
    {
        $client = self::createClientWithDatabase();

        // 测试路由匹配
        $client->request('GET', '/gallery');
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // 测试不匹配的HTTP方法 - 使用 catchExceptions 确保异常被转换为 HTTP 状态码
        $client->catchExceptions(true);

        $client->request('POST', '/gallery');
        $this->assertSame(405, $client->getResponse()->getStatusCode());

        $client->request('PUT', '/gallery');
        $this->assertSame(405, $client->getResponse()->getStatusCode());

        $client->request('DELETE', '/gallery');
        $this->assertSame(405, $client->getResponse()->getStatusCode());
    }

    public function testGalleryIndexRouteWithTrailingSlash(): void
    {
        $client = self::createClientWithDatabase();

        // 测试带斜杠的路径
        $client->request('GET', '/gallery/');

        // 这应该重定向到不带斜杠的版本，或者直接成功
        $this->assertTrue(
            $client->getResponse()->isSuccessful()
            || $client->getResponse()->isRedirection()
        );
    }

    public function testGalleryIndexMultipleRequests(): void
    {
        $client = self::createClientWithDatabase();

        // 测试多次请求都成功
        for ($i = 0; $i < 3; ++$i) {
            $client->request('GET', '/gallery');
            $this->assertSame(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testGalleryIndexResponseHeaders(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/gallery');

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $response = $client->getResponse();

        // 验证基本的响应头
        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type') ?? '');
    }

    public function testGalleryIndexWithDifferentAcceptHeaders(): void
    {
        $client = self::createClientWithDatabase();

        // 测试不同的Accept头
        $acceptHeaders = [
            'text/html',
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            '*/*',
        ];

        foreach ($acceptHeaders as $acceptHeader) {
            $client->request('GET', '/gallery', [], [], [
                'HTTP_ACCEPT' => $acceptHeader,
            ]);

            $this->assertSame(200, $client->getResponse()->getStatusCode());
        }
    }

    public function testGalleryIndexDoesNotRequireAuthentication(): void
    {
        // 创建不带认证的客户端
        $client = self::createClientWithDatabase();

        $client->request('GET', '/gallery');

        // 应该成功访问，不需要认证
        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(false);

        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/gallery');
    }
}
