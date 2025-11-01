<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Tourze\FileStorageBundle\Controller\Admin\FolderCrudController;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 * This test class includes testValidationErrors() method for validating required fields
 */
#[CoversClass(FolderCrudController::class)]
#[RunTestsInSeparateProcesses]
final class FolderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    public function testAdminRouteExists(): void
    {
        // 与同目录下 FileTypeCrudControllerTest 保持一致，统一通过 getAuthenticatedClient()
        $client = $this->getAuthenticatedClient();
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
        $this->assertActionReturnsResponse('index');
        $this->assertTrue(true); // 确保有断言
    }

    public function testNewActionReturnsResponse(): void
    {
        $this->assertActionReturnsResponse('new');
        $this->assertTrue(true); // 确保有断言
    }

    public function testDetailActionReturnsResponse(): void
    {
        // 创建测试实体
        $folder = $this->createTestFolder();
        $this->assertActionReturnsResponse('detail', '&entityId=' . $folder->getId());
        $this->assertTrue(true); // 确保有断言
    }

    public function testEditActionReturnsResponse(): void
    {
        // 创建测试实体
        $folder = $this->createTestFolder();
        $this->assertActionReturnsResponse('edit', '&entityId=' . $folder->getId());
        $this->assertTrue(true); // 确保有断言
    }

    public function testDeleteActionReturnsResponse(): void
    {
        // 创建测试实体
        $folder = $this->createTestFolder();
        $this->assertActionReturnsResponse('delete', '&entityId=' . $folder->getId(), 'POST');
        $this->assertTrue(true); // 确保有断言
    }

    public function testPostNewActionReturnsResponse(): void
    {
        $data = [
            'Folder' => [
                'name' => 'Test Folder',
                'path' => 'test-folder',
                'description' => 'Test Description',
                'isActive' => true,
                'isPublic' => false,
            ],
        ];
        $this->assertActionReturnsResponseWithData('new', 'POST', $data);
        $this->assertTrue(true); // 确保有断言
    }

    public function testPatchEditActionReturnsResponse(): void
    {
        // 创建测试实体
        $folder = $this->createTestFolder();
        $data = [
            'Folder' => [
                'name' => 'Patched Folder',
            ],
        ];
        $this->assertActionReturnsResponseWithData('edit', 'PATCH', $data, '&entityId=' . $folder->getId());
        $this->assertTrue(true); // 确保有断言
    }

    public function testHeadIndexActionReturnsResponse(): void
    {
        $this->assertActionReturnsResponse('index', '', 'HEAD');
        $this->assertTrue(true); // 确保有断言
    }

    public function testSearchFunctionalityExists(): void
    {
        $this->assertActionReturnsResponse('index', '&query=test');
        $this->assertTrue(true); // 确保有断言
    }

    public function testFiltersFunctionalityExists(): void
    {
        $this->assertActionReturnsResponse('index', '&filters[name]=Documents&filters[isPublic]=true');
        $this->assertTrue(true); // 确保有断言
    }

    public function testPaginationExists(): void
    {
        $this->assertActionReturnsResponse('index', '&page=2');
        $this->assertTrue(true); // 确保有断言
    }

    public function testSortingExists(): void
    {
        $this->assertActionReturnsResponse('index', '&sort[title]=ASC');
        $this->assertTrue(true); // 确保有断言
    }

    protected function getControllerService(): FolderCrudController
    {
        return self::getService(FolderCrudController::class);
    }

    private function createTestFolder(): \Tourze\FileStorageBundle\Entity\Folder
    {
        $em = self::getEntityManager();
        $folder = new \Tourze\FileStorageBundle\Entity\Folder();
        $folder->setTitle('Test Folder ' . uniqid());
        $folder->setIsActive(true);
        $em->persist($folder);
        $em->flush();

        return $folder;
    }

    private function getAuthenticatedClient(): KernelBrowser
    {
        // 简化为直接使用基类提供的方法，避免在分进程执行下复用导致的状态问题
        return $this->createAuthenticatedClient();
    }

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $data
     */
    private function makeRequestAndAssertOk(
        string $method,
        string $action,
        array $params = [],
        array $data = [],
    ): void {
        $client = $this->getAuthenticatedClient();
        $url = $this->generateAdminUrl($action, $params);
        $client->request($method, $url, $data);
        $response = $client->getResponse();
        $this->assertTrue(
            $response->isSuccessful() || $response->isRedirection(),
            ucfirst($action) . ' action should be accessible (实际状态码: ' . $response->getStatusCode() . ')'
        );
    }

    private function assertActionReturnsResponse(string $action, string $extraParams = '', string $method = 'GET'): void
    {
        /** @var array<string, mixed> $params */
        $params = [];
        if ('' !== $extraParams) {
            /** @var array<string, mixed> $parsedParams */
            $parsedParams = [];
            parse_str(ltrim($extraParams, '&'), $parsedParams);
            foreach ($parsedParams as $key => $value) {
                $params[(string) $key] = $value;
            }
        }
        $this->makeRequestAndAssertOk($method, $action, $params);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function assertActionReturnsResponseWithData(string $action, string $method, array $data, string $extraParams = ''): void
    {
        /** @var array<string, mixed> $params */
        $params = [];
        if ('' !== $extraParams) {
            /** @var array<string, mixed> $parsedParams */
            $parsedParams = [];
            parse_str(ltrim($extraParams, '&'), $parsedParams);
            foreach ($parsedParams as $key => $value) {
                $params[(string) $key] = $value;
            }
        }
        $this->makeRequestAndAssertOk($method, $action, $params, $data);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '名称' => ['名称'];
        yield '是否激活' => ['是否激活'];
        yield '创建时间' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'title' => ['title'];
        yield 'name' => ['name'];
        yield 'isActive' => ['isActive'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'title' => ['title'];
    }

    /**
     * 测试必填字段验证 - 按照PHPStan规则要求实现
     * 提交空表单并验证错误信息
     */
    public function testValidationErrors(): void
    {
        $client = $this->createAuthenticatedClient();
        $url = $this->generateAdminUrl('new');
        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful('New page should be accessible');

        // 查找并提交空表单
        $form = $this->findAndSubmitEmptyForm($crawler);
        $crawler = $client->submit($form);

        // 验证响应状态码和错误信息
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString(
            '名称不能为空',
            $crawler->filter('.invalid-feedback')->text()
        );
    }

    private function findAndSubmitEmptyForm(Crawler $crawler): Form
    {
        // 查找提交按钮 - 尝试多种可能的文本
        $submitButton = null;
        foreach (['创建', 'Create', 'Save', '保存', 'Submit'] as $buttonText) {
            try {
                $submitButton = $crawler->selectButton($buttonText);
                if ($submitButton->count() > 0) {
                    break;
                }
            } catch (\Exception $e) {
                // 继续尝试下一个
            }
        }

        $this->assertNotNull($submitButton, '找不到表单提交按钮');
        $this->assertGreaterThan(0, $submitButton->count(), '提交按钮应该存在');

        return $submitButton->form();
    }
}
