<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTmpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tmps', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 500)->nullable()->index('pre_tmp_title_index');
            $table->string('value')->nullable();
            $table->longText('content')->nullable();
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
        Schema::dropIfExists('tmps');
    }
}
