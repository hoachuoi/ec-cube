<?php

namespace Customize\Form\Type\Admin;

use Eccube\Entity\Member;
use Eccube\Form\Type\ToggleSwitchType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;

class ProductTypeExtension extends AbstractTypeExtension
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('id_warehouse', EntityType::class, [
            'class' => Member::class,
            'choice_label' => 'name',
            'multiple' => false,
            'expanded' => false,
            'label' => 'common.id_warehouse',
            'required' => false,
            'placeholder' => '---',
            'query_builder' => function () {
                return $this->entityManager->getRepository(Member::class)
                    ->createQueryBuilder('m')
                    ->where('m.is_warehouse = :is_warehouse')
                    ->setParameter('is_warehouse', 1);
            },
            'choice_value' => function (?Member $entity) {
                return $entity ? $entity->getId() : null;
            },
        ]);
    
        // Transformer để chuyển đổi giữa ID và Member
        $builder->get('id_warehouse')
            ->addModelTransformer(new CallbackTransformer(
                function ($idWarehouse) {
                    return $idWarehouse ? $this->entityManager->getRepository(Member::class)->find($idWarehouse) : null;
                },
                function ($member) {
                    return $member instanceof Member ? $member->getId() : null;
                }
            ));
    }
    

    public static function getExtendedTypes(): iterable
    {
        yield \Eccube\Form\Type\Admin\ProductType::class;
    }
}