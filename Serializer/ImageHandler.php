<?php
namespace Vanio\ApiBundle\Serializer;

use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use Vanio\DomainBundle\Model\Image;

class ImageHandler implements SubscribingHandlerInterface
{
    /**
     * @param VisitorInterface $visitor
     * @param Image $image
     * @return string
     */
    public function serialize(VisitorInterface $visitor, Image $image): string
    {
        return $image->fileName();
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribingMethods(): array
    {
        $subscribingMethods = [];

        foreach (['json', 'xml', 'yml'] as $format) {
            $subscribingMethods[] = [
                'type' => Image::class,
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format' => $format,
                'method' => 'serialize',
            ];
        }

        return $subscribingMethods;
    }
}
