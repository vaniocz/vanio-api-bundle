<?php
namespace Vanio\ApiBundle\Specification;

use Happyr\DoctrineSpecification\Logic\AndX;
use Vanio\ApiBundle\Specification\Properties;
use Vanio\DomainBundle\Doctrine\Specification;
use Vanio\DomainBundle\Pagination\OrderBy;
use Vanio\DomainBundle\Pagination\PageSpecification;

class Filter extends Specification
{
    /** @var OrderBy */
    private $orderBy;

    /** @var PageSpecification */
    private $page;

    /** @var Properties */
    private $properties;

    public function __construct(
        OrderBy $orderBy,
        PageSpecification $page,
        Properties $properties,
        ?string $dqlAlias = null
    ) {
        $this->orderBy = $orderBy;
        $this->page = $page;
        $this->properties = $properties;
        $this->dqlAlias = $dqlAlias;
    }

    public function orderBy(): OrderBy
    {
        return $this->orderBy;
    }

    public function page(): PageSpecification
    {
        return $this->page;
    }

    public function properties(): Properties
    {
        return $this->properties;
    }

    public function buildSpecification(string $dqlAlias): AndX
    {
        return new AndX($this->orderBy, $this->page, $this->properties);
    }
}
