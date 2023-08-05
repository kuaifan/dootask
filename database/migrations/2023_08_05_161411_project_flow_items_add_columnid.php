<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectFlowItemsAddColumnid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_flow_items', function (Blueprint $table) {
            if (!Schema::hasColumn('project_flow_items', 'columnid')) {
                $table->bigInteger('columnid')->nullable()->default(0)->after('userlimit')->comment('对应的项目列表');
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
        Schema::table('project_flow_items', function (Blueprint $table) {
            $table->dropColumn("columnid");
        });
    }
}
