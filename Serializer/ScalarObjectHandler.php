<?php
namespace Vanio\ApiBundle\Serializer;

use JMS\Serializer\EventDispatcher\Event;
use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreDeserializeEvent;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use Vanio\DoctrineGenericTypes\DBAL\ScalarObject;

class ScalarObjectHandler implements SubscribingHandlerInterface, EventSubscriberInterface
{
    /**
     * @return string|int|float|bool
     */
    public function serialize(VisitorInterface $visitor, ScalarObject $scalarObject)
    {
        return $scalarObject->scalarValue();
    }

    /**
     * @param string|int|float|bool $value
     * @param mixed[] $type
     */
    public function deserialize(VisitorInterface $visitor, $value, array $type): ScalarObject
    {
        $class = $type['name'];

        return is_callable([$class, 'create']) ? $class::create($value) : new $class($value);
    }

    public function onPreSerialize(PreSerializeEvent $event): void
    {
        $this->assignScalarObjectTypeName($event);
    }

    public function onPreDeserialize(PreDeserializeEvent $event): void
    {
        $this->assignScalarObjectTypeName($event);
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribingMethods(): array
    {
        $subscribingMethods = [];

        foreach (['json', 'xml', 'yml'] as $format) {
            $methods = [
                GraphNavigator::DIRECTION_SERIALIZATION => 'serialize',
                GraphNavigator::DIRECTION_DESERIALIZATION => 'deserialize',
            ];

            foreach ($methods as $direction => $method) {
                $subscribingMethods[] = [
                    'direction' => $direction,
                    'type' => ScalarObject::class,
                    'format' => $format,
                    'method' => $method,
                ];
            }
        }

        return $subscribingMethods;
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ['event' => Events::PRE_SERIALIZE, 'method' => 'onPreSerialize'],
            ['event' => Events::PRE_DESERIALIZE, 'method' => 'onPreDeserialize'],
        ];
    }

    /**
     * @param PreSerializeEvent|PreDeserializeEvent $event
     */
    private function assignScalarObjectTypeName(Event $event): void
    {
        if ($event->getObject() instanceof ScalarObject) {
            $event->setType(ScalarObject::class, [get_class($event->getObject())]);
        }
    }
}
