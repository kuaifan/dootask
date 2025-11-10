<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAppSortsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_app_sorts')) {
            return;
        }

        Schema::create('user_app_sorts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->unique()->comment('用户ID');
            $table->json('sorts')->nullable()->comment('排序配置');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_app_sorts');
    }
}
