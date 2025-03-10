<?php

namespace Customize\Form\Type\Admin;

use Eccube\Form\Type\ToggleSwitchType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class ProductTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('is_warehouse', ToggleSwitchType::class, [
            'label' => 'common.is_warehouse',
            'required' => false,
            'data'=>true,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield \Eccube\Form\Type\Admin\ProductType::class;
    }
}