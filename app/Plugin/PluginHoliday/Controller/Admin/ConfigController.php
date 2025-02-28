<?php

namespace Plugin\PluginHoliday\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\PluginHoliday\Entity\Holiday;
use Plugin\PluginHoliday\Form\Type\Admin\ConfigType;
use Plugin\PluginHoliday\Form\Type\Admin\HolidayType;
use Plugin\PluginHoliday\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\PluginHoliday\Repository\HolidayRepository;

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

        // Xử lý khi form được submit và hợp lệ
        if ($form->isSubmitted() && $form->isValid()) {
            $Holiday = $form->getData();

            // Gán năm mặc định cho holiday_date (vì form chỉ nhận ngày/tháng)
            $date = $form->get('holiday_date')->getData();
            if ($date instanceof \DateTime) {
                $currentYear = (new \DateTime())->format('Y');
                $date->setDate($currentYear, $date->format('m'), $date->format('d'));
                $Holiday->setHolidayDate($date);
            }

            $this->entityManager->persist($Holiday);
            $this->entityManager->flush();

            // Thông báo dựa trên hành động (tạo mới hay cập nhật)
            $message = $id ? '日祝を更新しました。' : '日祝を登録しました。';
            $this->addSuccess($message, 'admin');

            return $this->redirectToRoute('plugin_holiday_admin_create_holiday');
        }

        return [
            'form' => $form->createView(),
            'holiday' => $Holiday, // Truyền thêm đối tượng Holiday để phân biệt tạo mới hay chỉnh sửa
        ];
    }
}
