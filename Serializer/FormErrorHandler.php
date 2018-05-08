<?php
namespace Vanio\ApiBundle\Serializer;

use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

class FormErrorHandler implements SubscribingHandlerInterface
{
    /**
     * @param VisitorInterface $visitor
     * @param Form|FormView $form
     * @return mixed[]
     */
    public function serializeForm(VisitorInterface $visitor, $form): array
    {
        return $this->resolveFormErrorMessages($form);
    }

    /**
     * @return mixed[]
     */
    public static function getSubscribingMethods(): array
    {
        $subscribingMethods = [];

        foreach (['json', 'xml', 'yml'] as $format) {
            foreach ([Form::class, FormView::class] as $type) {
                $subscribingMethods[] = [
                    'type' => $type,
                    'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                    'format' => $format,
                    'method' => 'serializeForm',
                ];
            }
        }

        return $subscribingMethods;
    }

    /**
     * @param Form|FormView $form
     * @return mixed[]
     */
    private function resolveFormErrorMessages($form): array
    {
        $errorMessages = [];

        foreach ($form instanceof Form ? $form->getErrors() : $form->vars['errors'] as $error) {
            $errorMessages[] = $error->getMessage();
        }

        foreach ($form as $name => $child) {
            if ($childErrorMessages = $this->resolveFormErrorMessages($child)) {
                $errorMessages[$name] = $childErrorMessages;
            }
        }

        return $errorMessages;
    }
}
