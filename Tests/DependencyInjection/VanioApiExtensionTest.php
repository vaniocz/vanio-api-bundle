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
            'format_listener' => false,
            'request_body_listener' => false,
            'access_denied_listener' => false,
            'formats' => ['json'],
            'limit_default_options' => [],
            'serializer_doctrine_type_mapping' => [],
            'api_doc_type_mapping' => [],
            'api_doc_request_with_credentials' => false,
            'cors' => [
                'enabled' => false,
                'allow_origins' => [],
                'allow_methods' => true,
                'allow_headers' => true,
                'expose_headers' => true,
                'allow_credentials' => true,
                'maximum_age' => null,
            ],
        ], $config);
    }
}
