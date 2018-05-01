<?php
namespace Vanio\ApiBundle\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Vanio\ApiBundle\Specification\Limit;

class LimitParamConverter implements ParamConverterInterface
{
    private const DEFAULT_OPTIONS = [
        'limit_parameter' => 'limit',
        'offset_parameter' => 'offset',
        'default_limit' => 100,
    ];

    /** @var mixed[] */
    private $options;

    /**
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options + self::DEFAULT_OPTIONS;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $options = $configuration->getOptions() + $this->options;
        $limit = new Limit(
            $request->query->get($options['limit_parameter'], $options['default_limit']),
            $request->query->get($options['offset_parameter'], 0)
        );
        $request->attributes->set($configuration->getName(), $limit);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === Limit::class;
    }
}
