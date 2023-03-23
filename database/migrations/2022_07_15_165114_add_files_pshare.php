<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFilesPshare extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('files', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('files', 'pshare')) {
                $isAdd = true;
                $table->bigInteger('pshare')->nullable()->default(0)->after('share')->comment('所属分享ID');
            }
        });
        if ($isAdd) {
            \App\Models\File::whereShare(1)->chunkById(100, function ($lists) {
                /** @var \App\Models\File $item */
                foreach ($lists as $item) {
                    \App\Models\File::where("pids", "like", "%,{$item->id},%")->update(['pshare' => $item->id]);
                    $item->pshare = $item->id;
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
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn("pshare");
        });
    }
}
