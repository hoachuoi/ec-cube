<?php

namespace Customize\Form\Extension;

use Eccube\Form\Type\Front\EntryType;
use Symfony\Component\Form\AbstractTypeExtension; 
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CompanyNameRequireExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
       $options = $builder->get('company_name')->getOptions();

       $options['required'] = true;
       $options['constraints'] = new NotBlank();
       $options['attr']['placeholder'] = 'Company Name';
       $builder->add('company_name', TextType::class, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return EntryType::class;
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        yield EntryType::class;
    }
}