<?php
@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

use App\Module\Base;
use Illuminate\Database\Migrations\Migration;

class CreateAiSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $setting = Base::setting('aibotSetting');
        Base::setting('aiSetting', [
            'ai_provider' => 'openai',
            'ai_api_key' => $setting['openai_key'],
            'ai_api_url' => $setting['openai_base_url'],
            'ai_proxy' => $setting['openai_agency'],
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration does not need to be reversible
    }
}
