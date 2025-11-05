<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tags', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index()->comment('被标签用户ID');
            $table->string('name', 50)->comment('标签名称');
            $table->unsignedBigInteger('created_by')->index()->comment('创建人');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('最后更新人');
            $table->timestamps();

            $table->unique(['user_id', 'name'], 'user_tags_unique_name');
            $table->foreign('user_id')->references('userid')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('userid')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('userid')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_tags');
    }
}
