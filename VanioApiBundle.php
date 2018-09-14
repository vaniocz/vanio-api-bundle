<?php
namespace Vanio\ApiBundle;

use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Vanio\ApiBundle\DependencyInjection\DecorateAuthenticationHandlersPass;
use Vanio\ApiBundle\DependencyInjection\DecorateJmsModelDescriberPass;
use Vanio\ApiBundle\DependencyInjection\PrioritizeAddRequestFormatsListenerPass;
use Vanio\ApiBundle\Security\ApiFormFactory;

class VanioApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new DecorateAuthenticationHandlersPass)
            ->addCompilerPass(new DecorateJmsModelDescriberPass)
            ->addCompilerPass(new PrioritizeAddRequestFormatsListenerPass);
        /** @var SecurityExtension $securityExtension */
        $securityExtension = $container->getExtension('security');
        $securityExtension->addSecurityListenerFactory(new ApiFormFactory);
    }
}
