<?php
namespace Vanio\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterExpressionLanguageProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container
            ->getDefinition('jms_serializer.expression_language')
            ->addMethodCall('registerProvider', [new Reference('vanio_api.serializer.expression_language_provider')]);
    }
}
