<?php
namespace Vanio\ApiBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\ApiBundle\Specification\Filter;
use Vanio\ApiBundle\Specification\Properties;
use Vanio\DomainBundle\Pagination\OrderByParamConverter;

class FilterParamConverter implements ParamConverterInterface
{
    /** @var mixed[] */
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options + ['dql_alias' => null];
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $options = $configuration->getOptions() + $this->options;
        (new OrderByParamConverter($options))->apply($request, $configuration);
        $orderBy = $request->attributes->get($configuration->getName());
        (new LimitParamConverter($options))->apply($request, $configuration);
        $limit = $request->attributes->get($configuration->getName());
        (new PropertiesParamConverter($options))->apply($request, $configuration);
        $properties = $request->attributes->get($configuration->getName());
        $filter = new Filter($orderBy, $limit, $properties, $options['dql_alias']);
        $request->attributes->set($configuration->getName(), $filter);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === Filter::class;
    }
}
