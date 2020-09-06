<?php
namespace Vanio\ApiBundle\ApiDoc;

use EXSyst\Component\Swagger\Schema;
use JMS\Serializer\Exclusion\GroupsExclusionStrategy;
use JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use Metadata\MetadataFactoryInterface;
use Metadata\PropertyMetadata;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Ramsey\Uuid\Uuid;
use Vanio\DoctrineGenericTypes\DBAL\ScalarObject;
use Vanio\DomainBundle\Model\File;

class ModelDescriber implements ModelDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;

    /** @var mixed[] */
    private $defaultTypeMapping;

    /** @var mixed[] */
    private $typeMapping;

    /** @var ModelDescriberInterface */
    private $modelDescriber;

    /** @var MetadataFactoryInterface */
    private $metadataFactory;

    /** @var PropertyNamingStrategyInterface */
    private $propertyNamingStrategy;

    public function __construct(
        ModelDescriberInterface $modelDescriber,
        MetadataFactoryInterface $metadataFactory,
        PropertyNamingStrategyInterface $propertyNamingStrategy
    ) {
        $this->modelDescriber = $modelDescriber;
        $this->metadataFactory = $metadataFactory;
        $this->propertyNamingStrategy = $propertyNamingStrategy;
        $this->defaultTypeMapping = [
            ScalarObject::class => [$this, 'resolveScalarObjectType'],
            Uuid::class => [$this, 'resolveUuidType'],
            File::class => 'string',
        ];
        $this->typeMapping = $this->defaultTypeMapping;
    }

    public function setModelRegistry(ModelRegistry $modelRegistry): void
    {
        $this->modelRegistry = $modelRegistry;

        if ($this->modelDescriber instanceof ModelRegistryAwareInterface) {
            $this->modelDescriber->setModelRegistry($modelRegistry);
        }
    }

    /**
     * @param string[] $typeMapping
     */
    public function setTypeMapping(array $typeMapping): void
    {
        $this->typeMapping = $typeMapping + $this->defaultTypeMapping;
    }

    public function describe(Model $model, Schema $schema): void
    {
        $class = $model->getType()->getClassName();

        if (!$metadata = $this->metadataFactory->getMetadataForClass($model->getType()->getClassName())) {
            throw new \InvalidArgumentException(sprintf('Unable to get metadata for class "%s".', $class));
        }

        if ($model->getGroups() !== null) {
            $groupExclusionStrategy = new GroupsExclusionStrategy($model->getGroups());
        }

        $properties = $schema->getProperties();

        foreach ($metadata->propertyMetadata as $propertyMetadata) {
            assert($propertyMetadata instanceof PropertyMetadata);

            if (
                isset($groupExclusionStrategy)
                && $groupExclusionStrategy->shouldSkipProperty($propertyMetadata, SerializationContext::create())
            ) {
                continue;
            }

            $type = $propertyMetadata->type['name'];

            foreach ($this->typeMapping as $oldType => $newType) {
                if ($oldType === $type || is_a($type, $oldType, true)) {
                    $property = $properties->get($this->propertyNamingStrategy->translateName($propertyMetadata));

                    if (!is_string($newType)) {
                        $newType = $newType($propertyMetadata);
                    }

                    if (is_array($newType)) {
                        $property->merge($newType + $property->toArray(), true);
                    } else {
                        $property->setType($newType);
                    }
                }
            }
        }

        $this->modelDescriber->describe($model, $schema);
    }

    public function supports(Model $model): bool
    {
        return $this->modelDescriber->supports($model);
    }

    public function resolveScalarObjectType(PropertyMetadata $propertyMetadata): string
    {
        return $propertyMetadata->type['name']::scalarType();
    }

    /**
     * @return string[]
     */
    public function resolveUuidType(PropertyMetadata $propertyMetadata): array
    {
        return [
            'type' => 'uuid',
            'example' => '7e57d004-2b97-0e7a-b45f-5387367791cd',
        ];
    }
}
