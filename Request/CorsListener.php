<?php
namespace Vanio\ApiBundle\Request;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Vanio\Stdlib\Strings;
use Vanio\Stdlib\Uri;

class CorsListener implements EventSubscriberInterface
{
    private const SIMPLE_HEADERS = ['accept', 'accept-language', 'content-language', 'origin'];
    private const METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD', 'CONNECT', 'TRACE'];

    /** @var string[]|bool */
    private $allowedOrigins;

    /** @var string[]|bool */
    private $allowedMethods;

    /** @var string[]|bool */
    private $allowedHeaders;

    /** @var string[]|bool */
    private $exposedHeaders;

    /** @var bool */
    private $areCredentialsAllowed;

    /** @var int|null */
    private $maximumAge;

    /**
     * @param string[]|bool $allowedOrigins
     * @param string[]|bool $allowedMethods
     * @param string[]|bool $allowedHeaders
     * @param string[]|bool $exposedHeaders
     */
    public function __construct(
        $allowedOrigins = [],
        $allowedMethods = true,
        $allowedHeaders = true,
        $exposedHeaders = true,
        bool $areCredentialsAllowed = true,
        ?int $maximumAge = null
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $this->allowedMethods === true
            ? array_map('strtoupper', $allowedMethods)
            : self::METHODS;
        $this->allowedHeaders = $allowedHeaders;
        $this->exposedHeaders = $exposedHeaders;
        $this->areCredentialsAllowed = $areCredentialsAllowed;
        $this->maximumAge = $maximumAge;
    }

    /**
     * @internal
     */
    public function onRequest(
        GetResponseEvent $event,
        string $eventName,
        EventDispatcherInterface $eventDispatcher
    ): void {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $origin = $request->headers->get('Origin');

        if ($origin === null || $origin === $request->getSchemeAndHttpHost()) {
            return;
        } elseif ($request->isMethod('OPTIONS')) {
            $event->setResponse($this->createPreflightResponse($request));

            return;
        } elseif ($this->checkOrigin($request)) {
            $eventDispatcher->addListener(KernelEvents::RESPONSE, [$this, 'onResponse']);
        }
    }

    /**
     * @internal
     */
    public function onResponse(
        FilterResponseEvent $event,
        string $eventName,
        EventDispatcherInterface $eventDispatcher
    ): void {
        if (!$event->isMasterRequest()) {
            return;
        }

        $eventDispatcher->removeListener($eventName, [$this, 'onResponse']);
        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', $event->getRequest()->headers->get('Origin'));

        if ($this->exposedHeaders) {
            if ($exposedHeaders = $this->exposedHeaders === true ? $response->headers->keys() : $this->exposedHeaders) {
                $response->headers->set('Access-Control-Expose-Headers', implode(', ', $exposedHeaders));
            }
        }

        if ($this->areCredentialsAllowed) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 1024]];
    }

    private function createPreflightResponse(Request $request): Response
    {
        $response = new Response;
        $response->headers->set('Content-Type', 'text/plain');
        $headers = $request->headers->get('Access-Control-Request-Headers');

        if ($allowedMethods = $this->allowedMethods) {
            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        }

        if ($this->allowedHeaders) {
            if ($allowedHeaders = $this->allowedHeaders === true ? $headers : implode(', ', $this->allowedHeaders)) {
                $response->headers->set('Access-Control-Allow-Headers', $allowedHeaders);
            }
        }

        if ($this->areCredentialsAllowed) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        if ($this->maximumAge) {
            $response->headers->set('Access-Control-Max-Age', $this->maximumAge);
        }

        if (!$this->checkOrigin($request)) {
            $response->headers->set('Access-Control-Allow-Origin', 'null');

            return $response;
        }

        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
        $method = strtoupper($request->headers->get('Access-Control-Request-Method'));

        if (!in_array($method, $allowedMethods, true)) {
            return $response->setStatusCode(Response::HTTP_METHOD_NOT_ALLOWED);
        } elseif (!in_array($method, $allowedMethods, true)) {
            $allowedMethods[] = $method;
            $response->headers->set('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        }

        if ($this->allowedHeaders !== true && $headers) {
            foreach (preg_split('~, *~', trim(strtolower($headers))) as $header) {
                if (!in_array($header, self::SIMPLE_HEADERS, true)) {
                    continue;
                } elseif (!in_array($header, $this->allowedHeaders, true)) {
                    $response
                        ->setStatusCode(Response::HTTP_BAD_REQUEST)
                        ->setContent(sprintf('Unauthorized header "%s".', $header));
                }
            }
        }

        return $response;
    }

    private function checkOrigin(Request $request): bool
    {
        if ($this->allowedOrigins === true) {
            return true;
        }

        $origin = new Uri($request->headers->get('Origin'));

        foreach ((array) $this->allowedOrigins as $allowedOrigin) {
            $allowedOrigin = Strings::contains($allowedOrigin, '//')
                ? $allowedOrigin
                : sprintf('//%s', $allowedOrigin);
            $allowedOrigin = new Uri($allowedOrigin);

            if (
                $origin->host() === $allowedOrigin->host()
                && (!$allowedOrigin->scheme() || $allowedOrigin->scheme() === $origin->scheme())
            ) {
                return true;
            }
        }

        return false;
    }
}
