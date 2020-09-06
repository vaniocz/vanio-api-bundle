<?php
namespace Vanio\ApiBundle\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
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
    private $exposedProperties = [];

    /** @var ObjectManager|null */
    private $objectManager;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function shouldSkipClass(ClassMetadata $classMetadata, Context $context): bool
    {
        $this->classMetadatas[$classMetadata->name] = $classMetadata;

        return false;
    }

    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        if (!$entityClassMetadata = $this->getEntityClassMetadata($property->class)) {
            return false;
        }

        $exposedProperties = $this->getEntityExposedProperties($entityClassMetadata, $context);

        return !isset($exposedProperties[$property->name]);
    }

    /**
     * @return mixed[]
     */
    private function getEntityExposedProperties(ClassMetadataInfo $classMetadata, Context $context): array
    {
        $cacheKey = json_encode([$context->attributes->get('properties')->get(), $context->getCurrentPath()]);

        if (!isset($this->exposedProperties[$cacheKey])) {
            $this->exposedProperties[$cacheKey] = $this->resolveEntityExposedProperties($classMetadata, $context);
        }

        return $this->exposedProperties[$cacheKey];
    }

    /**
     * @return mixed[]
     */
    private function resolveEntityExposedProperties(ClassMetadataInfo $entityClassMetadata, Context $context): array
    {
        $properties = $context->attributes->get('properties')->get();

        try {
            $properties = Arrays::getReference($properties, $context->getCurrentPath()) ?? [];
        } catch (\InvalidArgumentException $e) {
            $properties = [];
        }

        $basicProperties = $this->resolveBasicProperties($entityClassMetadata, $context);
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

    private function getEntityClassMetadata(string $class): ?ClassMetadataInfo
    {
        if ($objectManager = $this->doctrine->getManagerForClass($class)) {
            $this->objectManager = $objectManager;
        } elseif (!$this->objectManager) {
            $this->objectManager = $this->doctrine->getManager();
        }

        return $this->objectManager->getMetadataFactory()->hasMetadataFor($class)
            ? $this->objectManager->getClassMetadata($class)
            : null;
    }

    /**
     * @return bool[]
     */
    private function resolveBasicProperties(ClassMetadataInfo $entityClassMetadata): array
    {
        $basicProperties = [];

        foreach ($entityClassMetadata->fieldMappings as $fieldMapping) {
            $basicProperties[$fieldMapping['declaredField'] ?? $fieldMapping['fieldName']] = true;
        }

        return $basicProperties;
    }
}
