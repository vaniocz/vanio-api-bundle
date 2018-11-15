<?php
namespace Vanio\ApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType as SymfonyDateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'widget' => 'single_text',
                'documentation' => [
                    'type' => 'string',
                    'format' => 'date',
                    'example' => date('Y-m-d'),
                ],
            ])
            ->setAllowedValues('widget', 'single_text');
    }

    public function getParent(): string
    {
        return SymfonyDateType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'vanio_date';
    }
}
