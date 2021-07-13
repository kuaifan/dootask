#!/bin/bash

#fonts color
Green="\033[32m"
Red="\033[31m"
GreenBG="\033[42;37m"
RedBG="\033[41;37m"
Font="\033[0m"

#notification information
OK="${Green}[OK]${Font}"
Error="${Red}[错误]${Font}"

cur_path="$(pwd)"

judge() {
    if [[ 0 -eq $? ]]; then
        echo -e "${OK} ${GreenBG} $1 完成 ${Font}"
        sleep 1
    else
        echo -e "${Error} ${RedBG} $1 失败${Font}"
        exit 1
    fi
}

supervisorctl_restart() {
    RES=`docker-compose exec php /bin/bash -c "supervisorctl update $1"`
    if [ -z "$RES" ];then
        docker-compose exec php /bin/bash -c "supervisorctl restart $1"
    else
        echo -e "$RES"
    fi
}

check_docker() {
    docker -v &> /dev/null
    if [ $? -ne  0 ]; then
        echo -e "${Error} ${RedBG} 未安装 Docker！${Font}"
        exit 1
    fi
    docker-compose -v &> /dev/null
    if [ $? -ne  0 ]; then
        echo -e "${Error} ${RedBG} 未安装 Docker-compose！${Font}"
        exit 1
    fi
}

check_node() {
    npm -v > /dev/null
    if [ $? -ne  0 ]; then
        echo -e "${Error} ${RedBG} 未安装nodejs！${Font}"
        exit 1
    fi
}

check_md5sum() {
    date +%s%N | md5sum > /dev/null
    if [ $? -ne  0 ]; then
        echo -e "${Error} ${RedBG} 未安装 md5sum！${Font}"
        exit 1
    fi
}

env_get() {
    key=$1
    value=`cat ${cur_path}/.env | grep "^$key=" | awk -F '=' '{print $2}'`
    echo "$value"
}

env_set() {
    key=$1
    val=$2
    exist=`cat ${cur_path}/.env | grep "^$key="`
    if [ -z "$exist" ];then
        echo "$key=$val" >> $cur_path/.env
    else
        command="sed -i '/^$key=/c\\$key=$val' /www/.env"
        docker run -it --rm -v ${cur_path}:/www alpine sh -c "$command"
    fi
}

env_init() {
    if [ ! -f ".env" ];then
        cp .env.docker .env
    fi
    if [ -z "$(env_get DB_ROOT_PASSWORD)" ];then
        check_md5sum
        env_set DB_ROOT_PASSWORD "$(date +%s%N | md5sum | cut -c 1-16)"
    fi
    if [ -z "$(env_get DOCKER_ID)" ];then
        check_md5sum
        env_set DOCKER_ID "$(date +%s%N | md5sum | cut -c 1-6)"
    fi
}

####################################################################################
####################################################################################
####################################################################################

COMPOSE="docker-compose"
env_init
check_docker

if [ $# -gt 0 ];then
    if [[ "$1" == "init" ]] || [[ "$1" == "install" ]]; then
        shift 1
        networkid=`docker network ls | grep "dooteak-networks-" | awk '{print $1}'`
        if [ -n "$networkid" ]; then
            docker network rm "$networkid" > /dev/null
        fi
        rm -rf composer.lock
        rm -rf package-lock.json
        mkdir -p ${cur_path}/docker/mysql/data
        chmod -R 777 ${cur_path}/docker/mysql/data
        $COMPOSE up -d
        $COMPOSE restart php
        $COMPOSE exec php /bin/bash -c "composer install"
        [ -z "$(env_get APP_KEY)" ] && $COMPOSE exec php /bin/bash -c "php artisan key:generate"
        $COMPOSE exec php /bin/bash -c "php artisan migrate --seed"
        $COMPOSE exec php /bin/bash -c "php bin/run --port=2222 --ssl=2223"
        $COMPOSE exec php /bin/bash -c "php bin/run --mode=prod"
        $COMPOSE stop
        $COMPOSE start
    elif [[ "$1" == "update" ]]; then
        shift 1
        git fetch --all
        git reset --hard origin/$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
        git pull
        $COMPOSE exec php /bin/bash -c "composer update"
        $COMPOSE exec php /bin/bash -c "php artisan migrate"
        supervisorctl_restart php
    elif [[ "$1" == "uninstall" ]]; then
        shift 1
        read -rp "确定要卸载（含：删除容器、数据库、日志）吗？(y/N): " uninstall
        [[ -z ${uninstall} ]] && uninstall="N"
        case $uninstall in
        [yY][eE][sS] | [yY])
            echo -e "${RedBG} 开始卸载... ${Font}"
            ;;
        *)
            echo -e "${GreenBG} 终止卸载。 ${Font}"
            exit 2
            ;;
        esac
        docker-compose rm -fs
        rm -rf "./docker/mysql/data"
        rm -rf "./docker/log/supervisor"
        find "./storage/logs" -name "*.log" | xargs rm -rf
        echo -e "${OK} ${GreenBG} 卸载完成 ${Font}"
    elif [[ "$1" == "dev" ]]; then
        shift 1
        check_node
        $COMPOSE exec php /bin/bash -c "php bin/run --mode=dev"
        supervisorctl_restart php
        mix watch --hot
    elif [[ "$1" == "prod" ]]; then
        shift 1
        check_node
        $COMPOSE exec php /bin/bash -c "php bin/run --mode=prod"
        supervisorctl_restart php
        rm -rf "./public/js/build"
        mix --production
    elif [[ "$1" == "super" ]]; then
        shift 1
        supervisorctl_restart "$@"
    elif [[ "$1" == "debug" ]]; then
        shift 1
        if [[ "$@" == "close" ]];then
            env_set APP_DEBUG "false"
        else
            env_set APP_DEBUG "true"
        fi
        supervisorctl_restart php
    elif [[ "$1" == "artisan" ]]; then
        shift 1
        e="php artisan $@" && $COMPOSE exec php /bin/bash -c "$e"
    elif [[ "$1" == "php" ]]; then
        shift 1
        e="php $@" && $COMPOSE exec php /bin/bash -c "$e"
    elif [[ "$1" == "mysql" ]]; then
        shift 1
        if [[ "$@" == "bak" ]];then
            database=$(env_get DB_DATABASE)
            password=$(env_get DB_ROOT_PASSWORD)
            filename="${cur_path}/docker/mysql/bak/${database}_$(date "+%Y%m%d%H%M%S").sql.gz"
            $COMPOSE exec mariadb /bin/sh -c "exec mysqldump --databases $database -uroot -p\"$password\"" | gzip > $filename
            judge "备份数据库"
            [ -f "$filename" ] && echo -e "备份文件：$filename"
        else
            e="mysql $@" && $COMPOSE exec mariadb /bin/sh -c "$e"
        fi
    elif [[ "$1" == "composer" ]]; then
        shift 1
        e="composer $@" && $COMPOSE exec php /bin/bash -c "$e"
    elif [[ "$1" == "supervisorctl" ]]; then
        shift 1
        e="supervisorctl $@" && $COMPOSE exec php /bin/bash -c "$e"
    elif [[ "$1" == "test" ]]; then
        shift 1
        e="./vendor/bin/phpunit $@" && $COMPOSE exec php /bin/bash -c "$e"
    elif [[ "$1" == "npm" ]]; then
        shift 1
        e="npm $@" && $COMPOSE exec php /bin/bash -c "$e"
    elif [[ "$1" == "yarn" ]]; then
        shift 1
        e="yarn $@" && $COMPOSE exec php /bin/bash -c "$e"
    elif [[ "$1" == "restart" ]]; then
        shift 1
        $COMPOSE stop "$@"
        $COMPOSE start "$@"
    else
        $COMPOSE "$@"
    fi
else
    $COMPOSE ps
fi
