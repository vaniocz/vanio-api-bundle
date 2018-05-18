<?php
namespace Vanio\ApiBundle\Specification;

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\Query as DoctrineQuery;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Happyr\DoctrineSpecification\Query\QueryModifier;
use Vanio\ApiBundle\Doctrine\InvalidQueryException;
use Vanio\ApiBundle\Doctrine\SafeConditionWalker;

class Query implements QueryModifier
{
    /** @var string */
    private $dql;

    public function __construct(string $dql)
    {
        $this->dql = trim($dql);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param QueryBuilder $queryBuilder
     * @param string|null $dqlAlias
     */
    public function modify(QueryBuilder $queryBuilder, $dqlAlias): void
    {
        if ($this->dql === '') {
            return;
        }

        $entityManager = $queryBuilder->getEntityManager();
        $conditionQueryBuilder = clone $queryBuilder;
        $dql = $conditionQueryBuilder->resetDQLParts(['groupBy', 'having', 'orderBy'])->where('')->getDQL();

        try {
            $sql = $entityManager
                ->createQuery($conditionQueryBuilder->where($this->dql)->getDQL())
                ->setParameters($conditionQueryBuilder->getParameters())
                ->setHint(DoctrineQuery::HINT_CUSTOM_TREE_WALKERS, [SafeConditionWalker::class])
                ->getSQL();
        } catch (QueryException $e) {
            throw InvalidQueryException::invalidDqlCondition($e, strlen($dql));
        }

        if ($entityManager->getConnection()->getDatabasePlatform()->getName() === 'postgresql') {
            try {
                $entityManager->getConnection()->executeQuery(sprintf('EXPLAIN %s', $sql));
            } catch (DriverException $e) {
                throw InvalidQueryException::invalidSql($e);
            }
        }

        $queryBuilder->andWhere($this->dql);
    }
}
