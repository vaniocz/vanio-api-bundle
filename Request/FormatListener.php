<?php
namespace Vanio\ApiBundle\Request;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class FormatListener implements EventSubscriberInterface
{
    /** @var string[] */
    private $formats;

    /**
     * @param string[] $formats
     */
    public function __construct(array $formats)
    {
        $this->formats = array_combine($formats, $formats);
    }

    public function onRequest(GetResponseEvent $event): void
    {
        if ($this->formats) {
            $event->getRequest()->attributes->set('_format', $this->resolveRequestFormat($event->getRequest()));
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['onRequest', 1024]];
    }

    private function resolveRequestFormat(Request $request): string
    {
        foreach ($request->getAcceptableContentTypes() as $contentType) {
            if ($format = $request->getFormat($contentType)) {
                if (isset($this->formats[$format])) {
                    return $format;
                }
            }
        }

        return current($this->formats);
    }
}
