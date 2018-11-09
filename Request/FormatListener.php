<?php
namespace Vanio\ApiBundle\Request;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\ConfigurableRequirementsInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

class FormatListener implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var UrlGeneratorInterface|null */
    private $configurableUrlGenerator;

    /** @var string[] */
    private $defaultAcceptableFormats;

    /**
     * FormatListener constructor.
     * @param UrlGeneratorInterface $urlGenerator
     * @param UrlGeneratorInterface|null $defaultUrlGenerator
     * @param string[] $defaultAcceptableFormats
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ?UrlGeneratorInterface $defaultUrlGenerator = null,
        array $defaultAcceptableFormats = []
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->configurableUrlGenerator = $this->resolveConfigurableRequirementsUrlGenerator($urlGenerator)
            ?: $this->resolveConfigurableRequirementsUrlGenerator($defaultUrlGenerator);
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

    private function resolveConfigurableRequirementsUrlGenerator(UrlGeneratorInterface $urlGenerator): ?ConfigurableRequirementsInterface
    {
        if ($urlGenerator instanceof ConfigurableRequirementsInterface) {
            return $urlGenerator;
        } elseif ($urlGenerator instanceof Router) {
            return $this->resolveConfigurableRequirementsUrlGenerator($urlGenerator->getGenerator());
        }

        return null;
    }

    private function isFormatPresentInRoutePattern(Request $request): bool
    {
        if (!$request->attributes->has('_route')) {
            return false;
        }

        $routeParameters = $request->attributes->get('_route_params') ?? [];
        $parameters = ['_format' => '__FORMAT__'] + $routeParameters;

        if ($this->configurableUrlGenerator) {
            $isStrictRequirements = $this->configurableUrlGenerator->isStrictRequirements();
            $this->configurableUrlGenerator->setStrictRequirements(null);
        }

        $url = $this->urlGenerator->generate($request->attributes->get('_route'), $parameters);

        if (isset($isStrictRequirements)) {
            $this->configurableUrlGenerator->setStrictRequirements($isStrictRequirements);
        }

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
