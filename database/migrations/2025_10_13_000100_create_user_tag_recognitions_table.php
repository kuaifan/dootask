<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTagRecognitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tag_recognitions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tag_id')->index()->comment('标签ID');
            $table->unsignedBigInteger('user_id')->index()->comment('认可人ID');
            $table->timestamps();

            $table->unique(['tag_id', 'user_id'], 'user_tag_recognitions_unique');
            $table->foreign('tag_id')->references('id')->on('user_tags')->onDelete('cascade');
            $table->foreign('user_id')->references('userid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_tag_recognitions');
    }
}
