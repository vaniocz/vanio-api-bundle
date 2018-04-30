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

    public function __construct(
        OrderBy $orderBy,
        Limit $limit,
        Properties $properties,
        ?string $dqlAlias = null
    ) {
        $this->orderBy = $orderBy;
        $this->limit = $limit;
        $this->properties = $properties;
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

    public function buildSpecification(string $dqlAlias): AndX
    {
        return new AndX($this->orderBy, $this->limit, $this->properties);
    }
}
