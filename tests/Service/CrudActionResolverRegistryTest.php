<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\FileStorageBundle\Service\CrudActionResolverInterface;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;

/**
 * @internal
 */
#[CoversClass(CrudActionResolverRegistry::class)]
final class CrudActionResolverRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 清除注册表中的实例，确保每个测试开始时状态干净
        CrudActionResolverRegistry::setInstance(null);
    }

    protected function tearDown(): void
    {
        // 清理注册表，确保测试之间不会相互影响
        CrudActionResolverRegistry::setInstance(null);
        parent::tearDown();
    }

    public function testSetAndGetInstance(): void
    {
        $resolver = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };

        // 设置实例
        CrudActionResolverRegistry::setInstance($resolver);

        // 验证能正确获取实例
        $this->assertSame($resolver, CrudActionResolverRegistry::getInstance());
    }

    public function testGetInstanceReturnsNullInitially(): void
    {
        // 初始状态下应该返回 null
        $this->assertNull(CrudActionResolverRegistry::getInstance());
    }

    public function testSetInstanceWithNull(): void
    {
        $resolver = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };

        // 先设置一个实例
        CrudActionResolverRegistry::setInstance($resolver);
        $this->assertSame($resolver, CrudActionResolverRegistry::getInstance());

        // 设置为 null
        CrudActionResolverRegistry::setInstance(null);
        $this->assertNull(CrudActionResolverRegistry::getInstance());
    }

    public function testSetInstanceOverridesPreviousInstance(): void
    {
        $resolver1 = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };
        $resolver2 = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };

        // 设置第一个实例
        CrudActionResolverRegistry::setInstance($resolver1);
        $this->assertSame($resolver1, CrudActionResolverRegistry::getInstance());

        // 设置第二个实例，应该覆盖第一个
        CrudActionResolverRegistry::setInstance($resolver2);
        $this->assertSame($resolver2, CrudActionResolverRegistry::getInstance());
        $this->assertNotSame($resolver1, CrudActionResolverRegistry::getInstance());
    }

    public function testMultipleCallsToGetInstanceReturnSameObject(): void
    {
        $resolver = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };

        CrudActionResolverRegistry::setInstance($resolver);

        $instance1 = CrudActionResolverRegistry::getInstance();
        $instance2 = CrudActionResolverRegistry::getInstance();
        $instance3 = CrudActionResolverRegistry::getInstance();

        // 多次调用应该返回相同的实例
        $this->assertSame($resolver, $instance1);
        $this->assertSame($resolver, $instance2);
        $this->assertSame($resolver, $instance3);
        $this->assertSame($instance1, $instance2);
        $this->assertSame($instance2, $instance3);
    }

    public function testRegistryIsFinalClass(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverRegistry::class);

        // 验证类是 final，不能被继承
        $this->assertTrue($reflectionClass->isFinal());
    }

    public function testRegistryMethodsAreStatic(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverRegistry::class);

        $setInstanceMethod = $reflectionClass->getMethod('setInstance');
        $getInstanceMethod = $reflectionClass->getMethod('getInstance');

        // 验证方法都是静态的
        $this->assertTrue($setInstanceMethod->isStatic());
        $this->assertTrue($getInstanceMethod->isStatic());
        $this->assertTrue($setInstanceMethod->isPublic());
        $this->assertTrue($getInstanceMethod->isPublic());
    }

    public function testSetInstanceMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverRegistry::class);
        $method = $reflectionClass->getMethod('setInstance');

        // 验证方法签名
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $returnType = $method->getReturnType();
        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals('void', $returnType->getName());
        }

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters);

        $parameter = $parameters[0];
        $this->assertEquals('resolver', $parameter->getName());
        $this->assertTrue($parameter->allowsNull());

        $parameterType = $parameter->getType();
        if ($parameterType instanceof \ReflectionNamedType) {
            $this->assertEquals(CrudActionResolverInterface::class, $parameterType->getName());
        }
    }

    public function testGetInstanceMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverRegistry::class);
        $method = $reflectionClass->getMethod('getInstance');

        // 验证方法签名
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());

        if ($returnType instanceof \ReflectionNamedType) {
            $this->assertEquals(CrudActionResolverInterface::class, $returnType->getName());
        }

        $parameters = $method->getParameters();
        $this->assertCount(0, $parameters);
    }

    public function testRegistryStaticProperty(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverRegistry::class);
        $property = $reflectionClass->getProperty('instance');

        // 验证静态属性
        $this->assertTrue($property->isStatic());
        $this->assertTrue($property->isPrivate());
    }

    public function testRegistryWithMultipleDifferentResolvers(): void
    {
        $resolver1 = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };
        $resolver2 = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };
        $resolver3 = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };

        $resolvers = [$resolver1, $resolver2, $resolver3];

        foreach ($resolvers as $resolver) {
            CrudActionResolverRegistry::setInstance($resolver);
            $this->assertSame($resolver, CrudActionResolverRegistry::getInstance());
        }

        // 最后设置的应该是 resolver3
        $this->assertSame($resolver3, CrudActionResolverRegistry::getInstance());
    }

    public function testRegistryNullHandling(): void
    {
        $resolver = new class implements CrudActionResolverInterface {
            public function getCurrentCrudAction(): ?string
            {
                return null;
            }
        };

        // 初始状态
        $this->assertNull(CrudActionResolverRegistry::getInstance());

        // 设置实例
        CrudActionResolverRegistry::setInstance($resolver);
        $this->assertNotNull(CrudActionResolverRegistry::getInstance());

        // 设置为 null
        CrudActionResolverRegistry::setInstance(null);
        $this->assertNull(CrudActionResolverRegistry::getInstance());

        // 再次设置实例
        CrudActionResolverRegistry::setInstance($resolver);
        $this->assertNotNull(CrudActionResolverRegistry::getInstance());
        $this->assertSame($resolver, CrudActionResolverRegistry::getInstance());
    }

    public function testRegistryClassDocumentation(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverRegistry::class);
        $docComment = $reflectionClass->getDocComment();

        // 验证类文档注释存在且包含预期内容
        $this->assertNotFalse($docComment);
        $this->assertStringContainsString('全局注册表', $docComment);
        $this->assertStringContainsString('静态上下文中访问CrudActionResolver服务', $docComment);
        $this->assertStringContainsString('EasyAdmin字段类无法直接注入服务的问题', $docComment);
    }

    public function testRegistryCannotBeInstantiated(): void
    {
        $reflectionClass = new \ReflectionClass(CrudActionResolverRegistry::class);

        // 获取构造函数
        $constructor = $reflectionClass->getConstructor();

        if (null !== $constructor) {
            // 如果有构造函数，它不应该是公共的
            $this->assertFalse($constructor->isPublic(), 'Constructor should not be public to prevent instantiation');
        }

        // 验证类没有公共构造函数（可以是私有构造函数或者没有构造函数）
        $this->assertTrue(
            null === $constructor || !$constructor->isPublic(),
            'Registry class should not have a public constructor to prevent instantiation'
        );
    }
}
