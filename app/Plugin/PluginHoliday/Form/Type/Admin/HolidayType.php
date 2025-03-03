<?php

namespace Plugin\PluginHoliday\Form\Type\Admin;

use Plugin\PluginHoliday\Entity\Holiday;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class HolidayType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'constraints' => [
                new NotBlank(),
                new Length(['max' => 255]),
            ],
        ])
        ->add('holiday_message', TextType::class, [
            'constraints' => [
                new NotBlank(),
                new Length(['max' => 255]),
            ],
        ])
        ->add('holiday_date', DateType::class, [
            'widget' => 'choice',         
            'months' => range(1, 12),      
            'days' => range(1, 31),        
            'placeholder' => [
                'day' => 'Day',
                'month' => 'Month',
                'year' => 'Year',
            ],
            'data' => new \DateTime(),
            'constraints' => [
                new NotBlank(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Holiday::class,
        ]);
    }
}
