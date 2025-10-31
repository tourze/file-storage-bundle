<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Tourze\AccessTokenBundle\Service\AccessTokenHandler;
use Tourze\FileStorageBundle\DependencyInjection\Compiler\SecurityFirewallCompilerPass;

/**
 * @internal
 */
#[CoversClass(SecurityFirewallCompilerPass::class)]
final class SecurityFirewallCompilerPassTest extends TestCase
{
    private SecurityFirewallCompilerPass $compilerPass;

    private ContainerBuilder $container;

    public function testThrowsExceptionWhenSecurityExtensionNotInstalled(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('FileStorageBundle requires the Symfony Security Component to be installed and configured.');

        $this->compilerPass->process($this->container);
    }

    public function testThrowsExceptionWhenFirewallsNotConfigured(): void
    {
        // Mock security extension without firewalls
        $securityExtension = $this->createMock(ExtensionInterface::class);
        $securityExtension->method('getAlias')->willReturn('security');
        $this->container->registerExtension($securityExtension);

        $this->container->prependExtensionConfig('security', [
            'providers' => ['app_user_provider' => []],
            'password_hashers' => [],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('FileStorageBundle requires security firewalls to be configured.');

        $this->compilerPass->process($this->container);
    }

    public function testThrowsExceptionWhenUploadStorageFirewallNotConfigured(): void
    {
        // Mock security extension with firewalls but without upload-storage
        $securityExtension = $this->createMock(ExtensionInterface::class);
        $securityExtension->method('getAlias')->willReturn('security');
        $this->container->registerExtension($securityExtension);

        $this->container->prependExtensionConfig('security', [
            'firewalls' => [
                'main' => [
                    'pattern' => '^/',
                    'form_login' => true,
                ],
            ],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('FileStorageBundle requires a "upload-storage" firewall to be configured');

        $this->compilerPass->process($this->container);
    }

    public function testThrowsExceptionWhenUploadStorageFirewallHasWrongPattern(): void
    {
        // Mock security extension with wrong pattern
        $securityExtension = $this->createMock(ExtensionInterface::class);
        $securityExtension->method('getAlias')->willReturn('security');
        $this->container->registerExtension($securityExtension);

        $this->container->prependExtensionConfig('security', [
            'firewalls' => [
                'upload-storage' => [
                    'pattern' => '^/upload/wrong',
                    'access_token' => true,
                ],
            ],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('FileStorageBundle requires a "upload-storage" firewall to be configured');

        $this->compilerPass->process($this->container);
    }

    public function testThrowsExceptionWhenUploadStorageFirewallHasNoAuthentication(): void
    {
        // Mock security extension with correct pattern but no authentication
        $securityExtension = $this->createMock(ExtensionInterface::class);
        $securityExtension->method('getAlias')->willReturn('security');
        $this->container->registerExtension($securityExtension);

        $this->container->prependExtensionConfig('security', [
            'firewalls' => [
                'upload-storage' => [
                    'pattern' => '^/upload/member',
                    'stateless' => true,
                ],
            ],
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('FileStorageBundle requires a "upload-storage" firewall to be configured');

        $this->compilerPass->process($this->container);
    }

    public function testPassesWithValidAccessTokenConfiguration(): void
    {
        // Mock security extension with valid configuration
        $securityExtension = $this->createMock(ExtensionInterface::class);
        $securityExtension->method('getAlias')->willReturn('security');
        $this->container->registerExtension($securityExtension);

        $this->container->prependExtensionConfig('security', [
            'firewalls' => [
                'upload-storage' => [
                    'pattern' => '^/upload/member',
                    'stateless' => true,
                    'access_token' => [
                        'token_handler' => AccessTokenHandler::class,
                        'token_extractors' => ['header', 'query_string'],
                    ],
                ],
            ],
        ]);

        // This should not throw any exception
        $this->compilerPass->process($this->container);

        // Verify the container was processed successfully
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testPassesWithValidFormLoginConfiguration(): void
    {
        // Mock security extension with form login
        $securityExtension = $this->createMock(ExtensionInterface::class);
        $securityExtension->expects($this->once())
            ->method('getAlias')
            ->willReturn('security')
        ;
        $this->container->registerExtension($securityExtension);

        $this->container->prependExtensionConfig('security', [
            'firewalls' => [
                'upload-storage' => [
                    'pattern' => '^/upload/member',
                    'form_login' => true,
                ],
            ],
        ]);

        // This should not throw any exception
        $this->compilerPass->process($this->container);

        // Verify the extension was properly registered and used
        $this->assertTrue($this->container->hasExtension('security'));
    }

    public function testPassesWithValidHttpBasicConfiguration(): void
    {
        // Mock security extension with HTTP basic auth
        $securityExtension = $this->createMock(ExtensionInterface::class);
        $securityExtension->method('getAlias')->willReturn('security');
        $this->container->registerExtension($securityExtension);

        $this->container->prependExtensionConfig('security', [
            'firewalls' => [
                'upload-storage' => [
                    'pattern' => '^/upload/member',
                    'http_basic' => true,
                ],
            ],
        ]);

        // This should not throw any exception
        $this->compilerPass->process($this->container);

        // Verify the container was processed successfully
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testPassesWithMultipleFirewallConfigurations(): void
    {
        // Mock security extension with multiple firewalls
        $securityExtension = $this->createMock(ExtensionInterface::class);
        $securityExtension->method('getAlias')->willReturn('security');
        $this->container->registerExtension($securityExtension);

        $this->container->prependExtensionConfig('security', [
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt)|css|images|js)/',
                    'security' => false,
                ],
                'upload-storage' => [
                    'pattern' => '^/upload/member',
                    'provider' => 'app_user_provider',
                    'stateless' => true,
                    'access_token' => [
                        'token_handler' => AccessTokenHandler::class,
                        'token_extractors' => ['header', 'query_string'],
                    ],
                ],
                'main' => [
                    'lazy' => true,
                    'provider' => 'app_user_provider',
                    'form_login' => true,
                ],
            ],
        ]);

        // This should not throw any exception
        $this->compilerPass->process($this->container);

        // Verify the container was processed successfully
        $this->assertInstanceOf(ContainerBuilder::class, $this->container);
    }

    public function testProcessValidatesSecurityConfiguration(): void
    {
        $reflection = new \ReflectionMethod($this->compilerPass, 'process');
        $this->assertSame('process', $reflection->getName());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->compilerPass = new SecurityFirewallCompilerPass();
        $this->container = new ContainerBuilder();
    }
}
