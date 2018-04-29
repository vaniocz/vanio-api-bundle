<?php
namespace Vanio\ApiBundle\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;
use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use Vanio\Stdlib\Arrays;

class PropertiesExclusionStrategy implements ExclusionStrategyInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var ClassMetadata[] */
    private $classMetadatas;

    /** @var mixed[] */
    private $propertySerializedNames;

    /** @var mixed[] */
    private $exposedProperties = [];

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function shouldSkipClass(ClassMetadata $classMetadata, Context $context): bool
    {
        $this->classMetadatas[$classMetadata->name] = $classMetadata;

        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context)
    {
        $exposedProperties = $this->getExposedProperties($property->class, $context);

        return !isset($exposedProperties[$property->name]);
    }
    
    private function getExposedProperties(string $class, Context $context): array
    {
        $cacheKey = json_encode([$context->attributes->get('properties')->get(), $context->getCurrentPath()]);

        if (!isset($this->exposedProperties[$cacheKey])) {
            $this->exposedProperties[$cacheKey] = $this->resolveExposedProperties($class, $context);
        }

        return $this->exposedProperties[$cacheKey];
    }

    private function resolveExposedProperties(string $class, Context $context): array
    {
        $properties = $context->attributes->get('properties')->get();
        $properties = Arrays::getReference($properties, $context->getCurrentPath()) ?? [];
        $classMetadata = $this->doctrine->getManagerForClass($class)->getClassMetadata($class);
        $basicProperties = array_flip($classMetadata->getFieldNames());
        $additionalProperties = [];
        $excludedProperties = [];

        if (is_array($properties)) {
            foreach ($properties as $property => $path) {
                if (is_array($path)) {
                    $additionalProperties[$property] = true;
                } elseif ($path[0] === '-') {
                    $excludedProperties[substr($path, 1)] = true;
                } elseif (!isset($basicProperties[$path])) {
                    $additionalProperties[$path] = true;
                } else {
                    if (!isset($exposedProperties)) {
                        $exposedProperties = [];
                    }

                    $exposedProperties[$path] = true;
                }
            }
        }

        if (!isset($exposedProperties)) {
            $exposedProperties = $basicProperties;
        }

        return array_diff_key($exposedProperties + $additionalProperties, $excludedProperties);
    }
}
