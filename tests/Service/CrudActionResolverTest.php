<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\FileStorageBundle\Service\CrudActionResolver;

/**
 * @internal
 */
#[CoversClass(CrudActionResolver::class)]
final class CrudActionResolverTest extends TestCase
{
    public function testGetCurrentCrudActionWithValidAction(): void
    {
        $request = new Request(['crudAction' => 'edit']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $resolver = new CrudActionResolver($requestStack);

        $this->assertEquals('edit', $resolver->getCurrentCrudAction());
    }

    public function testGetCurrentCrudActionWithoutParameter(): void
    {
        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $resolver = new CrudActionResolver($requestStack);

        $this->assertNull($resolver->getCurrentCrudAction());
    }

    public function testGetCurrentCrudActionWithNullValue(): void
    {
        $request = new Request(['crudAction' => null]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $resolver = new CrudActionResolver($requestStack);

        $this->assertNull($resolver->getCurrentCrudAction());
    }

    public function testGetCurrentCrudActionWithNoCurrentRequest(): void
    {
        $requestStack = new RequestStack();

        $resolver = new CrudActionResolver($requestStack);

        $this->assertNull($resolver->getCurrentCrudAction());
    }

    public function testGetCurrentCrudActionWithNonStringValue(): void
    {
        $request = new Request(['crudAction' => 123]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $resolver = new CrudActionResolver($requestStack);

        $this->assertEquals('123', $resolver->getCurrentCrudAction());
    }

    public function testGetCurrentCrudActionWithDifferentActions(): void
    {
        $requestStack = new RequestStack();
        $resolver = new CrudActionResolver($requestStack);

        $actions = ['edit', 'new', 'detail', 'index'];

        foreach ($actions as $action) {
            $request = new Request(['crudAction' => $action]);
            $requestStack->push($request);

            $this->assertEquals($action, $resolver->getCurrentCrudAction());

            // 移除请求以测试下一个
            $requestStack->pop();
        }
    }
}
