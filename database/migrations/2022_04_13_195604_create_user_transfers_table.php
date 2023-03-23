<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_transfers', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('original_userid')->nullable()->default(0)->comment('原作者');
            $table->bigInteger('new_userid')->nullable()->default(0)->comment('交接人');
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
        Schema::dropIfExists('user_transfers');
    }
}
