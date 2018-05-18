<?php
namespace Vanio\ApiBundle\Specification;

use Happyr\DoctrineSpecification\Logic\AndX;
use Vanio\DomainBundle\Doctrine\Specification;
use Vanio\DomainBundle\Pagination\OrderBy;

class Filter extends Specification
{
    /** @var OrderBy */
    private $orderBy;

    /** @var Limit */
    private $limit;

    /** @var Properties */
    private $properties;

    /** @var Query */
    private $query;

    public function __construct(
        OrderBy $orderBy,
        Limit $limit,
        Properties $properties,
        Query $query,
        ?string $dqlAlias = null
    ) {
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->properties = $properties;
        $this->query = $query;
        $this->dqlAlias = $dqlAlias;
    }

    public function orderBy(): OrderBy
    {
        return $this->orderBy;
    }

    public function limit(): Limit
    {
        return $this->limit;
    }

    public function properties(): Properties
    {
        return $this->properties;
    }

    public function query(): Query
    {
        return $this->query;
    }

    public function buildSpecification(string $dqlAlias): AndX
    {
        return new AndX($this->properties, $this->query, $this->limit, $this->orderBy);
    }
}
