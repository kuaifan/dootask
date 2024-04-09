<?php

use App\Models\WebSocketDialogMsg;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMeetingMsgsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('meeting_msgs');
        Schema::create('meeting_msgs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('meetingid')->nullable()->default('')->comment('会议ID');
            $table->bigInteger('dialog_id')->nullable()->default(0)->comment('对话ID');
            $table->bigInteger('msg_id')->nullable()->default(0)->comment('消息ID');
        });
        \DB::table('meetings')->update(['end_at' => null]);
        WebSocketDialogMsg::whereType('meeting')->chunk(100, function ($msgs) {
            /** @var WebSocketDialogMsg $msg */
            foreach ($msgs as $msg) {
                $meetingid = $msg->msg['meetingid'];
                $dialog_id = $msg->dialog_id;
                $msg_id = $msg->id;
                \DB::table('meeting_msgs')->insert(compact('meetingid', 'dialog_id', 'msg_id'));
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
        Schema::dropIfExists('meeting_msgs');
    }
}
