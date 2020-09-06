<?php
namespace Vanio\ApiBundle\Specification;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\DomainBundle\Doctrine\QueryBuilderUtility;

class Properties implements QueryModifier
{
    /** @var mixed[] */
    private $properties = [];

    /**
     * @param mixed[] $properties
     */
    public function __construct(array $properties, ?string $dqlAlias = null)
    {
        $this->properties = $properties;
        $this->dqlAlias = $dqlAlias;
    }

    public static function fromString(string $properties, ?string $dqlAlias = null): self
    {
        $tokens = preg_split(
            '~\.?({.*?})\.?|\s*(,)\s*~',
            trim($properties),
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
        $properties = [];
        $propertyPath = [];

        foreach ($tokens as $token) {
            if ($token === ',') {
                self::buildProperties($properties, $propertyPath);
                $propertyPath = [];
            } elseif ($token[0] === '{') {
                $propertyPath[] = preg_split('~,\s*~', substr($token, 1, -1));
            } else {
                $propertyPath = array_merge($propertyPath, explode('.', $token));
            }
        }

        self::buildProperties($properties, $propertyPath);

        return new self($properties, $dqlAlias);
    }

    /**
     * @return mixed[]
     */
    public function properties(): array
    {
        return $this->properties;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @param string $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias): void
    {
        $class = QueryBuilderUtility::resolveDqlAliasClasses($queryBuilder)[$dqlAlias];
        $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata($class);
        $this->joinAssociations($queryBuilder, $classMetadata, $this->dqlAlias ?? $dqlAlias, $this->properties);
    }

    /**
     * @param mixed[] $properties
     */
    private function joinAssociations(
        QueryBuilder $queryBuilder,
        ClassMetadata $classMetadata,
        string $dqlAlias,
        array $properties
    ): void {
        foreach ($properties as $property => $propertyPath) {
            if (!is_array($propertyPath)) {
                $property = $propertyPath;
                $propertyPath = null;
            }

            if (!$classMetadata->hasAssociation($property)) {
                continue;
            }

            $relation = sprintf('%s.%s', $dqlAlias, $property);
            $joinDqlAlias = sprintf('%s_%s', $dqlAlias, $property);
            $queryBuilder
                ->leftJoin($relation, $joinDqlAlias)
                ->addSelect($joinDqlAlias);

            if ($propertyPath) {
                $class = $classMetadata->getAssociationTargetClass($property);
                $classMetadata = $queryBuilder->getEntityManager()->getClassMetadata($class);
                $this->joinAssociations($queryBuilder, $classMetadata, $joinDqlAlias, $propertyPath);
            }
        }
    }

    /**
     * @param mixed[]|null $properties
     * @param mixed[] $propertyPath
     */
    private static function buildProperties(?array &$properties, array $propertyPath): void
    {
        if (!$segment = array_shift($propertyPath)) {
            return;
        } elseif (!$properties) {
            $properties = [];
        }

        if (is_array($segment)) {
            foreach ($segment as $property) {
                $properties[ltrim($property, '-')] = $property;
            }

            self::buildProperties($properties, $propertyPath);
        } elseif ($propertyPath) {
            self::buildProperties($properties[$segment], $propertyPath);
        } else {
            $properties[ltrim($segment, '-')] = $segment;
        }
    }
}
