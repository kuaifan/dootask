<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenamePreReportReceivesReceiveTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('report_receives', function (Blueprint $table) {
            if (Schema::hasColumn('report_receives', 'receive_time')) {
                $table->renameColumn('receive_time', 'receive_at');
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
        Schema::table('report_receives', function (Blueprint $table) {
            //
        });
    }
}
