<?php
namespace Vanio\ApiBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vanio\ApiBundle\DependencyInjection\PrioritizeAddRequestFormatsListenerPass;

class VanioApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PrioritizeAddRequestFormatsListenerPass);
    }
}
