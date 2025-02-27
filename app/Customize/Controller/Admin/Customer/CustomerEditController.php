<?php

namespace Customize\Controller\Admin\Customer;

use Composer\Util\Filesystem;
use Eccube\Controller\Admin\Customer\CustomerEditController as BaseCustomerEditController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\CustomerType;
use Eccube\Util\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;



class CustomerEditController extends  BaseCustomerEditController
{

    /**
     * @Route("/%eccube_admin_route%/customer/new", name="admin_customer_new", methods={"GET", "POST"})
     * @Route("/%eccube_admin_route%/customer/{id}/edit", requirements={"id" = "\d+"}, name="admin_customer_edit", methods={"GET", "POST"})
     * @Template("@admin/Customer/edit.twig")
     */
    public function index(Request $request, PaginatorInterface $paginator, $id = null)
    {
        $this->entityManager->getFilters()->enable('incomplete_order_status_hidden');
        // 編集
        if ($id) {
            /** @var Customer $Customer */
            $Customer = $this->customerRepository
                ->find($id);

            if (is_null($Customer)) {
                throw new NotFoundHttpException();
            }

            $oldStatusId = $Customer->getStatus()->getId();
            $Customer->setPlainPassword($this->eccubeConfig['eccube_default_password']);
        // 新規登録
        } else {
            $Customer = $this->customerRepository->newCustomer();

            $oldStatusId = null;
        }

        // 会員登録フォーム
        $builder = $this->formFactory
            ->createBuilder(CustomerType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch($event, EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_INITIALIZE);

        $form = $builder->getForm();

        $form->handleRequest($request);
        $page_count = (int) $this->session->get('eccube.admin.customer_edit.order.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.admin.customer_edit.order.page_count', $page_count);
                    break;
                }
            }
        }
        $page_no = (int) $request->get('page_no', 1);
        $qb = $this->orderRepository->getQueryBuilderByCustomer($Customer);
        $pagination = [];
        if (!is_null($Customer->getId())) {
            $pagination = $paginator->paginate(
                $qb,
                $page_no > 0 ? $page_no : 1,
                $page_count
            );
        }

        if ($form->isSubmitted() && $form->isValid()) {
            log_info('会員登録開始', [$Customer->getId()]);

            if ($Customer->getPlainPassword() !== $this->eccubeConfig['eccube_default_password']) {
                $password = $this->passwordHasher->hashPassword($Customer, $Customer->getPlainPassword());
                $Customer->setPassword($password);
            }

            $avatarFilename = $form->get('avatar_filename')->getData();

            if ($avatarFilename) {
                $tempPath = $this->eccubeConfig['eccube_temp_image_dir'] . '/' . $avatarFilename;
                $savePath = $this->eccubeConfig['eccube_save_image_dir'] . '/' . $avatarFilename;
                if (file_exists($tempPath)) {
                    $filesystem = new \Symfony\Component\Filesystem\Filesystem();
                    $filesystem->copy($tempPath, $savePath, true);
                    $filesystem->remove($tempPath);
                    $Customer->setAvatarFilename($avatarFilename);
                } else{
                    $Customer->setAvatarFilename('avartar_default.png');
                }
            }
            // 退会ステータスに更新の場合、ダミーのアドレスに更新
            $newStatusId = $Customer->getStatus()->getId();
            if ($oldStatusId != $newStatusId && $newStatusId == CustomerStatus::WITHDRAWING) {
                $Customer->setEmail(StringUtil::random(60).'@dummy.dummy');
            }
            $this->entityManager->persist($Customer);
            $this->entityManager->flush();

            log_info('会員登録完了', [$Customer->getId()]);

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch($event, EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_COMPLETE);

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_customer_edit', [
                'id' => $Customer->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
        ];
    }
   

/**
 * @Route("/admin/customer/image/process", name="admin_customer_image_process", methods={"POST"})
 */
public function imageProcess(Request $request, LoggerInterface $logger)
{
    $logger->info('--- imageProcess START ---');

    // Debug toàn bộ request
    $logger->info('Request files:', $request->files->all());
    $logger->info('Request headers:', $request->headers->all());

    // Lấy file từ request
    $image = $request->files->get('admin_customer')['avatar_file'] ?? null;
    $logger->info('Received image', ['image' => $image ? $image->getClientOriginalName() : 'null']);

    if (!$image || !$image->isValid()) {
        $logger->warning('No valid image received');
        return new JsonResponse(['error' => 'No valid image uploaded'], 400);
    }

    $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];

    // Kiểm tra MIME type
    $mimeType = $image->getMimeType();
    $logger->info('Processing image', ['mimeType' => $mimeType]);
    if (0 !== strpos($mimeType, 'image/')) {
        $logger->error('Invalid file type', ['mimeType' => $mimeType]);
        return new JsonResponse(['error' => 'File is not an image'], 415);
    }

    // Kiểm tra extension
    $extension = $image->getClientOriginalExtension();
    if (!in_array(strtolower($extension), $allowExtensions)) {
        $logger->error('Unsupported file extension', ['extension' => $extension]);
        return new JsonResponse(['error' => 'Unsupported file extension'], 415);
    }

    // Tạo tên file duy nhất
    $filename = date('mdHis') . uniqid('_') . '.' . $extension;
    $logger->info('Generated filename', ['filename' => $filename]);

    // Di chuyển file đến thư mục tạm
    try {
        $image->move($this->eccubeConfig['eccube_temp_image_dir'], $filename);
        $logger->info('Image moved successfully', ['path' => $this->eccubeConfig['eccube_temp_image_dir'] . '/' . $filename]);
    } catch (\Exception $e) {
        $logger->error('Failed to move image', ['error' => $e->getMessage()]);
        return new JsonResponse(['error' => 'Failed to save image'], 500);
    }

    // Gửi sự kiện (nếu cần)
    $event = new EventArgs(['image' => $image, 'filename' => $filename], $request);
    $this->eventDispatcher->dispatch($event, EccubeEvents::ADMIN_PRODUCT_ADD_IMAGE_COMPLETE);

    $logger->info('Returning response', ['file' => $filename]);
    $logger->info('--- imageProcess END ---');

    return new Response($filename); // Trả về tên file thuần túy cho FilePond
}
   /**
 * @Route("/admin/customer/image/load", name="admin_customer_image_load", methods={"GET"})
 */
public function imageLoad(Request $request)
{
    if (!$request->isXmlHttpRequest()) {
        throw new BadRequestHttpException();
    }

    $dirs = [
        $this->eccubeConfig['eccube_save_image_dir'],
        $this->eccubeConfig['eccube_temp_image_dir'],
    ];

    foreach ($dirs as $dir) {
        if (strpos($request->query->get('source'), '..') !== false) {
            throw new NotFoundHttpException();
        }
        $image = \realpath($dir.'/'.$request->query->get('source'));
        $dir = \realpath($dir);

        if (\is_file($image) && \str_starts_with($image, $dir)) {
            $file = new \SplFileObject($image);
            return $this->file($file, $file->getBasename());
        }
    }

    throw new NotFoundHttpException();
}

    /**
     * アップロード画像をすぐ削除する際にコールされるメソッド.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#revert
     * @Route("/admin/customer/image/revert", name="admin_customer_image_revert", methods={"DELETE"})
     */
    public function imageRevert(Request $request, LoggerInterface $logger)
    {
        // if (!$request->isXmlHttpRequest() && $this->isTokenValid()) {
        //     throw new BadRequestHttpException();
        // }

        $tempFile = $this->eccubeConfig['eccube_temp_image_dir'].'/'.$request->getContent();
        $logger->info('Deleting image', ['file' => $tempFile]);
        if (is_file($tempFile)) {
            $fs = new Filesystem();
            $fs->remove($tempFile);

            return new Response(null, Response::HTTP_NO_CONTENT);
        }

        throw new NotFoundHttpException();
    }
}