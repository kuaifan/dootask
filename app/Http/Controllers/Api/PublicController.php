<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserCheckinMac;
use App\Models\UserCheckinRecord;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\Base;
use Cache;
use Carbon\Carbon;
use Request;

/**
 * @apiDefine public
 *
 * 公开
 */
class PublicController extends AbstractController
{
    /**
     * 签到 - 路由器（openwrt）功能安装脚本
     *
     * @apiParam {String}   key
     *
     * @return string
     */
    public function checkin__install()
    {
        $key = trim(Request::input('key'));
        //
        $setting = Base::setting('checkinSetting');
        if ($setting['open'] !== 'open') {
            return <<<EOF
                #!/bin/sh
                echo "function off"
                EOF;
        }
        if ($key != $setting['key']) {
            return <<<EOF
                #!/bin/sh
                echo "key error"
                EOF;
        }
        //
        $reportUrl = Base::fillUrl("api/public/checkin/report");
        return <<<EOE
            #!/bin/sh
            echo 'installing...'

            cat > /etc/init.d/dootask-checkin-report <<EOF
            #!/bin/sh
            mac=\\\$(awk 'NR!=1&&\\\$3=="0x2" {print \\\$4}' /proc/net/arp | tr "\\n" ",")
            tmp='{"key":"{$setting['key']}","mac":"'\\\${mac}'","time":"'\\\$(date +%s)'"}'
            curl -4 -X POST "{$reportUrl}" -H "Content-Type: application/json" -d \\\${tmp}
            EOF

            chmod +x /etc/init.d/dootask-checkin-report
            crontab -l >/tmp/cronbak
            sed -i '/\/etc\/init.d\/dootask-checkin-report/d' /tmp/cronbak
            sed -i '/^$/d' /tmp/cronbak
            echo "* * * * * sh /etc/init.d/dootask-checkin-report" >>/tmp/cronbak
            crontab /tmp/cronbak
            rm -f /tmp/cronbak
            /etc/init.d/cron enable
            /etc/init.d/cron restart

            echo 'installed'
            EOE;
    }

    /**
     * {post} 签到 - 路由器（openwrt）上报
     *
     * @apiParam {String}   key
     * @apiParam {String}   mac     使用逗号分割多个
     * @apiParam {String}   time
     *
     * @return string
     */
    public function checkin__report()
    {
        $key = trim(Request::input('key'));
        $mac = trim(Request::input('mac'));
        $time = intval(Request::input('time'));
        //
        $setting = Base::setting('checkinSetting');
        if ($setting['open'] !== 'open') {
            return 'function off';
        }
        if ($key != $setting['key']) {
            return 'key error';
        }
        $times = $setting['time'] ? Base::json2array($setting['time']) : ['09:00', '18:00'];
        $advance = (intval($setting['advance']) ?: 120) * 60;
        $delay = (intval($setting['delay']) ?: 120) * 60;
        //
        $nowDate = date("Y-m-d");
        $nowTime = date("H:i:s");
        //
        $timeStart = strtotime("{$nowDate} {$times[0]}");
        $timeEnd = strtotime("{$nowDate} {$times[1]}");
        $timeAdvance = max($timeStart - $advance, strtotime($nowDate));
        $timeDelay = min($timeEnd + $delay, strtotime("{$nowDate} 23:59:59"));
        if (Base::time() < $timeAdvance || $timeDelay < Base::time()) {
            return "not in valid time, valid time is " . date("H:i", $timeAdvance) . "-" . date("H:i", $timeDelay);
        }
        //
        $macs = explode(",", $mac);
        $checkins = [];
        foreach ($macs as $mac) {
            $mac = strtoupper($mac);
            if (Base::isMac($mac) &&  $UserCheckinMac = UserCheckinMac::whereMac($mac)->first()) {
                $checkins[] = $UserCheckinMac;
                $array = [
                    'userid' => $UserCheckinMac->userid,
                    'mac' => $UserCheckinMac->mac,
                    'date' => $nowDate,
                ];
                $record = UserCheckinRecord::where($array)->first();
                if (empty($record)) {
                    $record = UserCheckinRecord::createInstance($array);
                }
                $record->times = Base::array2json(array_merge($record->times, [$nowTime]));
                $record->report_time = $time;
                $record->save();
            }
        }
        //
        if ($checkins && $botUser = User::botGetOrCreate('check-in')) {
            $getJokeSoup = function($type) {
                $pre = $type == "up" ? "每日开心：" : "心灵鸡汤：";
                $key = $type == "up" ? "JokeSoupTask:jokes" : "JokeSoupTask:soups";
                $array = Base::json2array(Cache::get($key));
                if ($array) {
                    $item = $array[array_rand($array)];
                    if ($item) {
                        return $pre . $item;
                    }
                }
                return null;
            };
            $sendMsg = function($type, UserCheckinMac $checkin) use ($getJokeSoup, $botUser, $nowDate) {
                $cacheKey = "Checkin::sendMsg-{$nowDate}-{$type}:" . $checkin->userid;
                if (Cache::get($cacheKey) === "yes") {
                    return;
                }
                Cache::put($cacheKey, "yes", Carbon::now()->addDay());
                //
                $dialog = WebSocketDialog::checkUserDialog($botUser, $checkin->userid);
                if ($dialog) {
                    $hi = date("H:i");
                    $pre = $type == "up" ? "上班" : "下班";
                    $remark = $checkin->remark ? " ({$checkin->remark})": "";
                    $text = "<p>{$pre}打卡成功，打卡时间: {$hi}{$remark}</p>";
                    $suff = $getJokeSoup($type);
                    if ($suff) {
                        $text = "{$text}<p>----------</p><p>{$suff}</p>";
                    }
                    WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid);
                }
            };
            if ($timeAdvance <= Base::time() && Base::time() < $timeEnd) {
                // 上班打卡通知（从最早打卡时间 到 下班打卡时间）
                foreach ($checkins as $checkin) {
                    $sendMsg('up', $checkin);
                }
            }
            if ($timeEnd <= Base::time() && Base::time() <= $timeDelay) {
                // 下班打卡通知（下班打卡时间 到 最晚打卡时间）
                foreach ($checkins as $checkin) {
                    $sendMsg('down', $checkin);
                }
            }
        }
        return 'success';
    }
}
