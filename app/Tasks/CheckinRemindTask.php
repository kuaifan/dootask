<?php

namespace App\Tasks;

use App\Models\User;
use App\Models\UserCheckinRecord;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\Base;
use App\Module\Extranet;
use Cache;
use Carbon\Carbon;

@error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

class CheckinRemindTask extends AbstractTask
{
    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        $setting = Base::setting('checkinSetting');
        if ($setting['open'] !== 'open') {
            return;
        }
        // 判断非工作日
        if (Extranet::isHoliday(date("Ymd")) > 0) {
            return;
        }
        //
        $times = $setting['time'] ? Base::json2array($setting['time']) : ['09:00', '18:00'];
        $remindin = (intval($setting['remindin']) ?: 5) * 60;
        $remindexceed = (intval($setting['remindexceed']) ?: 10) * 60;
        //
        $nowDate = date("Y-m-d");
        $timeStart = strtotime("{$nowDate} {$times[0]}");
        //
        if ($remindin > 0) {
            $timeRemindin = $timeStart - $remindin;
            if ($timeRemindin <= Base::time() && Base::time() <= $timeStart) {
                // 签到打卡提醒
                $this->remind('in');
            }
        }
        if ($remindexceed > 0) {
            $timeRemindexceed = $timeStart + $remindexceed;
            if ($timeRemindexceed <= Base::time() && Base::time() <= $timeRemindexceed + 300) {
                // 签到缺卡提醒
                $this->remind('exceed');
            }
        }
    }

    public function end()
    {

    }

    private function remind($type)
    {
        if (Cache::get("CheckinRemindTask:remind-" . $type) == date("Ymd")) {
            return;
        }
        Cache::put("CheckinRemindTask:remind-" . $type, date("Ymd"), Carbon::now()->addDay());
        //
        $botUser = User::botGetOrCreate('check-in');
        if (!$botUser) {
            return;
        }
        // 提醒对象：3天内有签到的成员（在职）
        User::whereBot(0)->whereNull('disable_at')->chunk(100, function ($users) use ($type, $botUser) {
            /** @var User $user */
            foreach ($users as $user) {
                if (UserCheckinRecord::whereUserid($user->userid)->where('date', date("Y-m-d"))->exists()) {
                    continue;   // 已打卡
                }
                if (!UserCheckinRecord::whereUserid($user->userid)->where('created_at', '>', Carbon::now()->subDays(3))->exists()) {
                    continue;   // 3天内没有打卡
                }
                $dialog = WebSocketDialog::checkUserDialog($botUser, $user->userid);
                if ($dialog) {
                    if ($type === 'exceed') {
                        $text = "<p><strong style='color:red'>缺卡提醒：</strong>上班时间到了，你还没有打卡哦~</p>";
                    } else {
                        $text = "<p><strong>打卡提醒：</strong>快到上班时间了，别忘了打卡哦~</p>";
                    }
                    WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid);
                }
            }
        });
    }
}
