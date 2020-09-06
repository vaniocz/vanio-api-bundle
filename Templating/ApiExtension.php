<?php
namespace Vanio\ApiBundle\Templating;

use JMS\Serializer\SerializerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;

class ApiExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var bool */
    private $shouldApiDocRequestWithCredentials;

    public function __construct(SerializerInterface $serializer, bool $shouldApiDocRequestWithCredentials = false)
    {
        $this->serializer = $serializer;
        $this->shouldApiDocRequestWithCredentials = $shouldApiDocRequestWithCredentials;
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [new TwigFilter('serialize', [$this, 'serialize'])];
    }

    /**
     * @return mixed[]
     */
    public function getGlobals(): array
    {
        return ['api_doc_request_with_credentials' => $this->shouldApiDocRequestWithCredentials];
    }

    /**
     * @param mixed $data
     */
    public function serialize($data, string $format = 'json'): string
    {
        return $this->serializer->serialize($data, $format);
    }
}
