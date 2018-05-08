<?php
namespace Vanio\ApiBundle\Serializer;

use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use Vanio\DomainBundle\Model\File;
use Vanio\DomainBundle\Model\Image;

class FileHandler implements SubscribingHandlerInterface
{
    public function serialize(VisitorInterface $visitor, File $file): string
    {
        return $file->fileName();
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribingMethods(): array
    {
        $subscribingMethods = [];

        foreach (['json', 'xml', 'yml'] as $format) {
            foreach ([File::class, Image::class] as $type) {
                $subscribingMethods[] = [
                    'type' => $type,
                    'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                    'format' => $format,
                    'method' => 'serialize',
                ];
            }
        }

        return $subscribingMethods;
    }
}
