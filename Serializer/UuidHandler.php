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
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidHandler implements SubscribingHandlerInterface, EventSubscriberInterface
{
    public function serialize(VisitorInterface $visitor, Uuid $uuid): string
    {
        return (string) $uuid;
    }

    public function deserialize(VisitorInterface $visitor, string $value): UuidInterface
    {
        return Uuid::fromString($value);
    }

    public function onPreSerialize(PreSerializeEvent $event): void
    {
        $this->assignUuidTypeName($event);
    }

    public function onPreDeserialize(PreDeserializeEvent $event): void
    {
        $this->assignUuidTypeName($event);
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
                    'type' => UuidInterface::class,
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
    private function assignUuidTypeName(Event $event): void
    {
        if ($event->getObject() instanceof UuidInterface) {
            $event->setType(UuidInterface::class, [get_class($event->getObject())]);
        }
    }
}
