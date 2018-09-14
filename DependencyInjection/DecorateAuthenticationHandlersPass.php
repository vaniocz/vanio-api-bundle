<?php
namespace Vanio\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DecorateAuthenticationHandlersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->decorateAuthenticationFailureHandler($container);
        $this->decorateAuthenticationSuccessHandler($container);
    }

    /**
     * Manual decoration of the original abstract service (prototype for actual default failure handler).
     * Builtin decoration cannot be used because DecoratorServicePass is handled after ResolveChildDefinitionsPass.
     */
    private function decorateAuthenticationFailureHandler(ContainerBuilder $container): void
    {
        $container->setDefinition(
            'vanio_api.security.authentication_failure_handler.inner',
            (clone $container->getDefinition('security.authentication.failure_handler'))->setAbstract(false)
        );
        $container->setDefinition(
            'security.authentication.failure_handler',
            $container->getDefinition('vanio_api.security.authentication_failure_handler')
        );
        $container->removeDefinition('vanio_api.security.authentication_failure_handler');
    }

    /**
     * Manual decoration of the original abstract service (prototype for actual default success handler).
     * Builtin decoration cannot be used because DecoratorServicePass is handled after ResolveChildDefinitionsPass.
     */
    private function decorateAuthenticationSuccessHandler(ContainerBuilder $container): void
    {
        $container->setDefinition(
            'vanio_api.security.authentication_success_handler.inner',
            (clone $container->getDefinition('security.authentication.success_handler'))->setAbstract(false)
        );
        $container->setDefinition(
            'security.authentication.success_handler',
            $container->getDefinition('vanio_api.security.authentication_success_handler')
        );
        $container->removeDefinition('vanio_api.security.authentication_success_handler');
    }
}
