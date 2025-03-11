<?php

namespace Plugin\PluginHoliday\Form\Type\Admin;

use Plugin\PluginHoliday\Entity\Holiday;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchHolidayType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [
            'required' => false,
        ])
        ->add('holiday_message', TextType::class, [
            'required' => false,
        ]) 
        ->add('holiday_date', DateType::class, [
            'required' => false,
            'label' => 'plugin.plugin_holiday.holiday_date',
            'widget' => 'single_text',
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