<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\EvenListener;

use Detection\MobileDetect;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Member;
use Eccube\Repository\AuthorityRoleRepository;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\BlockPositionRepository;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PageRepository;
use Eccube\Request\Context;
use Eccube\Service\SystemService;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Eccube\EventListener\TwigInitializeListener as BaseTwigInitializeListener;

class TwigInitializeListener extends BaseTwigInitializeListener
    
{
        /**
     * @var bool 初期化済かどうか.
     */
    protected $initialized = false;

    /**
     * @var Environment
     */

    protected $twig;
        /**
     * @var BaseInfoRepository
     */
    protected $baseInfoRepository;

    /**
     * @var DeviceTypeRepository
     */
    protected $deviceTypeRepository;


    /**
     * @var PageLayoutRepository
     */
    protected $pageLayoutRepository;

    /**
     * @var BlockPositionRepository
     */
    protected $blockPositionRepository;

    /**
     * @var Context
     */
    protected $requestContext;

    /**
     * @var AuthorityRoleRepository
     */
    private $authorityRoleRepository;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var MobileDetect
     */
    private $mobileDetector;

    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var LayoutRepository
     */
    private $layoutRepository;

    /**
     * @var SystemService
     */
    protected $systemService;

    /**
     * TwigInitializeListener constructor.
     */
    public function __construct(
        Environment $twig,
        BaseInfoRepository $baseInfoRepository,
        PageRepository $pageRepository,
        PageLayoutRepository $pageLayoutRepository,
        BlockPositionRepository $blockPositionRepository,
        DeviceTypeRepository $deviceTypeRepository,
        AuthorityRoleRepository $authorityRoleRepository,
        EccubeConfig $eccubeConfig,
        Context $context,
        MobileDetect $mobileDetector,
        UrlGeneratorInterface $router,
        LayoutRepository $layoutRepository,
        SystemService $systemService
    ) {
        parent::__construct($twig, $baseInfoRepository, $pageRepository, $pageLayoutRepository, $blockPositionRepository, $deviceTypeRepository, $authorityRoleRepository, $eccubeConfig, $context, $mobileDetector, $router, $layoutRepository, $systemService);
        $this->twig = $twig;
        $this->pageLayoutRepository = $pageLayoutRepository;
        $this->baseInfoRepository = $baseInfoRepository;
        $this->blockPositionRepository = $blockPositionRepository;
        $this->deviceTypeRepository = $deviceTypeRepository;
        $this->authorityRoleRepository = $authorityRoleRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->requestContext = $context;
        $this->router = $router;
        $this->layoutRepository = $layoutRepository;
        $this->systemService = $systemService;
    }
    public function setAdminGlobals(RequestEvent $event)
    {
        // メニュー表示用配列.
        parent::setAdminGlobals($event);
        $menus = [];
        $this->twig->addGlobal('menus', $menus);

        // メニューの権限制御.
        $eccubeNav = $this->eccubeConfig['eccube_nav'];

        $Member = $this->requestContext->getCurrentUser();
        if ($Member instanceof Member) {
            $AuthorityRoles = $this->authorityRoleRepository->findBy(['Authority' => $Member->getAuthority()]);
            $baseUrl = $event->getRequest()->getBaseUrl().'/'.$this->eccubeConfig['eccube_admin_route'];
            $eccubeNav = $this->getDisplayEccubeNav($eccubeNav, $AuthorityRoles, $baseUrl);
        }
        $isWarehouse = $Member && method_exists($Member, 'getIsWarehouse') && $Member->getIsWarehouse();
        if ($isWarehouse) {
            $eccubeNav = array_filter($eccubeNav, function ($nav) {
                return isset($nav['name']) && $nav['name'] === 'admin.product.product_management';
            });

            foreach ($eccubeNav as &$menu) {
                if (isset($menu['children'])) {
                    $menu['children'] = array_filter($menu['children'], function ($child) {
                        return in_array($child['name'], [
                            'admin.product.product_list',
                        ]);
                    });
                }
            }
        }
        
        $this->twig->addGlobal('eccubeNav', $eccubeNav);
        $this->twig->addGlobal('isMaintenance', $this->systemService->isMaintenanceMode());
        $this->twig->addGlobal('isDebugMode', env('APP_DEBUG'));

    }
     /**
     * URLに対する権限有無チェックして表示するNavを返す
     *
     * @param array $parentNav
     * @param AuthorityRole[] $AuthorityRoles
     * @param string $baseUrl
     *
     * @return array
     */
    private function getDisplayEccubeNav($parentNav, $AuthorityRoles, $baseUrl)
    {
        $restrictUrls = $this->eccubeConfig['eccube_restrict_file_upload_urls'];

        foreach ($parentNav as $key => $childNav) {
            if (array_key_exists('children', $childNav) && count($childNav['children']) > 0) {
                // 子のメニューがある場合は子の権限チェック
                $parentNav[$key]['children'] = $this->getDisplayEccubeNav($childNav['children'], $AuthorityRoles, $baseUrl);

                if (count($parentNav[$key]['children']) <= 0) {
                    // 子が存在しない場合は配列から削除
                    unset($parentNav[$key]);
                }
            } elseif (array_key_exists('url', $childNav)) {
                // 子のメニューがなく、URLが設定されている場合は権限があるURLか確認
                $param = array_key_exists('param', $childNav) ? $childNav['param'] : [];
                $url = $this->router->generate($childNav['url'], $param);
                foreach ($AuthorityRoles as $AuthorityRole) {
                    $denyUrl = str_replace('/', '\/', $baseUrl.$AuthorityRole->getDenyUrl());
                    if (preg_match("/^({$denyUrl})/i", $url)) {
                        // 権限がないURLの場合は配列から削除
                        unset($parentNav[$key]);
                        break;
                    }
                }

                if ($this->eccubeConfig['eccube_restrict_file_upload'] === '1' && in_array($childNav['url'], $restrictUrls)) {
                    unset($parentNav[$key]);
                }
            }
        }

        return $parentNav;
    }
}
