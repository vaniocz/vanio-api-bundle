<?php
namespace Vanio\ApiBundle\Serializer;

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class AccessorNormalizer extends AbstractObjectNormalizer
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param object $object
     * @param string|null $format
     * @param mixed[] $context
     * @return string[]
     */
    protected function extractAttributes($object, $format = null, array $context = []): array
    {
        $attributes = [];
        $reflectionClass = new \ReflectionClass($object);

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if (
                $reflectionMethod->getNumberOfRequiredParameters()
                || !$reflectionClass->hasProperty($reflectionMethod->name)
            ) {
                continue;
            }

            if ($this->isAllowedAttribute($object, $reflectionMethod->name, $format, $context)) {
                $attributes[$reflectionMethod->name] = true;
            }
        }

        return array_keys($attributes);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param object $object
     * @param string $attribute
     * @param string|null $format
     * @param mixed[] $context
     * @return mixed
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        return $object->$attribute();
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param object $object
     * @param string $attribute
     * @param mixed $value
     * @param string|null $format
     * @param mixed[] $context
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {}
}
