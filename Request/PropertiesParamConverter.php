<?php
namespace Vanio\ApiBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\ApiBundle\Specification\Properties;

class PropertiesParamConverter implements ParamConverterInterface
{
    public function supports(ParamConverter $configuration): bool
    {
        return is_a($configuration->getClass(), Properties::class, true);
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $properties = Properties::fromString($request->query->get('properties', ''));
        $request->attributes->set($configuration->getName(), $properties);

        return true;
    }
}
