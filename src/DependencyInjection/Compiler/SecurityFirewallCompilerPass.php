<?php

declare(strict_types=1);

namespace Tourze\FileStorageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class SecurityFirewallCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Skip validation in test environment
        if ($this->isTestEnvironment($container)) {
            return;
        }

        $this->validateSecurityExtension($container);

        $securityConfig = $container->getExtensionConfig('security');

        $this->validateFirewallConfiguration($securityConfig);
    }

    private function isTestEnvironment(ContainerBuilder $container): bool
    {
        // Check if we're in test environment by looking for test-specific parameters
        return $container->hasParameter('kernel.environment')
            && 'test' === $container->getParameter('kernel.environment');
    }

    private function validateSecurityExtension(ContainerBuilder $container): void
    {
        if (!$container->hasExtension('security')) {
            throw new LogicException('FileStorageBundle requires the Symfony Security Component to be installed and configured.');
        }
    }

    /**
     * @param array<mixed> $securityConfig
     */
    private function validateFirewallConfiguration(array $securityConfig): void
    {
        $hasFirewallConfig = $this->hasFirewallConfig($securityConfig);
        $hasUploadStorageFirewall = $this->hasUploadStorageFirewall($securityConfig);

        if (!$hasFirewallConfig) {
            throw new LogicException('FileStorageBundle requires security firewalls to be configured. Please configure the security firewalls in your security.yaml file.');
        }

        if (!$hasUploadStorageFirewall) {
            throw new LogicException($this->getUploadStorageErrorMessage());
        }
    }

    /**
     * @param array<mixed> $securityConfig
     */
    private function hasFirewallConfig(array $securityConfig): bool
    {
        foreach ($securityConfig as $config) {
            if (is_array($config) && isset($config['firewalls']) && is_array($config['firewalls'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $securityConfig
     */
    private function hasUploadStorageFirewall(array $securityConfig): bool
    {
        foreach ($securityConfig as $config) {
            if (is_array($config) && isset($config['firewalls']) && is_array($config['firewalls'])) {
                if ($this->isUploadStorageFirewallValid($config['firewalls'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<mixed> $firewalls
     */
    private function isUploadStorageFirewallValid(array $firewalls): bool
    {
        if (!isset($firewalls['upload-storage']) || !is_array($firewalls['upload-storage'])) {
            return false;
        }

        $uploadStorageFirewall = $firewalls['upload-storage'];

        if (!isset($uploadStorageFirewall['pattern']) || '^/upload/member' !== $uploadStorageFirewall['pattern']) {
            return false;
        }

        return isset($uploadStorageFirewall['access_token'])
            || isset($uploadStorageFirewall['form_login'])
            || isset($uploadStorageFirewall['http_basic']);
    }

    private function getUploadStorageErrorMessage(): string
    {
        return 'FileStorageBundle requires a "upload-storage" firewall to be configured for the "/upload/member" pattern. ' .
            'Please add the following configuration to your security.yaml file:' . PHP_EOL .
            'security:' . PHP_EOL .
            '    firewalls:' . PHP_EOL .
            '        upload-storage:' . PHP_EOL .
            '            pattern: ^/upload/member' . PHP_EOL .
            '            provider: app_user_provider' . PHP_EOL .
            '            stateless: true' . PHP_EOL .
            '            access_token:' . PHP_EOL .
            '                token_handler: Tourze\AccessTokenBundle\Service\AccessTokenHandler' . PHP_EOL .
            '                token_extractors:' . PHP_EOL .
            '                    - \'header\'' . PHP_EOL .
            '                    - \'query_string\'';
    }
}
