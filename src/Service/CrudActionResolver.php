<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

final readonly class CrudActionResolver implements CrudActionResolverInterface
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    /**
     * 获取当前CRUD操作类型
     */
    public function getCurrentCrudAction(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        $crudAction = $request->query->get('crudAction');
        if (null === $crudAction) {
            return null;
        }

        return (string) $crudAction;
    }
}
