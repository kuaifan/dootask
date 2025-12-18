<?php

use App\Module\Base;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

class UpdateSettingMicroappMenuType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $row = Setting::whereName('microapp_menu')->first();
        if (!$row) {
            return;
        }
        $data = Base::string2array($row->setting);
        if (empty($data) || !is_array($data)) {
            return;
        }

        $changed = false;
        foreach ($data as $appIndex => $app) {
            if (!is_array($app)) {
                continue;
            }
            $menuItems = [];
            if (isset($app['menu_items']) && is_array($app['menu_items'])) {
                $menuItems = $app['menu_items'];
            } elseif (isset($app['menu']) && is_array($app['menu'])) {
                $menuItems = [$app['menu']];
            }
            if (empty($menuItems)) {
                continue;
            }

            $newMenuItems = [];
            foreach ($menuItems as $menu) {
                if (!is_array($menu)) {
                    $newMenuItems[] = $menu;
                    continue;
                }
                if (!isset($menu['type']) && isset($menu['url_type'])) {
                    $menu['type'] = $menu['url_type'];
                    unset($menu['url_type']);
                    $changed = true;
                } elseif (isset($menu['url_type'])) {
                    unset($menu['url_type']);
                    $changed = true;
                }
                $newMenuItems[] = $menu;
            }

            if (isset($app['menu_items']) && is_array($app['menu_items'])) {
                $data[$appIndex]['menu_items'] = $newMenuItems;
            } elseif (isset($app['menu']) && is_array($app['menu'])) {
                $data[$appIndex]['menu'] = $newMenuItems[0] ?? $app['menu'];
            }
        }

        if ($changed) {
            $row->updateInstance(['setting' => $data]);
            $row->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No-op: do not revert settings payload.
    }
}
