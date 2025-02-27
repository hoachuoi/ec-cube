<?php

namespace Customize\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Admin\CustomerType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerTypeExtension extends AbstractTypeExtension
{
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * Constructor để inject EccubeConfig
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('account_type', ChoiceType::class, [
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
            ]);


        // Thêm event listener để kiểm tra đường dẫn file
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            /** @var FormInterface $form */
            $form = $event->getForm();
            $saveImgDir = $this->eccubeConfig['eccube_save_image_dir'];
            $tempImgDir = $this->eccubeConfig['eccube_temp_image_dir'];

            // Kiểm tra dữ liệu của avatar_file
            $avatarFile = $form->get('avatar_file')->getData();
            if ($avatarFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                // Nếu là file vừa upload, không cần kiểm tra đường dẫn (controller sẽ xử lý)
                return;
            } elseif (is_string($avatarFile)) {
                // Nếu là tên file (từ FilePond), kiểm tra đường dẫn
                $this->validateFilePath($form->get('avatar_file'), [$saveImgDir, $tempImgDir]);
            }
        });
    }

    /**
     * Kiểm tra xem tên file có nằm trong các thư mục hợp lệ không.
     *
     * @param FormInterface $form
     * @param array $dirs
     */
    private function validateFilePath($form, $dirs)
    {
        $fileName = $form->getData();
        if (!is_string($fileName)) {
            return; // Không làm gì nếu không phải chuỗi (file gốc sẽ được controller xử lý)
        }

        if (strpos($fileName, '..') !== false) {
            $form->addError(new FormError(trans('admin.product.image__invalid_path')));
            return;
        }

        $fileInDir = array_filter($dirs, function ($dir) use ($fileName) {
            $filePath = realpath($dir . '/' . $fileName);
            $topDirPath = realpath($dir);
            return $filePath && strpos($filePath, $topDirPath) === 0 && $filePath !== $topDirPath;
        });

        if (!$fileInDir) {
            $form->addError(new FormError(trans('admin.product.image__invalid_path')));
        }
    }

    public static function getExtendedTypes(): iterable
    {
        yield CustomerType::class;
    }
}