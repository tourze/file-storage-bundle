<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Tourze\FileStorageBundle\EventListener\CrudActionResolverListener;
use Tourze\FileStorageBundle\Service\CrudActionResolver;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(CrudActionResolverListener::class)]
#[RunTestsInSeparateProcesses]
final class CrudActionResolverListenerTest extends AbstractEventSubscriberTestCase
{
    private CrudActionResolverListener $listener;

    protected function onSetUp(): void
    {
        // 从容器获取监听器实例
        $this->listener = self::getService(CrudActionResolverListener::class);

        // 清除注册表中的实例
        CrudActionResolverRegistry::setInstance(null);
    }

    protected function onTearDown(): void
    {
        // 清理注册表
        CrudActionResolverRegistry::setInstance(null);
    }

    public function testOnKernelRequestWithMainRequest(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        // 确保注册表开始时为空
        $this->assertNull(CrudActionResolverRegistry::getInstance());

        $this->listener->onKernelRequest($event);

        // 验证实例已注册到注册表（不为null即可）
        $this->assertNotNull(CrudActionResolverRegistry::getInstance());
        $this->assertInstanceOf(CrudActionResolver::class, CrudActionResolverRegistry::getInstance());
    }

    public function testOnKernelRequestWithSubRequest(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        // 确保注册表开始时为空
        $this->assertNull(CrudActionResolverRegistry::getInstance());

        $this->listener->onKernelRequest($event);

        // 验证子请求不会注册实例
        $this->assertNull(CrudActionResolverRegistry::getInstance());
    }

    public function testOnKernelRequestMultipleCalls(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        $event = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        // 第一次调用
        $this->listener->onKernelRequest($event);
        $firstInstance = CrudActionResolverRegistry::getInstance();
        $this->assertNotNull($firstInstance);
        $this->assertInstanceOf(CrudActionResolver::class, $firstInstance);

        // 第二次调用（应该覆盖之前的实例）
        $this->listener->onKernelRequest($event);
        $secondInstance = CrudActionResolverRegistry::getInstance();
        $this->assertNotNull($secondInstance);
        $this->assertInstanceOf(CrudActionResolver::class, $secondInstance);
        $this->assertSame($firstInstance, $secondInstance);
    }

    public function testListenerHasCorrectEventConfiguration(): void
    {
        // 通过反射检查事件监听器的配置
        $reflectionClass = new \ReflectionClass(CrudActionResolverListener::class);
        $attributes = $reflectionClass->getAttributes();

        $this->assertCount(1, $attributes);

        $attribute = $attributes[0];
        $this->assertEquals('Symfony\Component\EventDispatcher\Attribute\AsEventListener', $attribute->getName());

        $arguments = $attribute->getArguments();
        $this->assertEquals(KernelEvents::REQUEST, $arguments['event']);
        $this->assertEquals(255, $arguments['priority']);
    }

    public function testConstructorReceivesCrudActionResolver(): void
    {
        // 验证监听器实例存在且类型正确
        $this->assertInstanceOf(CrudActionResolverListener::class, $this->listener);
    }

    public function testListenerIsReadonly(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverListener::class);

        // 验证类是 readonly
        $this->assertTrue($reflectionClass->isReadOnly());
    }

    public function testListenerIsFinal(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverListener::class);

        // 验证类是 final
        $this->assertTrue($reflectionClass->isFinal());
    }

    public function testOnKernelRequestMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverListener::class);
        $method = $reflectionClass->getMethod('onKernelRequest');

        // 验证方法签名
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());

        $returnType = $method->getReturnType();
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('void', $returnType->getName());
        }

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertEquals('event', $parameters[0]->getName());

        $parameterType = $parameters[0]->getType();
        if ($parameterType instanceof \ReflectionNamedType) {
            $this->assertEquals(RequestEvent::class, $parameterType->getName());
        }
    }

    public function testRegistryStateBeforeAndAfterMainRequest(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        // 确保开始时注册表为空
        $this->assertNull(CrudActionResolverRegistry::getInstance());

        // 创建主请求事件
        $mainEvent = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelRequest($mainEvent);

        // 验证主请求后注册表有实例
        $this->assertNotNull(CrudActionResolverRegistry::getInstance());
        $this->assertInstanceOf(CrudActionResolver::class, CrudActionResolverRegistry::getInstance());

        // 创建子请求事件
        $subEvent = new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelRequest($subEvent);

        // 验证子请求不影响注册表中的实例
        $instance = CrudActionResolverRegistry::getInstance();
        $this->assertNotNull($instance);
        $this->assertInstanceOf(CrudActionResolver::class, $instance);
    }

    public function testEventListenerPriority(): void
    {
        // 验证监听器的优先级设置正确（255是很高的优先级）
        $reflectionClass = new \ReflectionClass(CrudActionResolverListener::class);
        $attributes = $reflectionClass->getAttributes();

        $attribute = $attributes[0];
        $arguments = $attribute->getArguments();

        // 高优先级确保这个监听器在其他监听器之前执行
        $this->assertEquals(255, $arguments['priority']);
        $this->assertGreaterThan(0, $arguments['priority']);
    }

    public function testListenerRegistersResolverToRegistry(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // 确认初始状态为空
        $this->assertNull(CrudActionResolverRegistry::getInstance());

        // 调用监听器
        $this->listener->onKernelRequest($event);

        // 验证解析器已被注册到注册表
        $this->assertNotNull(CrudActionResolverRegistry::getInstance());
        $this->assertInstanceOf(CrudActionResolver::class, CrudActionResolverRegistry::getInstance());
    }
}
