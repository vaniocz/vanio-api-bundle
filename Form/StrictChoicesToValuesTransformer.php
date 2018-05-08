<?php
namespace Vanio\ApiBundle\Form;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StrictChoicesToValuesTransformer implements DataTransformerInterface
{
    /** @var DataTransformerInterface */
    private $dataTransformer;

    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $class;

    /** @var bool */
    private $isMultiple;

    public function __construct(
        DataTransformerInterface $dataTransformer,
        EntityManager $entityManager,
        string $class,
        bool $isMultiple = false
    ) {
        $this->dataTransformer = $dataTransformer;
        $this->entityManager = $entityManager;
        $this->class = $class;
        $this->isMultiple = $isMultiple;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function transform($value)
    {
        return $this->dataTransformer->transform($value);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function reverseTransform($value)
    {
        if ($value !== null) {
            $this->validate($value);
        }

        return $this->dataTransformer->reverseTransform($value);
    }

    /**
     * @param mixed $data
     */
    private function validate($data): void
    {
        $classMetadata = $this->entityManager->getClassMetadata($this->class);
        $identifier = $classMetadata->getIdentifier();
        $databasePlatform = $this->entityManager->getConnection()->getDatabasePlatform();

        foreach ($this->isMultiple ? $data : [$data] as $value) {
            if (count($identifier) === 1) {
                $value = [$identifier[0] => $value];
            }

            foreach ($classMetadata->getIdentifier() as $property) {
                $type = Type::getType($classMetadata->getTypeOfField($property));

                try {
                    $type->convertToPHPValue($value[$property], $databasePlatform);
                } catch (ConversionException $e) {
                    throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e->getPrevious());
                }
            }
        }
    }
}
