<?php
namespace Vanio\ApiBundle\Tests\Fixtures;

class Foo
{
    /** @var string */
    private $bar = 'bar';

    /** @var string */
    private $baz = 'baz';

    /** @var string */
    private $quux = 'quux';

    public function bar(): string
    {
        return $this->bar;
    }

    public function baz(): string
    {
        return $this->baz;
    }

    public function qux(): string
    {
        return 'qux';
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
     */
    private function quux(): string
    {
        return $this->quux;
    }
}
