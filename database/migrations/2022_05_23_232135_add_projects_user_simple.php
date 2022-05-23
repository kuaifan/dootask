<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectsUserSimple extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('projects', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('projects', 'user_simple')) {
                $isAdd = true;
                $table->string('user_simple', 255)->nullable()->default('')->after('personal')->comment('成员总数|1,2,3');
            }
        });
        if ($isAdd) {
            \App\Models\Project::chunkById(100, function ($lists) {
                /** @var \App\Models\Project $item */
                foreach ($lists as $item) {
                    $array = $item->projectUser->pluck('userid')->toArray();
                    $item->user_simple = count($array) . "|" . implode(",", array_slice($array, 0, 3));
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
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn("user_simple");
        });
    }
}
