<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectLogsAddRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('project_logs', 'record')) {
                $table->text('record')->nullable()->after('detail')->comment('记录数据');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_logs', function (Blueprint $table) {
            $table->dropColumn("record");
        });
    }
}
