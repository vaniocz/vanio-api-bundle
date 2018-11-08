<?php
namespace Vanio\ApiBundle\Request;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FormatListener implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var string[] */
    private $defaultAcceptableFormats;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param string[] $defaultAcceptableFormats
     */
    public function __construct(UrlGeneratorInterface $urlGenerator, array $defaultAcceptableFormats)
    {
        $this->urlGenerator = $urlGenerator;
        $this->defaultAcceptableFormats = $defaultAcceptableFormats;
    }

    public function onRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->attributes->has('_format') && $this->isFormatPresentInRoutePattern($request)) {
            return;
        }

        $acceptableRequestFormat = $this->resolveAcceptableRequestFormat($request);

        if ($acceptableRequestFormat !== null) {
            $request->setRequestFormat($acceptableRequestFormat);
        }
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onRequest'];
    }

    private function isFormatPresentInRoutePattern(Request $request): bool
    {
        if (!$request->attributes->has('_route')) {
            return false;
        }

        $routeParameters = $request->attributes->get('_route_params') ?? [];
        $parameters = ['_format' => '__FORMAT__'] + $routeParameters;
        $url = $this->urlGenerator->generate($request->attributes->get('_route'), $parameters);
        parse_str(parse_url($url, PHP_URL_QUERY), $additionalAttributes);

        return !isset($additionalAttributes['_format']);
    }

    private function resolveAcceptableRequestFormat(Request $request): ?string
    {
        $acceptableFormats = $request->attributes->get('_acceptable_formats', $this->defaultAcceptableFormats);
        $acceptableFormats = array_flip((array) $acceptableFormats);

        foreach ($request->getAcceptableContentTypes() as $contentType) {
            if ($format = $request->getFormat($contentType)) {
                if (isset($acceptableFormats[$format])) {
                    return $format;
                }
            }
        }

        return null;
    }
}
