<?php
namespace Vanio\ApiBundle\Request;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RouteNotFoundListener implements EventSubscriberInterface
{
    /** @var array */
    private $formats;

    public function __construct(array $formats)
    {
        $this->formats = array_combine($formats, $formats);
    }

    public function onException(GetResponseForExceptionEvent $event): void
    {
        if (!$this->formats || !$event->getException() instanceof NotFoundHttpException) {
            return;
        }

        $request = $event->getRequest();

        foreach ($request->getAcceptableContentTypes() as $contentType) {
            if ($format = $request->getFormat($contentType)) {
                if (isset($this->formats[$format])) {
                    $request->setRequestFormat($format);

                    return;
                }
            }
        }

        $request->setRequestFormat(current($this->formats));
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }
}
