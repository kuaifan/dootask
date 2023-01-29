<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserCheckinMac;
use App\Models\UserCheckinRecord;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\Base;
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
        $setting['time'] = $setting['time'] ? Base::json2array($setting['time']) : ['00:00', '23:59'];
        //
        $nowDate = date("Y-m-d");
        $nowTime = date("H:i:s");
        //
        $timeStart = strtotime(date("{$nowDate} {$setting['time'][0]}"));
        $timeEnd = strtotime(date("{$nowDate} {$setting['time'][1]}"));
        if (Base::time() < $timeStart || Base::time() > $timeEnd) {
            return "not in valid time, valid time is {$setting['time'][0]}-{$setting['time'][1]}";
        }
        //
        $macs = explode(",", $mac);
        $checkins = [];
        foreach ($macs as $mac) {
            $mac = strtoupper($mac);
            if (Base::isMac($mac) &&  $UserCheckinMac = UserCheckinMac::whereMac($mac)->first()) {
                $array = [
                    'userid' => $UserCheckinMac->userid,
                    'mac' => $UserCheckinMac->mac,
                    'date' => $nowDate,
                ];
                $record = UserCheckinRecord::where($array)->first();
                if (empty($record)) {
                    $record = UserCheckinRecord::createInstance($array);
                    $checkins[] = $UserCheckinMac;
                }
                $record->times = Base::array2json(array_merge($record->times, [$nowTime]));
                $record->report_time = $time;
                $record->save();

            }
        }
        //
        if ($checkins && $setting['notice'] === 'open') {
            $botUser = User::botGetOrCreate('check-in');
            if ($botUser) {
                foreach ($checkins as $checkin) {
                    $dialog = WebSocketDialog::checkUserDialog($botUser->userid, $checkin->userid);
                    if ($dialog) {
                        $hi = date("H:i");
                        $remark = $checkin->remark ?: $checkin->mac;
                        $text = "签到成功，签到时间: {$hi} ({$remark})";
                        WebSocketDialogMsg::sendMsg(null, $dialog->id, 'text', ['text' => $text], $botUser->userid);    // todo 未能在任务end事件来发送任务
                    }
                }
            }
        }
        return 'success';
    }
}
