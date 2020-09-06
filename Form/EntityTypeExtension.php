<?php
namespace Vanio\ApiBundle\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\DataTransformer\ChoicesToValuesTransformer;
use Symfony\Component\Form\Extension\Core\DataTransformer\ChoiceToValueTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityTypeExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('strict', false);
    }

    /**
     * @param mixed[] $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options = []): void
    {
        if (!$options['strict']) {
            return;
        }

        $viewTransformers = [];

        foreach ($builder->getViewTransformers() as $viewTransformer) {
            if ($viewTransformer instanceof ChoicesToValuesTransformer) {
                $isMultiple = true;
            } elseif (!$viewTransformer instanceof ChoiceToValueTransformer) {
                continue;
            }

            $viewTransformers[] = new StrictChoicesToValuesTransformer(
                $viewTransformer,
                $options['em'],
                $options['class'],
                $isMultiple ?? false
            );
        }

        $builder->resetViewTransformers();

        foreach ($viewTransformers as $viewTransformer) {
            $builder->addViewTransformer($viewTransformer);
        }
    }

    public function getExtendedType(): string
    {
        return EntityType::class;
    }
}
