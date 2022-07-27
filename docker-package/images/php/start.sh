#!/bin/bash

CMD_NAME="dootask"
CMD_ECHO="\033[1;96m[${CMD_NAME}]\033[0m"

VOLUME_HOME=/volumes

function info() {
  echo -e "$(date "+%Y-%m-%d %H:%M:%S") ${CMD_ECHO} - INFO - $@"
}

function warn() {
  echo -e "$(date "+%Y-%m-%d %H:%M:%S") ${CMD_ECHO} - \033[33mWARN\033[0m - $@"
}

function echo_dirs() {
  echo "\033[4m$@\033[0m"
}

PHP_CONF_FILE="/usr/local/etc/php/php.ini"

if [[ ! -d "${VOLUME_HOME}/etc.d" ]]; then
  if mkdir -p ${VOLUME_HOME}/etc.d; then
    info "generate config director '\033[4m${VOLUME_HOME}/etc.d\033[0m'"
  fi
fi

if [[ ! -f "${VOLUME_HOME}/etc.d/php.ini" ]]; then
  if cp /usr/local/etc/php/php.ini-production "${VOLUME_HOME}/etc.d/php.ini"; then
    info "copy conf file '\033[4m/usr/local/etc/php/php.ini-production\033[0m' to '\033[4m${VOLUME_HOME}/etc.d/php.ini\033[0m' - [\033[32msuccess\033[0m]"

    if ln -sfT "${VOLUME_HOME}/etc.d/php.ini" "${PHP_CONF_FILE}"; then
      info "link conf file '\033[4m${VOLUME_HOME}/etc.d/php.ini\033[0m' to '\033[4m${PHP_CONF_FILE}\033[0m' - [\033[32msuccess\033[0m]"
    fi
  elif cp /usr/local/etc/php/php.ini-production "${PHP_CONF_FILE}"; then
    info "copy conf file '\033[4m/usr/local/etc/php/php.ini-production\033[0m' to '\033[4m${PHP_CONF_FILE}\033[0m' - [\033[32msuccess\033[0m]"
  fi
elif ln -sfT "${VOLUME_HOME}/etc.d/php.ini" "${PHP_CONF_FILE}"; then
  info "link conf file '\033[4m${VOLUME_HOME}/etc.d/php.ini\033[0m' to '\033[4m${PHP_CONF_FILE}\033[0m' - [\033[32msuccess\033[0m]"
fi

SUPERVISOR_PHP_FILE="/etc/supervisor/conf.d/php.conf"

if [[ ! -f "${VOLUME_HOME}/etc.d/php.conf" ]]; then
  if cat <<EOF > ${VOLUME_HOME}/etc.d/php.conf; then
[program:php]
directory=/var/www

# 生产环境
command=php bin/laravels start -i

# 开发环境
#command=./bin/inotify ./app

numprocs=1
autostart=true
autorestart=true
startretries=100
user=root
redirect_stderr=true
stdout_logfile=/var/log/supervisor/%(program_name)s.log
EOF
    info "generate supervisor file '\033[4m${VOLUME_HOME}/etc.d/php.conf\033[0m' - [\033[32msuccess\033[0m]"

    if ln -sfT "${VOLUME_HOME}/etc.d/php.conf" "${SUPERVISOR_PHP_FILE}"; then
      info "link conf file '\033[4m${VOLUME_HOME}/etc.d/php.conf\033[0m' to '\033[4m${SUPERVISOR_PHP_FILE}\033[0m' - [\033[32msuccess\033[0m]"
    fi
  fi
elif ln -sfT "${VOLUME_HOME}/etc.d/php.conf" "${SUPERVISOR_PHP_FILE}"; then
  info "link conf file '\033[4m${VOLUME_HOME}/etc.d/php.conf\033[0m' to '\033[4m${SUPERVISOR_PHP_FILE}\033[0m' - [\033[32msuccess\033[0m]"
fi

SUPERVISOR_CRON_FILE="/etc/supervisor/conf.d/crontab.conf"

if [[ ! -f "${VOLUME_HOME}/etc.d/crontab.conf" ]]; then
  if cat <<EOF > ${VOLUME_HOME}/etc.d/crontab.conf; then
[program:crontab]
directory=/var/www/docker/crontab
command=/etc/init.d/cron start
numprocs=1
autostart=true
autorestart=false
startretries=1
user=root
redirect_stderr=true
stdout_logfile=/var/log/supervisor/%(program_name)s.log
EOF
    info "generate supervisor file '\033[4m${VOLUME_HOME}/etc.d/crontab.conf\033[0m' - [\033[32msuccess\033[0m]"

    if ln -sfT "${VOLUME_HOME}/etc.d/crontab.conf" "${SUPERVISOR_CRON_FILE}"; then
      info "link conf file '\033[4m${VOLUME_HOME}/etc.d/crontab.conf\033[0m' to '\033[4m${SUPERVISOR_CRON_FILE}\033[0m' - [\033[32msuccess\033[0m]"
    fi
  fi
elif ln -sfT "${VOLUME_HOME}/etc.d/crontab.conf" "${SUPERVISOR_CRON_FILE}"; then
  info "link conf file '\033[4m${VOLUME_HOME}/etc.d/crontab.conf\033[0m' to '\033[4m${SUPERVISOR_CRON_FILE}\033[0m' - [\033[32msuccess\033[0m]"
fi

if [[ -d "/var/cache/dootask" ]]; then
  cp -nr /var/cache/dootask/* /var/www/storage
fi

/bin/bash "/8.0.sh"
