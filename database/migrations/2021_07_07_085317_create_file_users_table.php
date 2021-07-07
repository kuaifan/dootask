<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('file_id')->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('userid')->nullable()->default(0)->comment('成员ID');
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
        Schema::dropIfExists('file_users');
    }
}
