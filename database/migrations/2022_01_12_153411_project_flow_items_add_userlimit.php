<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectFlowItemsAddUserlimit extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_flow_items', function (Blueprint $table) {
            if (!Schema::hasColumn('project_flow_items', 'userlimit')) {
                $table->tinyInteger('userlimit')->nullable()->default(0)->after('usertype')->comment('限制负责人');
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
            $table->dropColumn("userlimit");
        });
    }
}
