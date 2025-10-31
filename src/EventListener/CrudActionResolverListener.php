<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tourze\FileStorageBundle\Service\CrudActionResolver;
use Tourze\FileStorageBundle\Service\CrudActionResolverRegistry;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 255)]
final readonly class CrudActionResolverListener
{
    public function __construct(
        private CrudActionResolver $crudActionResolver,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // 只在主请求时注册
        if (!$event->isMainRequest()) {
            return;
        }

        CrudActionResolverRegistry::setInstance($this->crudActionResolver);
    }
}
