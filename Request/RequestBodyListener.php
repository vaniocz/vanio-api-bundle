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

    /** @var string|null */
    private $defaultContentFormat;

    public function __construct(SerializerInterface $serializer, ?string $defaultContentFormat)
    {
        $this->serializer = $serializer;
        $this->defaultContentFormat = $defaultContentFormat;
    }

    public function onRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();
        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (!in_array($request->getMethod(), $methods, true) || $this->isFormRequest($request)) {
            return;
        }

        $content = trim($request->getContent());

        if ($content === '') {
            return;
        }

        $contentType = $this->resolveRequestContentType($request);
        $defaultContentFormat = $this->defaultContentFormat ?: $request->getRequestFormat();
        $format = $contentType === null
            ? $request->attributes->get('_default_content_format', $defaultContentFormat)
            : $request->getFormat($contentType);

        try {
            $data = $this->serializer->deserialize($content, 'array', $format);
        } catch (UnsupportedFormatException $e) {
            throw new UnsupportedMediaTypeHttpException(sprintf(
                'Request body content format "%s" is not supported.',
                $format
            ));
        } catch (RuntimeException $e) {
            throw new BadRequestHttpException(sprintf('Malformed "%s" request body content.', $format));
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
        $formContentTypes = ['multipart/form-data', 'application/x-www-form-urlencoded'];

        return in_array(strtolower($this->resolveRequestContentType($request)), $formContentTypes, true);
    }

    private function resolveRequestContentType(Request $request): ?string
    {
        $contentType = explode(';', $request->headers->get('Content-Type'));

        return $contentType[0] ?: null;
    }
}
