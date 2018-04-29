<?php
namespace Vanio\ApiBundle\Serializer;

use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use Ramsey\Uuid\Uuid;

class UuidHandler implements SubscribingHandlerInterface
{
    public function serialize(VisitorInterface $visitor, Uuid $uuid): string
    {
        return (string) $uuid;
    }

    public function deserialize(VisitorInterface $visitor, string $value): Uuid
    {
        return Uuid::fromString($value);
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
                    'type' => Uuid::class,
                    'direction' => $direction,
                    'format' => $format,
                    'method' => $method,
                ];
            }
        }

        return $subscribingMethods;
    }
}
