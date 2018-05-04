<?php
namespace Vanio\ApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelEvents;

class PrioritizeAddRequestFormatsListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('request.add_request_formats_listener')) {
            return;
        }

        $container
            ->getDefinition('request.add_request_formats_listener')
            ->clearTags()
            ->addTag('kernel.event_listener', [
                'event' => KernelEvents::REQUEST,
                'method' => 'onKernelRequest',
                'priority' => 1024,
            ]);
    }
}
