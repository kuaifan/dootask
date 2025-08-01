<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColorToProjectFlowItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_flow_items', function (Blueprint $table) {
            if (!Schema::hasColumn('project_flow_items', 'color')) {
                $table->string('color', 20)->nullable()->default('')->after('status')->comment('自定义颜色');
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
            $table->dropColumn('color');
        });
    }
}
