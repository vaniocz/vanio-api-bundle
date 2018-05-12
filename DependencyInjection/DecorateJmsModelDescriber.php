<?php
namespace Vanio\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DecorateJmsModelDescriber implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('nelmio_api_doc.model_describers.jms')) {
            return;
        }

        $container
            ->getDefinition('vanio_api.api_doc.model_describer')
            ->setAbstract(false)
            ->setDecoratedService('nelmio_api_doc.model_describers.jms');
    }
}
