<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateWebSocketDialogsGroupName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_socket_dialogs', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->default('')->comment('对话名称')->change();
        });
        //
        \App\Models\WebSocketDialog::whereGroupType('project')
            ->chunk(100, function ($lists) {
                /** @var \App\Models\WebSocketDialog $item */
                foreach ($lists as $item) {
                    $item->name = \App\Models\Project::whereDialogId($item->id)->first()?->name;
                    $item->save();
                }
            });
        \App\Models\WebSocketDialog::whereGroupType('task')
            ->chunk(100, function ($lists) {
                /** @var \App\Models\WebSocketDialog $item */
                foreach ($lists as $item) {
                    $item->name = \App\Models\ProjectTask::whereDialogId($item->id)->first()?->name;
                    $item->save();
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

    }
}
