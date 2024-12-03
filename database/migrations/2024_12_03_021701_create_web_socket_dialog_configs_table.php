<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebSocketDialogConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('web_socket_dialog_configs')) {
            Schema::create('web_socket_dialog_configs', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('dialog_id')->unsigned()->index()->comment('对话ID');
                $table->bigInteger('userid')->unsigned()->index()->comment('用户ID');
                $table->string('type', 50)->default('')->comment('配置类型');
                $table->text('value')->nullable()->comment('配置值');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('web_socket_dialog_configs');
    }
}
