<?php
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @return Bundle[]
     */
    public function registerBundles(): array
    {
        return [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle,
            new Symfony\Bundle\SecurityBundle\SecurityBundle,
            new JMS\SerializerBundle\JMSSerializerBundle,
            new Vanio\ApiBundle\VanioApiBundle,
        ];
    }

    public function getRootDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sprintf('%s/../../var/cache/%s', __DIR__, $this->getEnvironment());
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/../../var/logs';
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {}

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->loadFromExtension('framework', ['secret' => 'secret']);
        $container->loadFromExtension('security', [
            'firewalls' => [
                'test' => ['security' => false],
            ],
            'providers' => [
                'in_memory' => ['memory' => []],
            ],
        ]);
        $container->register('doctrine', 'stdClass');
    }
}
