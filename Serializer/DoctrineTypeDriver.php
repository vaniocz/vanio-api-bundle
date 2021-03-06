<?php
namespace Vanio\ApiBundle\Serializer;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use JMS\Serializer\Metadata\Driver\DoctrineTypeDriver as BaseDoctrineTypeDriver;
use JMS\Serializer\Metadata\PropertyMetadata;
use Ramsey\Uuid\Uuid;

class DoctrineTypeDriver extends BaseDoctrineTypeDriver
{
    private const DEFAULT_TYPE_MAPPING = ['uuid' => Uuid::class];

    /** @var string[] */
    private $typeMapping = self::DEFAULT_TYPE_MAPPING;

    /**
     * @param string[] $typeMapping
     */
    public function setTypeMapping(array $typeMapping): void
    {
        $this->typeMapping = $typeMapping + self::DEFAULT_TYPE_MAPPING;
    }

    protected function setPropertyType(ClassMetadata $classMetadata, PropertyMetadata $propertyMetadata): void
    {
        parent::setPropertyType($classMetadata, $propertyMetadata);

        if ($propertyMetadata->type === null && $classMetadata instanceof ClassMetadataInfo) {
            if ($class = $classMetadata->embeddedClasses[$propertyMetadata->name]['class'] ?? null) {
                $propertyMetadata->setType($this->typeMapping[$class] ?? $class);
            }
        }
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param string $type
     */
    protected function normalizeFieldType($type): ?string
    {
        if ($normalizedType = $this->fieldMapping[$type] ?? $this->typeMapping[$type] ?? null) {
            return $normalizedType;
        }

        [$type, $typeParametersLiteral] = explode('<', $type, 2) + [1 => null];
        $typeParameters = $typeParametersLiteral
            ? preg_split('~,\h*~', trim(substr($typeParametersLiteral, 0, -1)))
            : [];

        if ($type = array_shift($typeParameters)) {
            return $typeParameters ? sprintf('%s<%s>', implode(', ', $typeParameters)) : $type;
        }

        return null;
    }
}
