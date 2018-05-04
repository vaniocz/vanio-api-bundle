<?php
namespace Vanio\ApiBundle\Request;

use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestBodyListener implements EventSubscriberInterface
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var string[] */
    private $formats;

    /**
     * @param SerializerInterface $serializer
     * @param string[] $formats
     */
    public function __construct(SerializerInterface $serializer, array $formats)
    {
        $this->serializer = $serializer;
        $this->formats = array_combine($formats, $formats);
    }

    public function onRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (!$this->formats || !in_array($request->getMethod(), $methods) || $this->isFormRequest($request)) {
            return;
        }

        $content = trim($request->getContent());

        if ($content === '') {
            return;
        }

        $contentType = $this->resolveRequestContentType($request);
        $format = $contentType === null
            ? $request->getRequestFormat()
            : $request->getFormat($contentType) ?? current($this->formats);

        try {
            $data = $this->serializer->deserialize($content, 'array', $format);
        } catch (UnsupportedFormatException $e) {
            throw new UnsupportedMediaTypeHttpException(sprintf('Request body format "%s" is not supported.', $format));
        } catch (RuntimeException $e) {
            throw new BadRequestHttpException(sprintf('Malformed "%s" request body.', $format));
        }

        if (is_array($data)) {
            $request->request->add($data);
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 10]];
    }

    private function isFormRequest(Request $request): bool
    {
        return in_array(strtolower($this->resolveRequestContentType($request)), [
            'multipart/form-data',
            'application/x-www-form-urlencoded',
        ]);
    }

    private function resolveRequestContentType(Request $request): ?string
    {
        $contentType = explode(';', $request->headers->get('Content-Type'));

        return $contentType[0] ?: null;
    }
}
