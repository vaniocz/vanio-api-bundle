<?php
namespace Vanio\ApiBundle\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\ExpressionLanguage\Expression;

class ApiFormFactory extends FormLoginFactory
{
    public function __construct()
    {
        $this->addOption('username_parameter');
        $this->addOption('password_parameter', 'password');
        $this->addOption('post_only', true);
    }

    public function getListenerId(): string
    {
        return 'security.authentication.listener.api_form';
    }

    public function getKey(): string
    {
        return 'api-form-login';
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @param mixed[] $config
     * @param string $userProvider
     * @return string
     */
    protected function createListener($container, $id, $config, $userProvider): string
    {
        $listenerId = parent::createListener($container, $id, $config, $userProvider);

        if ($config['username_parameter'] !== null) {
            return $listenerId;
        }

        $definition = $container->getDefinition($listenerId);
        $options = $definition->getArgument(7);
        unset($options['username_parameter']);
        $parameter = "{$listenerId}.options";
        $container->setParameter($parameter, $options);
        $definition->replaceArgument(7, new Expression("
            container.getParameter('{$parameter}') + {
                username_parameter: container.hasParameter('vanio_user.email_only') && container.getParameter('vanio_user.email_only')
                    ? 'email'
                    : 'username'
                }
        "));

        return $listenerId;
    }
}
