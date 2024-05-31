<?php
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Module\Base;
use Illuminate\Database\Migrations\Migration;

class AllGroupMuteHandle extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $systemConfig = Base::setting('system');
        if ($systemConfig['all_group_mute'] == 'user' || $systemConfig['all_group_mute'] == 'all') {
            $systemConfig['all_group_mute'] = 'close';
            Base::setting('system', $systemConfig);
        }
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
