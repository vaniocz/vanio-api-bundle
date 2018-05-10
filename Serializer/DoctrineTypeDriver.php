<?php
namespace Vanio\ApiBundle\Serializer;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use JMS\Serializer\Metadata\Driver\DoctrineTypeDriver as BaseDoctrineTypeDriver;
use JMS\Serializer\Metadata\PropertyMetadata;
use Vanio\DomainBundle\Model\File;
use Vanio\DomainBundle\Model\Image;
use Vanio\Stdlib\Strings;

class DoctrineTypeDriver extends BaseDoctrineTypeDriver
{
    private const DEFAULT_TYPE_MAPPING = [
        'uuid' => 'string',
        File::class => 'string',
        Image::class => 'string',
    ];

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
     * @param string $type
     * @return string|null
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     */
    protected function normalizeFieldType($type): ?string
    {
        if ($normalizedType = $this->fieldMapping[$type] ?? $this->typeMapping[$type] ?? null) {
            return $normalizedType;
        } elseif (Strings::startsWith($type, 'scalar_object<')) {
            $type = substr($type, 14, -1);

            return $this->fieldMapping[$type::scalarType()];
        }

        return null;
    }
}
