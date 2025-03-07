<?php

namespace Customize\Form\Type\Admin;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Eccube\Form\Type\ToggleSwitchType;

class MemberTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('is_warehouse', ToggleSwitchType::class, [
            'label' => 'common.is_warehouse',
            'required' => false,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield \Eccube\Form\Type\Admin\MemberType::class;
    }
      
}