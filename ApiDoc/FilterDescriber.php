<?php
namespace Vanio\ApiBundle\ApiDoc;

use EXSyst\Component\Swagger\Parameter;
use EXSyst\Component\Swagger\Swagger;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use Symfony\Component\Routing\Route;
use Vanio\ApiBundle\Specification\Filter;
use Vanio\ApiBundle\Specification\Limit;
use Vanio\ApiBundle\Specification\Properties;

class FilterDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    private const PARAMETERS = [
        'properties' => [
            'type' => 'string',
            'description' => 'The properties to include/exclude',
        ],
        'order' => [
            'type' => 'string',
            'description' => 'The property used to order',
            'default' => 'id',
        ],
        'limit' => [
            'type' => 'integer',
            'description' => 'The maximal number of records',
            'default' => 100,
        ],
        'offset' => [
            'type' => 'integer',
            'description' => 'The number of records to skip',
            'default' => 0,
        ],
    ];

    public function describe(Swagger $api, Route $route, \ReflectionMethod $reflectionMethod): void
    {
        if (!$parameters = $this->resolveActionParameters($reflectionMethod)) {
            return;
        }

        foreach ($this->getOperations($api, $route) as $operation) {
            $operationParameters = $operation->getParameters();

            foreach ($parameters as $name) {
                if ($operationParameters->has($name, 'query')) {
                    continue;
                }

                $operationParameters->add(new Parameter(self::PARAMETERS[$name] + ['name' => $name, 'in' => 'query']));
            }
        }
    }

    /**
     * @return string[]
     */
    private function resolveActionParameters(\ReflectionMethod $reflectionMethod): array
    {
        $parameters = [];

        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $type = $reflectionParameter->getType();

            if (!$type || $type->isBuiltin()) {
                continue;
            }

            $type = $type->getName();

            if (is_a($type, Filter::class, true)) {
                return array_keys(self::PARAMETERS);
            } elseif (is_a($type, Properties::class, true)) {
                $parameters['properties'] = true;
            } elseif (is_a($type, Limit::class, true)) {
                $parameters['limit'] = true;
                $parameters['offset'] = true;
            }
        }

        return array_keys($parameters);
    }
}
