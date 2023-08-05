<?php

use App\Module\Base;
use Illuminate\Database\Migrations\Migration;

class SettingCheckinModesValue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $setting = Base::setting('checkinSetting');
        $setting['modes'] = ['auto'];
        Base::setting('checkinSetting', $setting);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
