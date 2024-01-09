<?php

namespace App\Http\Controllers\Api;

use App\Models\UserBot;
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
        if (!in_array('auto', $setting['modes'])) {
            return 'mode off';
        }
        if ($key != $setting['key']) {
            return 'key error';
        }
        if ($error = UserBot::checkinBotCheckin($mac, $time)) {
            return $error;
        }
        return 'success';
    }
}
