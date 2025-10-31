<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Tourze\FileStorageBundle\Controller\GetAllowedTypesAnonymousController;
use Tourze\FileStorageBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    public function testLoadControllerWithRouteAttributes(): void
    {
        $routes = $this->loader->load(GetAllowedTypesAnonymousController::class);

        $this->assertInstanceOf(RouteCollection::class, $routes);
        $this->assertCount(1, $routes);

        $route = $routes->get('file_allowed_types_anonymous');
        $this->assertInstanceOf(Route::class, $route);
        $this->assertNotNull($route);
        $this->assertEquals('/allowed-types/anonymous', $route->getPath());
        $this->assertEquals(['GET'], $route->getMethods());
        $this->assertEquals(GetAllowedTypesAnonymousController::class, $route->getDefault('_controller'));
    }

    public function testSupportsControllerClass(): void
    {
        $this->assertTrue($this->loader->supports(GetAllowedTypesAnonymousController::class));
        $this->assertTrue($this->loader->supports(GetAllowedTypesAnonymousController::class, 'annotation'));
        $this->assertTrue($this->loader->supports(GetAllowedTypesAnonymousController::class, 'attribute'));
    }

    public function testDoesNotSupportNonControllerResource(): void
    {
        $this->assertFalse($this->loader->supports('some/file.php'));
        $this->assertFalse($this->loader->supports(__DIR__));
        $this->assertFalse($this->loader->supports('NonExistentClass'));
    }

    public function testDoesNotSupportWrongType(): void
    {
        $this->assertFalse($this->loader->supports(GetAllowedTypesAnonymousController::class, 'yaml'));
        $this->assertFalse($this->loader->supports(GetAllowedTypesAnonymousController::class, 'xml'));
    }

    public function testAutoloadLoadsAllControllers(): void
    {
        $reflection = new \ReflectionMethod($this->loader, 'autoload');
        $this->assertSame('autoload', $reflection->getName());
    }

    protected function onSetUp(): void
    {
        // 从容器中获取服务实例
        $this->loader = self::getService(AttributeControllerLoader::class);
    }
}
