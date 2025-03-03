<?php

namespace Plugin\PluginHoliday;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'holiday' => [
                'name' => 'Holiday',
                'icon' => 'fa-calendar',
                'children' => [
                    'holiday_create' => [
                        'name' => 'plugin_holiday.admin.create',
                        'url' => 'plugin_holiday_admin_create_holiday',
                    ],
                    'holiday_list' => [
                        'name' => 'plugin_holiday.admin.holiday_list',
                        'url' => 'plugin_holiday_admin_list',
                    ],
                    'plugin_holiday.admin.config' => [
                        'name' => 'plugin_holiday.admin.config',
                        'url' => 'plugin_holiday_admin_config',
                    ],
                ],
            ],
        ];
    }
}
