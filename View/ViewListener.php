<?php
namespace Vanio\ApiBundle\View;

use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\Context;
use JMS\Serializer\ContextFactory\SerializationContextFactoryInterface;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Vanio\ApiBundle\Serializer\PropertiesExclusionStrategy;
use Vanio\ApiBundle\Specification\Filter;
use Vanio\ApiBundle\Specification\Properties;

class ViewListener implements EventSubscriberInterface
{
    /** @var SerializerInterface */
    private $serializer;

    /** @var SerializationContextFactoryInterface */
    private $serializationContextFactory;

    /** @var PropertiesExclusionStrategy */
    private $propertiesExclusionStrategy;

    public function __construct(
        SerializerInterface $serializer,
        SerializationContextFactoryInterface $serializationContextFactory,
        PropertiesExclusionStrategy $propertiesExclusionStrategy
    ) {
        $this->serializer = $serializer;
        $this->serializationContextFactory = $serializationContextFactory;
        $this->propertiesExclusionStrategy = $propertiesExclusionStrategy;
    }

    public function onView(GetResponseForControllerResultEvent $event): void
    {
        $view = $event->getControllerResult();

        if (!$view instanceof View) {
            return;
        }

        $request = $event->getRequest();
        $format = $view->format() ?? $request->getRequestFormat();
        $data = $view->data();
        $content = $this->serializer->serialize($data, $format, $this->resolveSerializationContext($view, $request));
        $headers = $view->headers()->all();

        if (!$view->headers()->has('Content-Type')) {
            $headers['Content-Type'] = $request->getMimeType($format);
        }

        if ($data instanceof Paginator) {
            $headers['Total-Count'] = $data->count();
        }

        if ($view->statusCode() === null) {
            $statusCode = is_array($view->data()) && !empty($view->data()['errors'])
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_OK;
        } else {
            $statusCode = $view->statusCode();
        }

        $event->setResponse(new Response($content, $statusCode, $headers));
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::VIEW => 'onView'];
    }

    private function resolveSerializationContext(View $view, Request $request): Context
    {
        if (!$serializationContext = $view->serializationContext()) {
            $serializationContext = $this->serializationContextFactory->createSerializationContext();
        }

        $properties = $view->properties() ?? $this->guessProperties($request);

        if ($properties !== null) {
            $serializationContext->addExclusionStrategy($this->propertiesExclusionStrategy);
            $serializationContext->attributes->set('properties', $properties);
        }

        return $serializationContext;
    }

    /**
     * @return mixed[]|null
     */
    private function guessProperties(Request $request): ?array
    {
        $properties = $request->attributes->get('properties');
        $filter = $request->attributes->get('filter');

        if ($properties && $properties instanceof Properties) {
            return $properties->properties();
        } elseif ($filter && $filter instanceof Filter) {
            return $filter->properties()->properties();
        }

        return null;
    }
}
