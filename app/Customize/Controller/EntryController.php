<?php

namespace Customize\Controller;

// use Composer\Util\Http\Response;
use Eccube\Controller\EntryController as BaseEntryController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Eccube\Form\Type\Front\EntryType;
use Eccube\Entity\Master\CustomerStatus;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
class EntryController extends BaseEntryController

{

   /**
     * 会員登録画面.
     *
     * @Route("/entry", name="entry", methods={"GET", "POST"})
     * @Route("/entry", name="entry_confirm", methods={"GET", "POST"})
     * @Template("Entry/index.twig")
     */
    public function index(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            log_info('認証済のためログイン処理をスキップ');

            return $this->redirectToRoute('mypage');
        }

        /** @var \Eccube\Entity\Customer $Customer */
        $Customer = $this->customerRepository->newCustomer();

        /** @var \Symfony\Component\Form\FormBuilderInterface $builder */
        $builder = $this->formFactory->createBuilder(EntryType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_ENTRY_INDEX_INITIALIZE);

        /** @var \Symfony\Component\Form\FormInterface $form */
        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('会員登録確認開始');
                    log_info('会員登録確認完了');

                    return $this->render(
                        'Entry/confirm.twig',
                        [
                            'form' => $form->createView(),
                            'Page' => $this->pageRepository->getPageByRoute('entry_confirm'),
                        ]
                    );

                case 'complete':
                    log_info('会員登録開始');
                    $avatarFilename = $form->get('avatar_filename')->getData();
                    log_info('Avatar filename from form:', ['filename' => $avatarFilename]);

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

                    $password = $this->passwordHasher->hashPassword($Customer, $Customer->getPlainPassword());
                    $Customer->setPassword($password);

                    $this->entityManager->persist($Customer);
                    $this->entityManager->flush();

                    log_info('会員登録完了');

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'Customer' => $Customer,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_ENTRY_INDEX_COMPLETE);

                    $activateFlg = $this->BaseInfo->isOptionCustomerActivate();

                    // 仮会員設定が有効な場合は、確認メールを送信し完了画面表示.
                    if ($activateFlg) {
                        $activateUrl = $this->generateUrl('entry_activate', ['secret_key' => $Customer->getSecretKey()], UrlGeneratorInterface::ABSOLUTE_URL);

                        // メール送信
                        $this->mailService->sendCustomerConfirmMail($Customer, $activateUrl);

                        if ($event->hasResponse()) {
                            return $event->getResponse();
                        }

                        log_info('仮会員登録完了画面へリダイレクト');

                        return $this->redirectToRoute('entry_complete');
                    } else {
                        // 仮会員設定が無効な場合は、会員登録を完了させる.
                        $qtyInCart = $this->entryActivate($request, $Customer->getSecretKey());

                        // URLを変更するため完了画面にリダイレクト
                        return $this->redirectToRoute('entry_activate', [
                            'secret_key' => $Customer->getSecretKey(),
                            'qtyInCart' => $qtyInCart,
                        ]);
                    }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * 会員登録完了画面.
     *
     * @Route("/entry/complete", name="entry_complete", methods={"GET"})
     * @Template("Entry/complete.twig")
     */
    public function complete()
    {
        return [];
    }

    /**
     * 会員のアクティベート（本会員化）を行う.
     *
     * @Route("/entry/activate/{secret_key}/{qtyInCart}", name="entry_activate", methods={"GET"})
     * @Template("Entry/activate.twig")
     */
    public function activate(Request $request, $secret_key, $qtyInCart = null)
    {
        $errors = $this->recursiveValidator->validate(
            $secret_key,
            [
                new Assert\NotBlank(),
                new Assert\Regex(
                    [
                        'pattern' => '/^[a-zA-Z0-9]+$/',
                    ]
                ),
            ]
        );

        if (!$this->session->has('eccube.login.target.path')) {
            $this->setLoginTargetPath($this->generateUrl('mypage', [], UrlGeneratorInterface::ABSOLUTE_URL));
        }

        if (!is_null($qtyInCart)) {
            return [
                'qtyInCart' => $qtyInCart,
            ];
        } elseif ($request->getMethod() === 'GET' && count($errors) === 0) {
            // 会員登録処理を行う
            $qtyInCart = $this->entryActivate($request, $secret_key);

            return [
                'qtyInCart' => $qtyInCart,
            ];
        }

        throw new HttpException\NotFoundHttpException();
    }

    /**
     * 会員登録処理を行う
     *
     * @param Request $request
     * @param $secret_key
     *
     * @return \Eccube\Entity\Cart|mixed
     */
    private function entryActivate(Request $request, $secret_key)
    {
        log_info('本会員登録開始');
        $Customer = $this->customerRepository->getProvisionalCustomerBySecretKey($secret_key);
        if (is_null($Customer)) {
            throw new HttpException\NotFoundHttpException();
        }

        $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::REGULAR);
        $Customer->setStatus($CustomerStatus);
        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        log_info('本会員登録完了');

        $event = new EventArgs(
            [
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch($event, EccubeEvents::FRONT_ENTRY_ACTIVATE_COMPLETE);

        // メール送信
        $this->mailService->sendCustomerCompleteMail($Customer);

        // Assign session carts into customer carts
        $Carts = $this->cartService->getCarts();
        $qtyInCart = 0;
        foreach ($Carts as $Cart) {
            $qtyInCart += $Cart->getTotalQuantity();
        }

        if ($qtyInCart) {
            $this->cartService->save();
        }

        return $qtyInCart;
    }


/**
 * @Route("/entry/customer/image/process", name="customer_image_process", methods={"POST"})
 */
public function imageProcess(Request $request, LoggerInterface $logger)
{
    $logger->info('--- imageProcess START ---');

    // Debug toàn bộ request
    $logger->info('Request files:', $request->files->all());
    $logger->info('Request headers:', $request->headers->all());

    // Lấy file từ request
    // $image = $request->files->get('filepond');
    $image = $request->files->get('entry')['avatar_file'] ?? null;
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
     * アップロード画像を取得する際にコールされるメソッド.
     *
     * @see https://pqina.nl/filepond/docs/api/server/#load
     * @Route("/entry/customer/image/load", name="customer_image_load", methods={"GET"})
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
     * @Route("/entry/customer/image/revert", name="customer_image_revert", methods={"DELETE"})
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
