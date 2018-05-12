<?php
namespace Vanio\ApiBundle\Serializer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;

class PaginatorHandler implements SubscribingHandlerInterface
{
    /**
     * @param VisitorInterface $visitor
     * @param Paginator $paginator
     * @param mixed[] $type
     * @param Context $context
     * @return object[]
     */
    public function serialize(VisitorInterface $visitor, Paginator $paginator, array $type, Context $context): array
    {
        if ($entities = $paginator->getIterator()->getArrayCopy()) {
            $type = ['name' => ClassUtils::getClass(current($entities))];
        }

        return $visitor->visitArray($entities, $type, $context);
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribingMethods(): array
    {
        $subscribingMethods = [];

        foreach (['json', 'xml', 'yml'] as $format) {
            $subscribingMethods[] = [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'type' => Paginator::class,
                'format' => $format,
                'method' => 'serialize',
            ];
        }

        return $subscribingMethods;
    }
}
