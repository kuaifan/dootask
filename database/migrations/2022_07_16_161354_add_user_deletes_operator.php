<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserDeletesOperator extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_deletes', function (Blueprint $table) {
            if (!Schema::hasColumn('user_deletes', 'operator')) {
                $table->bigInteger('operator')->nullable()->default(0)->after('id')->comment('操作人员');
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
        Schema::table('user_deletes', function (Blueprint $table) {
            $table->dropColumn("operator");
        });
    }
}
