<?php
namespace Vanio\ApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class VanioApiExtension extends Extension
{
    /**
     * @param mixed[] $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration, $configs);
        $loader = new XmlFileLoader($container, new FileLocator(sprintf('%s/../Resources/config', __DIR__)));
        $loader->load('config.xml');
        $this->setContainerRecursiveParameter($container, 'vanio_api', $config);
        $subscribers = [
            'format_listener' => 'vanio_api.request.format_listener',
            'request_body_listener' => 'vanio_api.request.request_body_listener',
            'access_denied_listener' => 'vanio_api.security.access_denied_listener',
        ];

        foreach ($subscribers as $name => $id) {
            if ($config[$name]) {
                $container
                    ->getDefinition($id)
                    ->setAbstract(false)
                    ->addTag('kernel.event_subscriber');
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string $name
     * @param mixed $value
     */
    private function setContainerRecursiveParameter(ContainerBuilder $container, string $name, $value): void
    {
        $container->setParameter($name, $value);

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $this->setContainerRecursiveParameter($container, "$name.$k", $v);
            }
        }
    }
}
