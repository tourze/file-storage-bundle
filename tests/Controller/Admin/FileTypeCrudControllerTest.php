<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\FileStorageBundle\Controller\Admin\FileTypeCrudController;
use Tourze\FileStorageBundle\Entity\FileType;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(FileTypeCrudController::class)]
#[RunTestsInSeparateProcesses]
final class FileTypeCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testAdminRouteExists(): void
    {
        $client = $this->createAuthenticatedClient();
        // 使用 EasyAdmin Url 生成器访问当前 CRUD 的首页，而不是 Dashboard
        $url = $this->generateAdminUrl('index');
        $client->request('GET', $url);
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            'CRUD 首页应可访问（允许 2xx 或 3xx 重定向）'
        );
    }

    public function testIndexActionReturnsResponse(): void
    {
        $this->makeRequestAndAssertOk('GET', 'index', 'Index action should exist');
        $this->assertTrue(true); // 确保有断言
    }

    public function testNewActionReturnsResponse(): void
    {
        $this->makeRequestAndAssertOk('GET', 'new', 'New action should exist');
        $this->assertTrue(true); // 确保有断言
    }

    public function testDetailActionReturnsResponse(): void
    {
        $entity = $this->createTestFileType();
        $this->makeRequestAndAssertOk('GET', 'detail', 'Detail action should exist', ['entityId' => (string) $entity->getId()]);
        $this->assertTrue(true); // 确保有断言
    }

    public function testEditActionReturnsResponse(): void
    {
        $entity = $this->createTestFileType();
        $this->makeRequestAndAssertOk('GET', 'edit', 'Edit action should exist', ['entityId' => (string) $entity->getId()]);
        $this->assertTrue(true); // 确保有断言
    }

    public function testDeleteActionReturnsResponse(): void
    {
        $entity = $this->createTestFileType();
        $this->makeRequestAndAssertOk('POST', 'delete', 'Delete action should exist', ['entityId' => (string) $entity->getId()]);
        $this->assertTrue(true); // 确保有断言
    }

    public function testPostNewActionReturnsResponse(): void
    {
        $data = $this->getValidFileTypeData();
        $this->makeRequestAndAssertOk('POST', 'new', 'POST new action should exist', [], $data);
        $this->assertTrue(true); // 确保有断言
    }

    public function testPatchEditActionReturnsResponse(): void
    {
        $entity = $this->createTestFileType();
        $data = ['FileType' => ['name' => 'Patched Type']];
        $this->makeRequestAndAssertOk('PATCH', 'edit', 'PATCH edit action should exist', ['entityId' => (string) $entity->getId()], $data);
        $this->assertTrue(true); // 确保有断言
    }

    public function testHeadIndexActionReturnsResponse(): void
    {
        $this->makeRequestAndAssertOk('HEAD', 'index', 'HEAD index action should exist');
        $this->assertTrue(true); // 确保有断言
    }

    public function testSearchFunctionalityExists(): void
    {
        $this->makeRequestAndAssertOk('GET', 'index', 'Search functionality should exist', ['query' => 'pdf']);
        $this->assertTrue(true); // 确保有断言
    }

    public function testFiltersFunctionalityExists(): void
    {
        $params = ['filters[name]' => 'Images', 'filters[uploadType]' => 'both'];
        $this->makeRequestAndAssertOk('GET', 'index', 'Filters functionality should exist', $params);
        $this->assertTrue(true); // 确保有断言
    }

    public function testPaginationExists(): void
    {
        // 某些情况下无数据页码会重定向到第一页，因此允许 3xx
        $this->makeRequestAndAssertOk('GET', 'index', 'Pagination should exist', ['page' => '2']);
        $this->assertTrue(true); // 确保有断言
    }

    public function testSortingExists(): void
    {
        $this->makeRequestAndAssertOk('GET', 'index', 'Sorting should exist', ['sort[displayOrder]' => 'ASC']);
        $this->assertTrue(true); // 确保有断言
    }

    private function createTestFileType(): FileType
    {
        $em = self::getEntityManager();
        $fileType = new FileType();
        $uniqueId = uniqid();
        $fileType->setName('Test Type ' . $uniqueId);
        $fileType->setMimeType('application/test-' . $uniqueId);
        $fileType->setExtension('test' . substr($uniqueId, -6)); // 使用唯一扩展名
        $fileType->setMaxSize(1024);
        $fileType->setUploadType('both');
        $fileType->setDisplayOrder(1);
        $fileType->setIsActive(true);
        $em->persist($fileType);
        $em->flush();

        return $fileType;
    }

    /**
     * @param array<string, string> $params
     * @param array<string, mixed> $data
     */
    private function makeRequestAndAssertOk(
        string $method,
        string $action,
        string $message,
        array $params = [],
        array $data = [],
    ): void {
        $client = $this->createAuthenticatedClient();
        $url = $this->generateAdminUrl($action, $params);
        $client->request($method, $url, $data);
        $response = $client->getResponse();
        // 大多数 EasyAdmin 提交动作会返回重定向，统一接受 2xx 或 3xx
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            $message . sprintf(' (实际状态码: %d)', $response->getStatusCode())
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getValidFileTypeData(string $name = 'Test Type', int $maxSize = 1024): array
    {
        $uniqueId = uniqid();

        return [
            'FileType' => [
                'name' => $name . ' ' . $uniqueId,
                'mimeType' => 'application/test-' . $uniqueId,
                'extension' => 'test' . substr($uniqueId, -6), // 使用唯一扩展名
                'maxSize' => $maxSize,
                'uploadType' => 'both',
                'displayOrder' => 1,
                'isActive' => true,
            ],
        ];
    }

    private function assertValidationErrorsExist(Crawler $crawler): void
    {
        $errorMessages = $crawler->filter('.invalid-feedback');
        if ($errorMessages->count() > 0) {
            $this->assertStringContainsString('不能为空', $errorMessages->text());
        } else {
            $errorMessages = $crawler->filter('.form-error-message, .error, [class*="error"]');
            $this->assertGreaterThan(0, $errorMessages->count(), '应该显示验证错误信息');
        }
    }

    protected function getControllerService(): FileTypeCrudController
    {
        return self::getService(FileTypeCrudController::class);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '文件类型名称' => ['文件类型名称'];
        yield 'MIME类型' => ['MIME类型'];
        yield '文件扩展名' => ['文件扩展名'];
        yield '最大文件大小' => ['最大文件大小'];
        yield '上传类型' => ['上传类型'];
        yield '显示顺序' => ['显示顺序'];
        yield '是否激活' => ['是否激活'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'mimeType' => ['mimeType'];
        yield 'extension' => ['extension'];
        yield 'maxSize' => ['maxSize'];
        yield 'displayOrder' => ['displayOrder'];
        yield 'isActive' => ['isActive'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'mimeType' => ['mimeType'];
        yield 'extension' => ['extension'];
        yield 'maxSize' => ['maxSize'];
        yield 'uploadType' => ['uploadType'];
        yield 'displayOrder' => ['displayOrder'];
        yield 'isActive' => ['isActive'];
        yield 'description' => ['description'];
    }

    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();
        $crawler = $client->request('GET', $this->generateAdminUrl('new'));
        $this->assertResponseIsSuccessful('New page should be accessible');

        // 查找提交按钮 - 尝试多种可能的文本
        $submitButton = null;
        foreach (['创建', 'Create', 'Save', '保存', 'Submit'] as $buttonText) {
            try {
                $submitButton = $crawler->selectButton($buttonText);
                if ($submitButton->count() > 0) {
                    break;
                }
            } catch (\Exception) {
                // 继续尝试下一个
            }
        }

        $this->assertNotNull($submitButton, '找不到表单提交按钮');
        $this->assertGreaterThan(0, $submitButton->count(), '提交按钮应该存在');

        $form = $submitButton->form();
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertValidationErrorsExist($crawler);
    }
}
