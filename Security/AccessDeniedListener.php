<?php
namespace Vanio\ApiBundle\Security;

use JMS\Serializer\Exception\UnsupportedFormatException;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AccessDeniedListener implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AuthenticationTrustResolver */
    private $authenticationTrustResolver;

    /** @var SerializerInterface */
    private $serializer;

    /** @var \Exception|null */
    private $exception;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationTrustResolver $authenticationTrustResolver,
        SerializerInterface $serializer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
        $this->serializer = $serializer;
    }

    public function onKernelException(
        GetResponseForExceptionEvent $event,
        string $eventName,
        EventDispatcherInterface $eventDispatcher
    ): void {
        static $handling;

        if ($handling || $event->getRequest()->getRequestFormat() === 'html') {
            return;
        }

        $handling = true;
        $this->exception = $event->getException();

        if ($this->exception instanceof AuthenticationException || $this->exception instanceof AccessDeniedException) {
            $eventDispatcher->addListener(KernelEvents::RESPONSE, [$this, 'onKernelResponse']);
        }

        $handling = false;
    }

    public function onKernelResponse(
        FilterResponseEvent $event,
        string $eventName,
        EventDispatcherInterface $eventDispatcher
    ): void {
        if (!$this->exception || !$event->isMasterRequest()) {
            return;
        }

        $eventDispatcher->removeListener(KernelEvents::RESPONSE, [$this, 'onKernelResponse']);
        $exception = $this->exception;
        $this->exception = null;
        $request = $event->getRequest();
        $headers = $event->getResponse()->headers->all();
        $format = $request->getRequestFormat();
        unset($headers['location']);
        $headers['content-type'] = $request->getMimeType($format);

        try {
            if ($exception instanceof AuthenticationException) {
                $response = $this->createUnauthorizedResponse($format);
                $response->headers->add($headers);
                $event->setResponse($response);
            } elseif ($exception instanceof AccessDeniedException) {
                $response = $this->authenticationTrustResolver->isFullFledged($this->tokenStorage->getToken())
                    ? $this->createForbiddenResponse($format)
                    : $this->createUnauthorizedResponse($format);
                $response->headers->add($headers);
                $event->setResponse($response);
            }
        } catch (UnsupportedFormatException $e) {}
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 5]];
    }

    private function createUnauthorizedResponse(string $format): Response
    {
        $content = $this->serializeExceptionContent(
            Response::HTTP_UNAUTHORIZED,
            'You are not authenticated.',
            $format
        );

        return new Response($content, Response::HTTP_UNAUTHORIZED);
    }

    private function createForbiddenResponse(string $format): Response
    {
        $content = $this->serializeExceptionContent(
            Response::HTTP_FORBIDDEN,
            "You don't have the required permissions.",
            $format
        );

        return new Response($content, Response::HTTP_FORBIDDEN);
    }

    private function serializeExceptionContent(int $code, string $message, string $format): string
    {
        if ($format === 'xml') {
            return sprintf(
                '<?xml version="1.0" encoding="UTF-8"?>
                    <response>
                      <code>%d</code>
                      <message><![CDATA[%s]]></message>
                      <errors>
                        <error><![CDATA[%s]]></error>
                      </errors>
                    </response>
                ',
                $code,
                $message,
                $message
            );
        }

        return $this->serializer->serialize(
            [
                'code' => $code,
                'message' => $message,
                'errors' => [$message],
            ],
            $format
        );
    }
}
