<?php

namespace App\Tasks;

use App\Models\Meeting;
use App\Models\WebSocketDialog;
use App\Module\Base;
use Carbon\Carbon;
use App\Models\WebSocketDialogMsg;
use Illuminate\Support\Facades\Cache;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class CloseMeetingRoomTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        // 10分钟执行一次
        $time = intval(Cache::get("CloseMeetingRoomTask:Time"));
        if (time() - $time < 600) {
            return;
        }
        Cache::put("CloseMeetingRoomTask:Time", time(), Carbon::now()->addMinutes(10));
        // 判断参数
        $setting = Base::setting('meetingSetting');
        if ($setting['open'] !== 'open') {
            return;
        }
        if (empty($setting['appid']) ||empty($setting['api_key']) || empty($setting['api_secret'])) {
            return;
        }
        $credentials = $setting['api_key'] . ":" . $setting['api_secret'];
        $base64Credentials = base64_encode($credentials);
        $arrHeader = [
            "Accept: application/json",
            "Authorization: Basic " . $base64Credentials
        ];
        // 获取10分钟未更新的会议
        $meetings = Meeting::whereNull('end_at')
            ->where('updated_at', '<', Carbon::now()->subMinutes(10))
            ->take(100)
            ->get();
        $dialogIds = [];
        /** @var Meeting $meeting */
        foreach ($meetings as $meeting) {
            if (!$this->isEmptyChannel($setting['appid'], $meeting->channel, $arrHeader)) {
                $meeting->updated_at = Carbon::now();
                $meeting->save();
                continue;
            }
            $meeting->end_at = Carbon::now();
            $meeting->save();
            // 更新消息
            $newMsg = $meeting->toArray();
            $newMsg['end_at'] = $meeting->end_at->toDateTimeString();
            WebSocketDialogMsg::select(['web_socket_dialog_msgs.*', 'm.meetingid'])
                ->join("meeting_msgs as m", "m.msg_id", "=", "web_socket_dialog_msgs.id")
                ->where('m.meetingid', $meeting->meetingid)
                ->chunk(100, function ($msgs) use ($newMsg, &$dialogIds) {
                    /** @var WebSocketDialogMsg $msg */
                    foreach ($msgs as $msg) {
                        $msgData = Base::json2array($msg->getRawOriginal('msg'));
                        $msg->msg = Base::array2json(array_merge($msgData, $newMsg));
                        $msg->save();
                        //
                        if (!isset($dialogIds[$msg->dialog_id])) {
                            $dialogIds[$msg->dialog_id] = [];
                        }
                        $dialogIds[$msg->dialog_id][] = [
                            'id' => $msg->id,
                            'msg' => $msg->msg,
                        ];
                    }
                });
        }
        // 推送更新
        foreach ($dialogIds as $dialogId => $datas) {
            $dialog = WebSocketDialog::find($dialogId);
            if (empty($dialog)) {
                continue;
            }
            foreach ($datas as $data) {
                $dialog->pushMsg('update', $data);
            }
        }
    }

    public function end()
    {
    }

    /**
     * 是否空频道
     * @param $appid
     * @param $channel
     * @param $arrHeader
     * @return bool
     */
    private function isEmptyChannel($appid, $channel, $arrHeader)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.sd-rtn.com/dev/v1/channel/user/{$appid}/{$channel}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => $arrHeader,
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;   // 错误
        }
        $data = Base::json2array($response);
        if (!$data['success']) {
            return false;   // 失败
        }
        if ($data['data']['channel_exist']) {
            return false;   // 有人
        }
        return true;
    }
}
