<?php
namespace Vanio\ApiBundle\Doctrine;

use Doctrine\ORM\Query\AST\AggregateExpression;
use Doctrine\ORM\Query\AST\ArithmeticExpression;
use Doctrine\ORM\Query\AST\ArithmeticFactor;
use Doctrine\ORM\Query\AST\ArithmeticTerm;
use Doctrine\ORM\Query\AST\BetweenExpression;
use Doctrine\ORM\Query\AST\CollectionMemberExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalFactor;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;
use Doctrine\ORM\Query\AST\DeleteClause;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\InExpression;
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\AST\LikeExpression;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\NullComparisonExpression;
use Doctrine\ORM\Query\AST\ParenthesisExpression;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleArithmeticExpression;
use Doctrine\ORM\Query\AST\UpdateStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\TreeWalkerAdapter;

class SafeConditionWalker extends TreeWalkerAdapter
{
    /** @var bool */
    private $isInsideCondition;

    public function walkSelectStatement(SelectStatement $selectStatement): void
    {
        $this->isInsideCondition = false;

        if ($selectStatement->whereClause) {
            $this->walkWhereClause($selectStatement->whereClause);
        }

        if ($selectStatement->havingClause) {
            $this->walkHavingClause($selectStatement->havingClause);
        }

        if ($selectStatement->orderByClause) {
            $this->walkOrderByClause($selectStatement->orderByClause);
        }
    }

    public function walkUpdateStatement(UpdateStatement $updateStatement): void
    {
        $this->notAllowedInsideCondition('update statements are forbidden');
    }

    public function walkDeleteStatement(DeleteStatement $deleteStatement): void
    {
        $this->notAllowedInsideCondition('delete statements are forbidden');
    }

    /**
     * @param mixed $selectClause
     */
    public function walkSelectClause($selectClause): void
    {
        $this->notAllowedInsideCondition('select clauses are forbidden');
    }

    /**
     * @param mixed $fromClause
     */
    public function walkFromClause($fromClause): void
    {
        $this->notAllowedInsideCondition('from clauses are forbidden');
    }

    /**
     * @param mixed $orderByClause
     */
    public function walkOrderByClause($orderByClause): void
    {
        $this->notAllowedInsideCondition('order by clauses are forbidden');
    }

    /**
     * @param mixed $orderByItem
     */
    public function walkOrderByItem($orderByItem): void
    {
        $this->notAllowedInsideCondition('order by items are forbidden');
    }

    /**
     * @param mixed $havingClause
     */
    public function walkHavingClause($havingClause): void
    {
        $this->walkConditionalExpression($havingClause->conditionalExpression);
    }

    /**
     * @param mixed $join
     */
    public function walkJoin($join): void
    {
        $this->notAllowedInsideCondition('joins are forbidden');
    }

    /**
     * @param mixed $selectExpression
     */
    public function walkSelectExpression($selectExpression): void
    {
        $this->notAllowedInsideCondition('select expression are forbidden');
    }

    /**
     * @param mixed $quantifiedExpression
     */
    public function walkQuantifiedExpression($quantifiedExpression): void
    {
        $this->notAllowedInsideCondition('quantified expressions are forbidden');
    }

    /**
     * @param mixed $subselect
     */
    public function walkSubselect($subselect): void
    {
        $this->notAllowedInsideCondition('subselects are forbidden');
    }

    /**
     * @param mixed $subselectFromClause
     */
    public function walkSubselectFromClause($subselectFromClause): void
    {
        $this->notAllowedInsideCondition('subselect from clauses are forbidden');
    }

    /**
     * @param mixed $simpleSelectClause
     */
    public function walkSimpleSelectClause($simpleSelectClause): void
    {
        $this->notAllowedInsideCondition('simple select clauses are forbidden');
    }

    public function walkParenthesisExpression(ParenthesisExpression $parenthesisExpression): void
    {
        $parenthesisExpression->expression->dispatch($this);
    }

    /**
     * @param mixed $simpleSelectExpression
     */
    public function walkSimpleSelectExpression($simpleSelectExpression): void
    {
        $this->notAllowedInsideCondition('simple select expressions are forbidden');
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param AggregateExpression $aggregateExpression
     */
    public function walkAggregateExpression($aggregateExpression): void
    {
        $this->walkSimpleArithmeticExpression($aggregateExpression->pathExpression);
    }

    public function walkDeleteClause(DeleteClause $deleteClause): void
    {
        $this->notAllowedInsideCondition('delete clauses are forbidden');
    }

    /**
     * @param mixed $updateClause
     */
    public function walkUpdateClause($updateClause): void
    {
        $this->notAllowedInsideCondition('update clauses are forbidden');
    }

    /**
     * @param mixed $updateItem
     */
    public function walkUpdateItem($updateItem): void
    {
        $this->notAllowedInsideCondition('update items are forbidden');
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param WhereClause $whereClause
     */
    public function walkWhereClause($whereClause): void
    {
        $this->walkConditionalExpression($whereClause->conditionalExpression);
    }

    /**
     * @param mixed $conditionalExpression
     */
    public function walkConditionalExpression($conditionalExpression): void
    {
        $this->isInsideCondition = true;

        if (!$conditionalExpression instanceof ConditionalExpression) {
            $this->walkConditionalTerm($conditionalExpression);

            return;
        }

        array_walk($conditionalExpression->conditionalTerms, [$this, 'walkConditionalTerm']);
    }

    /**
     * @param mixed $conditionalTerm
     */
    public function walkConditionalTerm($conditionalTerm): void
    {
        if (!$conditionalTerm instanceof ConditionalTerm) {
            $this->walkConditionalFactor($conditionalTerm);

            return;
        }

        array_walk($conditionalTerm->conditionalFactors, [$this, 'walkConditionalFactor']);
    }

    /**
     * @param mixed $factor
     */
    public function walkConditionalFactor($factor): void
    {
        $this->walkConditionalPrimary($factor instanceof ConditionalFactor ? $factor->conditionalPrimary : $factor);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param ConditionalPrimary $primary
     */
    public function walkConditionalPrimary($primary): void
    {
        if ($primary->isSimpleConditionalExpression()) {
            $primary->simpleConditionalExpression->dispatch($this);

            return;
        } elseif ($primary->isConditionalExpression()) {
            $this->walkConditionalExpression($primary->conditionalExpression);
        }
    }

    /**
     * @param mixed $existsExpression
     */
    public function walkExistsExpression($existsExpression): void
    {
        $this->notAllowedInsideCondition('exists expressions are forbidden');
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param CollectionMemberExpression $collectionMemberExpression
     */
    public function walkCollectionMemberExpression($collectionMemberExpression): void
    {
        if ($collectionMemberExpression->entityExpression instanceof InputParameter) {
            $this->walkInputParameter($collectionMemberExpression->entityExpression);
        }
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param NullComparisonExpression $nullComparisonExpression
     */
    public function walkNullComparisonExpression($nullComparisonExpression): void
    {
        if ($nullComparisonExpression->expression instanceof InputParameter) {
            $this->walkInputParameter($nullComparisonExpression->expression);
        }

        $nullComparisonExpression->expression->dispatch($this);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param InExpression $inExpression
     */
    public function walkInExpression($inExpression): void
    {
        $this->walkArithmeticExpression($inExpression->expression);

        if ($inExpression->subselect) {
            $this->walkSubselect($inExpression->subselect);
        }

        array_walk($inExpression->literals, [$this, 'walkInParameter']);
    }

    /**
     * @param mixed $instanceOfExpression
     */
    public function walkInstanceOfExpression($instanceOfExpression): void
    {
        $this->notAllowedInsideCondition('instanceOf expressions are forbidden');
    }

    /**
     * @param mixed $inParameter
     */
    public function walkInParameter($inParameter): void
    {
        if ($inParameter instanceof InputParameter) {
            $this->walkInputParameter($inParameter);
        }
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param BetweenExpression $betweenExpression
     */
    public function walkBetweenExpression($betweenExpression): void
    {
        $this->walkArithmeticExpression($betweenExpression->expression);
        $this->walkArithmeticExpression($betweenExpression->leftBetweenExpression);
        $this->walkArithmeticExpression($betweenExpression->rightBetweenExpression);
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param LikeExpression $likeExpression
     */
    public function walkLikeExpression($likeExpression): void
    {
        $likeExpression->stringExpression->dispatch($this);

        if ($likeExpression->stringPattern instanceof InputParameter) {
            $this->walkInputParameter($likeExpression->stringPattern);
        }
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param ComparisonExpression $comparisonExpression
     */
    public function walkComparisonExpression($comparisonExpression): void
    {
        if ($comparisonExpression->leftExpression instanceof Node) {
            $comparisonExpression->leftExpression->dispatch($this);
        }

        if ($comparisonExpression->rightExpression instanceof Node) {
            $comparisonExpression->rightExpression->dispatch($this);
        }
    }

    /**
     * @param mixed $inputParameter
     */
    public function walkInputParameter($inputParameter): void
    {
        $this->notAllowedInsideCondition('input parameters are forbidden');
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @param ArithmeticExpression $arithmeticExpression
     */
    public function walkArithmeticExpression($arithmeticExpression): void
    {
        if ($arithmeticExpression->isSubselect()) {
            $this->walkSubselect($arithmeticExpression->subselect);
        }

        $this->walkSimpleArithmeticExpression($arithmeticExpression->simpleArithmeticExpression);
    }

    /**
     * @param mixed $simpleArithmeticExpression
     */
    public function walkSimpleArithmeticExpression($simpleArithmeticExpression): void
    {
        if (!$simpleArithmeticExpression instanceof SimpleArithmeticExpression) {
            $this->walkArithmeticTerm($simpleArithmeticExpression);

            return;
        }

        array_walk($simpleArithmeticExpression->arithmeticTerms, [$this, 'walkArithmeticTerm']);
    }

    /**
     * @param mixed $arithmeticTerm
     */
    public function walkArithmeticTerm($arithmeticTerm): void
    {
        if (is_string($arithmeticTerm)) {
            return;
        } elseif (!$arithmeticTerm instanceof ArithmeticTerm) {
            $this->walkArithmeticFactor($arithmeticTerm);

            return;
        }

        array_walk($arithmeticTerm->arithmeticFactors, [$this, 'walkArithmeticFactor']);
    }

    /**
     * @param mixed $arithmeticFactor
     */
    public function walkArithmeticFactor($arithmeticFactor): void
    {
        if (is_string($arithmeticFactor)) {
            return;
        } elseif (!$arithmeticFactor instanceof ArithmeticFactor) {
            $this->walkArithmeticPrimary($arithmeticFactor);

            return;
        }

        $this->walkArithmeticPrimary($arithmeticFactor->arithmeticPrimary);
    }

    /**
     * @param mixed $arithmeticPrimary
     */
    public function walkArithmeticPrimary($arithmeticPrimary): void
    {
        if ($arithmeticPrimary instanceof SimpleArithmeticExpression) {
            $this->walkSimpleArithmeticExpression($arithmeticPrimary);
        } elseif ($arithmeticPrimary instanceof Node) {
            $arithmeticPrimary->dispatch($this);
        }
    }

    /**
     * @param mixed $stringPrimary
     */
    public function walkStringPrimary($stringPrimary): void
    {
        if (!is_string($stringPrimary)) {
            $stringPrimary->dispatch($this);
        }
    }

    private function notAllowedInsideCondition(string $message): void
    {
        if ($this->isInsideCondition) {
            throw InvalidQueryException::forbidden(sprintf('Invalid DQL query: %s.', $message));
        }
    }
}
