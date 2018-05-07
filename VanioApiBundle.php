<?php
namespace Vanio\ApiBundle;

use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vanio\ApiBundle\DependencyInjection\PrioritizeAddRequestFormatsListenerPass;
use Vanio\ApiBundle\Security\ApiFormFactory;

class VanioApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PrioritizeAddRequestFormatsListenerPass);
        $this->getSecurityExtension($container)->addSecurityListenerFactory(new ApiFormFactory);
    }

    private function getSecurityExtension(ContainerBuilder $container): SecurityExtension
    {
        return $container->getExtension('security');
    }
}
