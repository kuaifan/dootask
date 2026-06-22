#!/bin/sh
# 在线授权续期：php 容器内的独立系统进程（supervisor [program:license] 托管）。
# 每小时直接跑一次 artisan CLI——不依赖 LARAVELS_TIMER、不经过 HTTP 转发。
# artisan 失败（如启动时 DB 未就绪）不会中断本循环，下个周期自动重试。
while true; do
    php /var/www/artisan online-license:renew
    sleep 3600
done
