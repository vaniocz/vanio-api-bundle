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
            'format_listener' => true,
            'request_body_listener' => true,
            'access_denied_listener' => true,
            'formats' => ['json'],
            'limit_default_options' => [],
        ], $config);
    }
}
