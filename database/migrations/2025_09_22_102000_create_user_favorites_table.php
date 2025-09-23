<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserFavoritesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_favorites'))
            return;

        Schema::create('user_favorites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->index()->nullable()->default(0)->comment('用户ID');
            $table->string('favoritable_type', 50)->index()->nullable()->default('')->comment('收藏类型(比如：task/project/file/message)');
            $table->bigInteger('favoritable_id')->index()->nullable()->default(0)->comment('收藏对象ID');
            $table->timestamps();

            // 复合索引：用户ID + 收藏类型（用于按类型获取收藏列表）
            $table->index(['userid', 'favoritable_type']);
            // 唯一索引：用户ID + 收藏类型 + 收藏对象ID（防止重复收藏）
            $table->unique(['userid', 'favoritable_type', 'favoritable_id'], 'user_favorites_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_favorites');
    }
}
