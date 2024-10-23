<?php

use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogMsgRead;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebSocketDialogMsgReadsLive extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        info("update web_socket_dialog_msg_reads live field");
        $isAdd = false;
        Schema::table('web_socket_dialog_msg_reads', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('web_socket_dialog_msg_reads', 'live')) {
                $isAdd = true;
                $table->integer('live')->nullable()->default(0)->index()->after('dot')->comment('是否在会话里');
                $table->index(['userid', 'live', 'msg_id']);
            }
        });
        info("update web_socket_dialog_msgs deleted_at");
        Schema::table('web_socket_dialog_msgs', function (Blueprint $table) {
            $table->index('deleted_at');
        });
        if ($isAdd) {
            // 关键词包含
            $contains = [
                '您可以通过发送以下命令来控制我',
                '我的机器人。',
                '机器人名称：',
                '已加入的会话：',
                '你可以通过执行以下命令来请求我:',
                '假期类型：',
                '评论了此审批',
                '我是你的机器人助理',
                '打卡时间: ',
                '匿名消息使用说明',
                '匿名消息将通过',
                '导出任务统计已完成',
                '我不是你的机器人',
                '机器人名称由',
                '您没有创建机器人',
                '缺卡提醒：',
                '打卡提醒：',
                '文件下载打包已完成',
                '您有一个新任务 #',
                '您协助的任务 #',
                '您负责的任务 #',
            ];
            info("update web_socket_dialog_msgs key contains");
            foreach ($contains as $key) {
                WebSocketDialogMsg::whereType('text')->where('key', 'like', "%{$key}%")->update(['key' => '']);
            }

            // 关键词开始以
            $starts = [
                '/hello',
                '/help',
                '/list',
                '/info',
                '/newbot',
                '/setname',
                '/deletebot',
                '/token',
                '/revoke',
                '/webhook',
                '/clearday',
                '/dialog',
                '/api',
            ];
            info("update web_socket_dialog_msgs key starts");
            foreach ($starts as $key) {
                WebSocketDialogMsg::whereType('text')->where('key', 'like', "{$key}%")->update(['key' => '']);
            }

            // 关键词等于
            $equals = [
                '我要打卡',
                'IT资讯',
                '36氪',
                '60s读世界',
                '开心笑话',
                '心灵鸡汤',
                '使用说明',
                '隐私说明',
                '帮助指令',
                'API接口文档',
                '我的机器人',
                '清空上下文',
                '操作频繁！',
                '暂未开启签到功能。',
                '暂未开放手动签到。',
                '设置成功',
                '机器人不存在。',
            ];
            info("update web_socket_dialog_msgs key equals");
            foreach ($equals as $key) {
                WebSocketDialogMsg::whereType('text')->whereKey($key)->update(['key' => '']);
            }

            // 更新是否在会话里面
            info("update web_socket_dialog_msg_reads live value");
            WebSocketDialog::chunk(100, function ($dialogs) {
                /** @var WebSocketDialog $dialog */
                foreach ($dialogs as $dialog) {
                    WebSocketDialogMsgRead::whereDialogId($dialog->id)->whereIn('userid', function ($query) use ($dialog) {
                        $query->select('userid')->from('web_socket_dialog_users')->whereDialogId($dialog->id);
                    })->update(['live' => 1]);
                }
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
        //
    }
}
