<?php

namespace Plugin\PluginHoliday\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\PluginHoliday\Entity\Holiday;
use Plugin\PluginHoliday\Form\Type\Admin\ConfigType;
use Plugin\PluginHoliday\Form\Type\Admin\HolidayType;
use Plugin\PluginHoliday\Form\Type\Admin\SearchHolidayType;
use Plugin\PluginHoliday\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\PluginHoliday\Repository\HolidayRepository;
use Knp\Component\Pager\PaginatorInterface;


class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var HolidayRepository
     */
    protected $holidayRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository, HolidayRepository $holidayRepository)
    {
        $this->configRepository = $configRepository;
        $this->holidayRepository = $holidayRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/plugin_holiday/config", name="plugin_holiday_admin_config")
     * @Template("@PluginHoliday/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush();
            $this->addSuccess('登録しました。', 'admin');

            return $this->redirectToRoute('plugin_holiday_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * Hàm xử lý thêm mới và chỉnh sửa ngày lễ
     * @Route("/%eccube_admin_route%/plugin_holiday/create_holiday", name="plugin_holiday_admin_create_holiday")
     * @Route("/%eccube_admin_route%/plugin_holiday/{id}/edit_holiday", name="plugin_holiday_admin_edit_holiday", requirements={"id" = "\d+"})
     * @Template("@PluginHoliday/admin/holiday_add.twig")
     */
    public function createHoliday(Request $request, $id = null)
    {
        // Nếu có $id, lấy bản ghi hiện có; nếu không, tạo mới
        if ($id) {
            $Holiday = $this->holidayRepository->find($id);
            if (!$Holiday) {
                throw $this->createNotFoundException('Ngày lễ không tồn tại.');
            }
        } else {
            $Holiday = new Holiday();
        }
        
        // Tạo form dựa trên HolidayType
        $form = $this->createForm(HolidayType::class, $Holiday);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Holiday = $form->getData();

            $date = $form->get('holiday_date')->getData();
            if ($date instanceof \DateTime) {
                $currentYear = (new \DateTime())->format('Y');
                $date->setDate($currentYear, $date->format('m'), $date->format('d'));
                $Holiday->setHolidayDate($date);
            }

            $this->entityManager->persist($Holiday);
            $this->entityManager->flush();

            $message = $id ? '日祝を更新しました。' : '日祝を登録しました。';
            $this->addSuccess($message, 'admin');

            return $this->redirectToRoute('plugin_holiday_admin_create_holiday');
        }

        return [
            'form' => $form->createView(),
            'holiday' => $Holiday,
        ];
    }

    /**
     * Danh sách ngày lễ với tìm kiếm và phân trang
     * @Route("/%eccube_admin_route%/plugin_holiday/list", name="plugin_holiday_admin_list")
     * @Route("/%eccube_admin_route%/plugin_holiday/list/page/{page_no}", requirements={"page_no" = "\d+"}, name="plugin_holiday_admin_list_page")
     * @Template("@PluginHoliday/admin/holiday_list.twig")
     */
    public function listHoliday(Request $request, PaginatorInterface $paginator, $page_no = null)
    {
        $session = $this->session;
        $builder = $this->formFactory->createBuilder(SearchHolidayType::class);
        $searchForm = $builder->getForm();

        $pageMaxis = $this->entityManager->getRepository('Eccube\Entity\Master\PageMax')->findAll();
        $pageCount = $session->get('plugin_holiday.admin.search.page_count', $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('plugin_holiday.admin.search.page_count', $pageCount);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();

                if(!is_array($searchData)) {
                    $searchData = [
                        'name' => $searchData->getName(),
                        'holiday_message' => $searchData->getHolidayMessage(),
                        'holiday_date' => $searchData->getHolidayDate(),
                    ];
                }
                $page_no = 1;

                $session->set('plugin_holiday.admin.search', $searchForm->getData());
                $session->set('plugin_holiday.admin.search.page_no', $page_no);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                if ($page_no) {
                    $session->set('plugin_holiday.admin.search.page_no', (int) $page_no);
                } else {
                    $page_no = $session->get('plugin_holiday.admin.search.page_no', 1);
                }
                $viewData = $session->get('plugin_holiday.admin.search', []);
            } else {
                $page_no = 1;
                $viewData = [];
                $session->set('plugin_holiday.admin.search', $viewData);
                $session->set('plugin_holiday.admin.search.page_no', $page_no);
            }
            $searchData = $viewData;
            $searchForm->submit($searchData);
        }

        $qb = $this->holidayRepository->getQueryBuilderBySearchData($searchData);

        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $page_no,
            $pageCount
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $pageCount,
            'has_errors' => false,
        ];
    }
    /**
     * controller xóa ngày lễ
     * @Route("/%eccube_admin_route%/plugin_holiday/{id}/delete_holiday", name="plugin_holiday_admin_delete_holiday", requirements={"id" = "\d+"})
     */
    public function deleteHoliday(Request $request, $id)
    {
        $Holiday = $this->holidayRepository->find($id);
        if (!$Holiday) {
            throw $this->createNotFoundException('Ngày lễ không tồn tại.');
        }

        $this->entityManager->remove($Holiday);
        $this->entityManager->flush();

        $this->addSuccess('日祝を削除しました。', 'admin');

        return $this->redirectToRoute('plugin_holiday_admin_list');
    }
    /** 
     * Controller cho block holiday_message
     * @Route("/block_holiday_message", name="block_holiday_message")
     * @Template("@PluginHoliday/default/holiday_message.twig")
     */
    public function holidayMessageBlock()
    {
        $today = new \DateTime();

        $holiday = $this->holidayRepository->findOneBy(['holiday_date' => $today]);

        $holidayName = null;
        $holidayMessage = null;

        if ($holiday) {
            $holidayName = $holiday->getName();
            $holidayMessage = $holiday->getHolidayMessage();
        }

        return [
            'is_holiday' => !is_null($holiday), 
            'holiday_message' => $holidayMessage, 
            'holiday_name' => $holidayName 
        ]; 
    }
}