<?php
namespace Vanio\ApiBundle\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
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

    /** @var string[] */
    private $formats;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param AuthenticationTrustResolver $authenticationTrustResolver
     * @param string[] $formats
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationTrustResolver $authenticationTrustResolver,
        array $formats
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationTrustResolver = $authenticationTrustResolver;
        $this->formats = array_combine($formats, $formats);
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        static $handling;

        if ($handling || !$this->isRequestInApiFormat($event->getRequest())) {
            return;
        }

        $handling = true;
        $exception = $event->getException();

        if ($exception instanceof AuthenticationException) {
            $exception = $this->createUnauthorizedException();
        } elseif ($exception instanceof AccessDeniedException) {
            $exception = $this->authenticationTrustResolver->isFullFledged($this->tokenStorage->getToken())
                ? $this->createForbiddenException()
                : $this->createUnauthorizedException();
        }

        $event->setException($exception);
        $handling = false;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 5]];
    }

    private function isRequestInApiFormat(Request $request): bool
    {
        return isset($this->formats[$request->getRequestFormat()]) || isset($this->formats[$request->getContentType()]);
    }

    private function createUnauthorizedException(): HttpException
    {
        return new HttpException(401, 'You are not authenticated.');
    }

    private function createForbiddenException(): AccessDeniedHttpException
    {
        return new AccessDeniedHttpException("You don't have the required permissions.");
    }
}
