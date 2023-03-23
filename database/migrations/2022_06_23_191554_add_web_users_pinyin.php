<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebUsersPinyin extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('users', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('users', 'pinyin')) {
                $isAdd = true;
                $table->string('pinyin', 255)->nullable()->default('')->after('az')->comment('拼音（主要用于搜索）');
            }
        });
        if ($isAdd) {
            \App\Models\User::chunkById(100, function ($lists) {
                /** @var \App\Models\User $item */
                foreach ($lists as $item) {
                    $item->pinyin = \App\Module\Base::cn2pinyin($item->nickname);
                    $item->save();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn("pinyin");
        });
    }
}
