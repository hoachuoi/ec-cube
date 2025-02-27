<?php
namespace Customize\Form\Extension;

use Eccube\Form\Type\Front\EntryType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Eccube\Common\EccubeConfig;

class AccountTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * AccountTypeExtension constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('account_type', ChoiceType::class, [
            'label' => 'common.account_type', 
            'choices' => [
                'common.account_type_personal' => 'personal', 
                'common.account_type_company' => 'company'  
            ],
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'data' => 'personal',
            'constraints' => [new NotBlank()],
            'attr' => ['class' => 'account-type-radio'],
            'translation_domain' => 'messages',
        ])

        ->add('avatar_file', FileType::class, [
            'label' => 'common.customer.avatar',
            'multiple' => false,
            'required' => false,
            'mapped' => false,
        ])
        ->add('avatar_filename', HiddenType::class, [
            'required' => false,
            'mapped' => false,
        ])
        // Thêm trường add_images để lưu các ảnh mới upload
        ->add('add_images', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'allow_add' => true,
            'mapped' => false, // Không map trực tiếp vào entity
            'prototype' => true,
        ])
        // Thêm trường delete_images để lưu các ảnh bị xóa
        ->add('delete_images', CollectionType::class, [
            'entry_type' => HiddenType::class,
            'allow_add' => true,
            'mapped' => false, // Không map trực tiếp vào entity
            'prototype' => true,
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var FormInterface $form */
            $form = $event->getForm();
            $saveImgDir = $this->eccubeConfig['eccube_save_image_dir'];
            $tempImgDir = $this->eccubeConfig['eccube_temp_image_dir'];
            
            $this->validateFilePath($form, [$saveImgDir]);

        });
    }

    /**
     * Kiểm tra đường dẫn file hợp lệ.
     *
     * @param $form
     * @param $dirs
     */
    private function validateFilePath($form, $dirs)
    {
        foreach ($form->getData() as $fileName) {
            if (strpos($fileName, '..') !== false) {
                $form->addError(new FormError('Đường dẫn ảnh không hợp lệ.'));
                break;
            }

            $fileInDir = array_filter($dirs, function ($dir) use ($fileName) {
                $filePath = realpath($dir . '/' . $fileName);
                $topDirPath = realpath($dir);
                return $filePath && strpos($filePath, $topDirPath) === 0;
            });

            if (!$fileInDir) {
                $form->addError(new FormError('Đường dẫn ảnh không hợp lệ.'));
            }
        }
    }

    public static function getExtendedTypes(): iterable
    {
        yield EntryType::class;
    }
}
