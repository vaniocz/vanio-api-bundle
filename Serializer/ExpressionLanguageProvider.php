<?php
namespace Vanio\ApiBundle\Serializer;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[]
     */
    public function getFunctions(): array
    {
        return [$this->createEntityExpressionFunction()];
    }

    private function createEntityExpressionFunction(): ExpressionFunction
    {
        return new ExpressionFunction(
            'entity',
            function (string $class, string $id) {
                return sprintf('$this->get("doctrine")->getManagerForClass(%s)->find(%s, %s)', $class, $class, $id);
            },
            function (array $variables, string $class, $id) {
                return $variables['container']->get('doctrine')->getManagerForClass($class)->find($class, $id);
            }
        );
    }
}
