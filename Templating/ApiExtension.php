<?php
namespace Vanio\ApiBundle\Templating;

use JMS\Serializer\SerializerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ApiExtension extends AbstractExtension
{
    /** @var SerializerInterface */
    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [new TwigFilter('serialize', [$this, 'serialize'])];
    }

    /**
     * @param mixed $data
     * @param string $format
     * @return string
     */
    public function serialize($data, string $format = 'json'): string
    {
        return $this->serializer->serialize($data, $format);
    }
}
