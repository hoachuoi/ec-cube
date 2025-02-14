<?php 

namespace Customize\Form\Type\Admin;

use Eccube\Form\Type\Admin\CustomerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
       $builder->add('account_type', ChoiceType::class, [
            'label' => 'common.account_type',
            'choices' => [
                'common.account_type_personal' => 'personal',
                'common.account_type_company' => 'business'
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'data' => 'personal',
            'constraints' => [new NotBlank()],
            'attr' => ['class' => 'account-type-radio'],
            'translation_domain' => 'messages',
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield CustomerType::class;
    }
}