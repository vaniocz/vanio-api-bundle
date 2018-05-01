<?php
namespace Vanio\ApiBundle\Specification;

use Doctrine\ORM\AbstractQuery;
use Happyr\DoctrineSpecification\Result\ResultModifier;

class Limit implements ResultModifier
{
    /** @var int */
    private $limit;

    /** @var int */
    private $offset;

    public function __construct(int $limit, int $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    public function limit(): int
    {
        return $this->limit;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    /**
     * @param Query|AbstractQuery $query
     */
    public function modify(AbstractQuery $query): void
    {
        $query
            ->setFirstResult($this->offset)
            ->setMaxResults($this->limit);
    }
}
