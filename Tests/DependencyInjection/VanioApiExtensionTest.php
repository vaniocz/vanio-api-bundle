<?php
namespace Vanio\ApiBundle\Tests\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VanioApiExtensionTest extends KernelTestCase
{
    function test_default_configuration()
    {
        static::bootKernel();
        $config = static::$kernel->getContainer()->getParameter('vanio_api');
        $this->assertEquals([
            'route_not_found_listener' => true,
            'access_denied_listener' => true,
            'formats' => ['json'],
            'limit_default_options' => [],
        ], $config);
    }
}
