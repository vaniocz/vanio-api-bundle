<?php
namespace Vanio\ApiBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\ApiBundle\Specification\Query;

class QueryParamConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration): bool
    {
        return is_a($configuration->getClass(), Query::class, true);
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $query = new Query($request->query->get('query', ''));
        $request->attributes->set($configuration->getName(), $query);

        return true;
    }
}
