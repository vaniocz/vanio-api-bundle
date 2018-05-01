<?php
namespace Vanio\ApiBundle\View;

use JMS\Serializer\SerializationContext;
use Symfony\Component\HttpFoundation\HeaderBag;

class View
{
    /** @var mixed */
    private $data;

    /** @var int */
    private $statusCode;

    /** @var HeaderBag */
    private $headers;

    /** @var string|null */
    private $format;

    /** @var array|null */
    private $properties;

    /** @var SerializationContext|null */
    private $serializationContext;

    /**
     * @param mixed $data
     * @param int $statusCode
     * @param mixed[] $headers
     */
    public function __construct($data = null, int $statusCode = 200, array $headers = [])
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = new HeaderBag($headers);
    }

    /**
     * @return mixed
     */
    public function data()
    {
        return $this->data;
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function headers(): HeaderBag
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    public function format(): ?string
    {
        return $this->format;
    }

    public function setFormat(?string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function properties(): ?array
    {
        return $this->properties;
    }

    public function setProperties(?array $properties): self
    {
        $this->properties = $properties;
    }

    public function serializationContext(): ?SerializationContext
    {
        return $this->serializationContext;
    }

    public function setSerializationContext(?SerializationContext $serializationContext): self
    {
        $this->serializationContext = $serializationContext;

        return $this;
    }
}
