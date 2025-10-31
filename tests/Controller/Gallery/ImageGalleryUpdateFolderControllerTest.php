<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Controller\Gallery;

use Doctrine\Bundle\DoctrineBundle\Registry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\FileStorageBundle\Controller\Gallery\ImageGalleryUpdateFolderController;
use Tourze\FileStorageBundle\Entity\Folder;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ImageGalleryUpdateFolderController::class)]
#[RunTestsInSeparateProcesses]
final class ImageGalleryUpdateFolderControllerTest extends AbstractWebTestCase
{
    protected function onSetUp(): void
    {
        // No additional setup needed
    }

    public function testUpdateFolderSuccessfully(): void
    {
        $client = self::createClientWithDatabase();
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $entityManager = $doctrine->getManager();

        // 创建测试文件夹
        $folder = new Folder();
        $folder->setName('Original Folder');
        $folder->setDescription('Original description');
        $folder->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($folder);
        $entityManager->flush();

        $folderId = $folder->getId();

        // 更新文件夹
        $updateData = [
            'name' => 'Updated Folder Name',
            'description' => 'Updated description',
        ];

        $client->request(
            'PUT',
            "/gallery/api/folders/{$folderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($updateData) ? json_encode($updateData) : ''
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertSame(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertSame('application/json', $client->getResponse()->headers->get('content-type'));

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('文件夹更新成功', $responseData['message']);

        $updatedFolderData = $responseData['data'];
        $this->assertIsArray($updatedFolderData);
        $this->assertArrayHasKey('name', $updatedFolderData);
        $this->assertArrayHasKey('description', $updatedFolderData);
        $this->assertEquals('Updated Folder Name', $updatedFolderData['name']);
        $this->assertEquals('Updated description', $updatedFolderData['description']);

        // 清理 - 重新获取实体以避免 detached entity 错误
        $freshFolder = $entityManager->find(Folder::class, $folderId);
        if (null !== $freshFolder) {
            $entityManager->remove($freshFolder);
            $entityManager->flush();
        }
    }

    public function testUpdateFolderWithNameOnly(): void
    {
        $client = self::createClientWithDatabase();
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $entityManager = $doctrine->getManager();

        $folder = new Folder();
        $folder->setName('Test Folder');
        $folder->setDescription('Test description');
        $folder->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($folder);
        $entityManager->flush();

        $folderId = $folder->getId();

        // 只更新名称
        $updateData = [
            'name' => 'New Name Only',
        ];

        $client->request(
            'PUT',
            "/gallery/api/folders/{$folderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($updateData) ? json_encode($updateData) : ''
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertTrue($responseData['success']);
        $updatedFolderData = $responseData['data'];
        $this->assertIsArray($updatedFolderData);
        $this->assertArrayHasKey('name', $updatedFolderData);
        $this->assertEquals('New Name Only', $updatedFolderData['name']);

        // 清理 - 重新获取实体以避免 detached entity 错误
        $freshFolder = $entityManager->find(Folder::class, $folderId);
        if (null !== $freshFolder) {
            $entityManager->remove($freshFolder);
            $entityManager->flush();
        }
    }

    public function testUpdateFolderWithEmptyDescription(): void
    {
        $client = self::createClientWithDatabase();
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $entityManager = $doctrine->getManager();

        $folder = new Folder();
        $folder->setName('Test Folder');
        $folder->setDescription('Original description');
        $folder->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($folder);
        $entityManager->flush();

        $folderId = $folder->getId();

        // 设置空描述
        $updateData = [
            'name' => 'Updated Name',
            'description' => '',
        ];

        $client->request(
            'PUT',
            "/gallery/api/folders/{$folderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($updateData) ? json_encode($updateData) : ''
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertTrue($responseData['success']);
        $updatedFolderData = $responseData['data'];
        $this->assertIsArray($updatedFolderData);
        $this->assertArrayHasKey('name', $updatedFolderData);
        $this->assertEquals('Updated Name', $updatedFolderData['name']);

        // 清理 - 重新获取实体以避免 detached entity 错误
        $freshFolder = $entityManager->find(Folder::class, $folderId);
        if (null !== $freshFolder) {
            $entityManager->remove($freshFolder);
            $entityManager->flush();
        }
    }

    public function testUpdateFolderWithInvalidId(): void
    {
        $client = self::createClientWithDatabase();

        $updateData = [
            'name' => 'New Name',
        ];

        $client->request(
            'PUT',
            '/gallery/api/folders/99999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($updateData) ? json_encode($updateData) : ''
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertFalse($responseData['success']);
        $this->assertEquals('文件夹未找到', $responseData['error']);
    }

    public function testUpdateFolderWithoutName(): void
    {
        $client = self::createClientWithDatabase();

        $updateData = [
            'description' => 'Only description',
        ];

        $client->request(
            'PUT',
            '/gallery/api/folders/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($updateData) ? json_encode($updateData) : ''
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertFalse($responseData['success']);
        $this->assertEquals('文件夹名称是必需的', $responseData['error']);
    }

    public function testUpdateFolderWithEmptyName(): void
    {
        $client = self::createClientWithDatabase();
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $entityManager = $doctrine->getManager();

        $folder = new Folder();
        $folder->setName('Test Folder');
        $folder->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($folder);
        $entityManager->flush();

        $folderId = $folder->getId();

        // 设置空名称
        $updateData = [
            'name' => '',
        ];

        $client->request(
            'PUT',
            "/gallery/api/folders/{$folderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($updateData) ? json_encode($updateData) : ''
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertFalse($responseData['success']);
        $this->assertEquals('文件夹名称不能为空', $responseData['error']);

        // 清理 - 重新获取实体以避免 detached entity 错误
        $freshFolder = $entityManager->find(Folder::class, $folderId);
        if (null !== $freshFolder) {
            $entityManager->remove($freshFolder);
            $entityManager->flush();
        }
    }

    public function testUpdateFolderWithWhitespaceOnlyName(): void
    {
        $client = self::createClientWithDatabase();
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $entityManager = $doctrine->getManager();

        $folder = new Folder();
        $folder->setName('Test Folder');
        $folder->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($folder);
        $entityManager->flush();

        $folderId = $folder->getId();

        // 设置只有空格的名称
        $updateData = [
            'name' => '   ',
        ];

        $client->request(
            'PUT',
            "/gallery/api/folders/{$folderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($updateData) ? json_encode($updateData) : ''
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertFalse($responseData['success']);
        $this->assertEquals('文件夹名称不能为空', $responseData['error']);

        // 清理 - 重新获取实体以避免 detached entity 错误
        $freshFolder = $entityManager->find(Folder::class, $folderId);
        if (null !== $freshFolder) {
            $entityManager->remove($freshFolder);
            $entityManager->flush();
        }
    }

    public function testUpdateFolderWithInvalidJson(): void
    {
        $client = self::createClientWithDatabase();

        $client->request(
            'PUT',
            '/gallery/api/folders/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertFalse($responseData['success']);
        $this->assertEquals('文件夹名称是必需的', $responseData['error']);
    }

    public function testUpdateFolderWithNullData(): void
    {
        $client = self::createClientWithDatabase();

        $client->request(
            'PUT',
            '/gallery/api/folders/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'null'
        );

        $this->assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertFalse($responseData['success']);
        $this->assertEquals('文件夹名称是必需的', $responseData['error']);
    }

    public function testUpdateFolderWithDifferentHttpMethods(): void
    {
        $client = self::createClientWithDatabase();
        $client->catchExceptions(true);

        $updateData = [
            'name' => 'Test Name',
        ];

        // 测试不支持的HTTP方法
        $client->request('GET', '/gallery/api/folders/1');
        $this->assertSame(405, $client->getResponse()->getStatusCode());

        $client->request('POST', '/gallery/api/folders/1');
        $this->assertSame(405, $client->getResponse()->getStatusCode());

        // DELETE 方法实际上是支持的（匹配了另一个控制器），所以不会返回405
        // 不测试DELETE，因为它匹配了 ImageGalleryDeleteFolderController
    }

    public function testUpdateFolderTrimsInputData(): void
    {
        $client = self::createClientWithDatabase();
        /** @var Registry $doctrine */
        $doctrine = self::getContainer()->get('doctrine');
        $entityManager = $doctrine->getManager();

        $folder = new Folder();
        $folder->setName('Test Folder');
        $folder->setCreateTime(new \DateTimeImmutable());

        $entityManager->persist($folder);
        $entityManager->flush();

        $folderId = $folder->getId();

        // 使用有空格的数据
        $updateData = [
            'name' => '  Trimmed Name  ',
            'description' => '  Trimmed Description  ',
        ];

        $client->request(
            'PUT',
            "/gallery/api/folders/{$folderId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            false !== json_encode($updateData) ? json_encode($updateData) : ''
        );

        $this->assertTrue($client->getResponse()->isSuccessful());
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertTrue($responseData['success']);
        $updatedFolderData = $responseData['data'];
        $this->assertIsArray($updatedFolderData);
        $this->assertArrayHasKey('name', $updatedFolderData);
        $this->assertEquals('Trimmed Name', $updatedFolderData['name']); // 应该被trim

        // 清理 - 重新获取实体以避免 detached entity 错误
        $freshFolder = $entityManager->find(Folder::class, $folderId);
        if (null !== $freshFolder) {
            $entityManager->remove($freshFolder);
            $entityManager->flush();
        }
    }

    #[Test]
    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        // DELETE 方法实际上匹配了另一个路由（ImageGalleryDeleteFolderController），
        // 所以不会抛出 MethodNotAllowedHttpException
        if ('DELETE' === $method) {
            $client->request($method, '/gallery/api/folders/1');
            // DELETE 路由存在，只是控制器不同，所以会正常响应或返回其他状态码
            $this->assertNotEquals(404, $client->getResponse()->getStatusCode());

            return;
        }

        $client->catchExceptions(false);
        $this->expectException(MethodNotAllowedHttpException::class);
        $client->request($method, '/gallery/api/folders/1');
    }
}
