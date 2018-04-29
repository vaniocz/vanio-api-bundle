<?php
namespace Vanio\ApiBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\ApiBundle\Specification\Filter;
use Vanio\ApiBundle\Specification\Properties;
use Vanio\DomainBundle\Pagination\OrderByParamConverter;
use Vanio\DomainBundle\Pagination\Page;
use Vanio\DomainBundle\Pagination\PageParamConverter;

class FilterParamConverter implements ParamConverterInterface
{
    /** @var mixed[] */
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options + [
            'dql_alias' => null,
            'page_class' => Page::class,
        ];
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $options = $configuration->getOptions() + $this->options;
        $configuration->setClass($options['page_class']);
        (new OrderByParamConverter($options))->apply($request, $configuration);
        $orderBy = $request->attributes->get($configuration->getName());
        (new PageParamConverter($options))->apply($request, $configuration);
        $page = $request->attributes->get($configuration->getName());
        (new PropertiesParamConverter($options))->apply($request, $configuration);
        $properties = $request->attributes->get($configuration->getName());
        $filter = new Filter($orderBy, $page, $properties, $options['dql_alias']);
        $request->attributes->set($configuration->getName(), $filter);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === Filter::class;
    }
}
