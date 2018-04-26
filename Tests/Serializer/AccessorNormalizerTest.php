<?php
namespace Vanio\ApiBundle\Tests\DependencyInjection;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vanio\ApiBundle\Tests\Fixtures\Foo;

class AccessorNormalizerTest extends KernelTestCase
{
    function test_normalization()
    {
        static::bootKernel();
        $this->assertSame(
            [
                'bar' => 'bar',
                'baz' => 'baz',
            ],
            $this->normalizer()->normalize(new Foo)
        );
    }

    private function normalizer(): NormalizerInterface
    {
        return static::$kernel->getContainer()->get('serializer');
    }
}
