<?php
namespace Vanio\ApiBundle\Serializer;

use Doctrine\ORM\EntityManager;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Vanio\DoctrineGenericTypes\DBAL\ScalarObject;

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
            function (string $class, $criteria) {
                $class = json_encode($class);
                $entityRepository = "\$this->get('doctrine')->getManagerForClass({$class})->getRepository({$class})";

                if (is_array($criteria)) {
                    foreach ($criteria as &$value) {
                        if ($value instanceof ScalarObject) {
                            $value = $value->scalarValue();
                        }
                    }

                    $criteria = var_export($criteria, true);

                    return "{$entityRepository}->findOneBy({$criteria})";
                }

                $criteria = json_encode($criteria);

                return "{$entityRepository}->find({$criteria})";
            },
            function (array $variables, string $class, $criteria) {
                $entityManager = $variables['container']->get('doctrine')->getManagerForClass($class);
                assert($entityManager instanceof EntityManager);
                $entityRepository = $entityManager->getRepository($class);

                return is_array($criteria)
                    ? $entityRepository->findOneBy($criteria)
                    : $entityRepository->find($criteria);
            }
        );
    }
}
