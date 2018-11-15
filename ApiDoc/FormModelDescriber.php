<?php
namespace Vanio\ApiBundle\ApiDoc;

use EXSyst\Component\Swagger\Schema;
use Metadata\MetadataFactoryInterface;
use Metadata\PropertyMetadata;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Vanio\DoctrineGenericTypes\Bundle\Form\ScalarObjectType;

class FormModelDescriber implements ModelDescriberInterface, ModelRegistryAwareInterface
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

    /** @var FormFactoryInterface */
    private $formFactory;

    public function __construct(
        ModelDescriberInterface $modelDescriber,
        FormFactoryInterface $formFactory
    ) {
        $this->modelDescriber = $modelDescriber;
        $this->formFactory = $formFactory;
        $this->defaultTypeMapping = [
            ScalarObjectType::class => [$this, 'resolveScalarObjectType'],
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
        $this->modelDescriber->describe($model, $schema);
        $form = $this->formFactory->create($model->getType()->getClassName());
        $this->describeForm($schema, $form);
    }

    private function describeForm(Schema $schema, FormInterface $form): void
    {
        $properties = $schema->getProperties();

        foreach ($form->all() as $name => $child) {
            $documentation = $child->getConfig()->getOption('documentation');

            if (!empty($documentation['type'])) {
                continue;
            } elseif ($resolvedType = $this->resolveType($child)) {
                $properties->get($name)->merge($resolvedType, true);
            }
        }
    }

    /**
     * @return mixed[]|null
     */
    private function resolveType(FormInterface $form): ?array
    {
        $formType = $form->getConfig()->getType();

        do {
            $class = get_class($formType->getInnerType());

            if ($type = $this->typeMapping[$class] ?? null) {
                return is_string($type) ? ['type' => $type] : $type($form);
            }
        } while ($formType = $formType->getParent());

        return null;
    }

    public function supports(Model $model): bool
    {
        return $this->modelDescriber->supports($model);
    }

    public function resolveScalarObjectType(FormInterface $form): array
    {
        $class = $form->getConfig()->getDataClass();

        return ['type' => $class::{'scalarType'}()];
    }
}
