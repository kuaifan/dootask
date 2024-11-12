<?php

use App\Models\ProjectPermission;
use App\Module\Base;
use Illuminate\Database\Migrations\Migration;

class UpdateSettingEmailSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $setting = Base::setting('emailSetting');
        if (!isset($setting['msg_unread_time_ranges'])) {
            $setting['msg_unread_time_ranges'] = [
                ['00:00', '09:00'],
                ['18:00', '23:59'],
            ];
            Base::setting('emailSetting', $setting);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
