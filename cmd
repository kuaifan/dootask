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

rand() {
    local min=$1
    local max=$(($2-$min+1))
    local num=$(($RANDOM+1000000000))
    echo $(($num%$max+$min))
}

supervisorctl_restart() {
    local RES=`run_exec php "supervisorctl update $1"`
    if [ -z "$RES" ]; then
        run_exec php "supervisorctl restart $1"
    else
        echo -e "$RES"
    fi
}

check_docker() {
    docker --version &> /dev/null
    if [ $? -ne  0 ]; then
        echo -e "${Error} ${RedBG} 未安装 Docker！${Font}"
        exit 1
    fi
    docker-compose --version &> /dev/null
    if [ $? -ne  0 ]; then
        echo -e "${Error} ${RedBG} 未安装 Docker-compose！${Font}"
        exit 1
    fi
}

check_node() {
    npm --version > /dev/null
    if [ $? -ne  0 ]; then
        echo -e "${Error} ${RedBG} 未安装nodejs！${Font}"
        exit 1
    fi
}

docker_name() {
    echo `docker-compose ps | awk '{print $1}' | grep "\-$1\-"`
}

run_compile() {
    local type=$1
    check_node
    if [ ! -d "./node_modules" ]; then
        npm install
    fi
    run_exec php "php bin/run --mode=$type"
    supervisorctl_restart php
    #
    if [ "$type" = "prod" ]; then
        rm -rf "./public/js/build"
        npx mix --production
    else
        npx mix watch --hot
    fi
}

run_electron() {
    local argv=$@
    check_node
    if [ ! -d "./node_modules" ]; then
        npm install
    fi
    if [ ! -d "./electron/node_modules" ]; then
        pushd electron
        npm install
        popd
    fi
    #
    if [ -d "./electron/dist" ]; then
        rm -rf "./electron/dist"
    fi
    if [ -d "./electron/public" ]; then
        rm -rf "./electron/public"
    fi
    mkdir -p ./electron/public
    cp ./electron/index.html ./electron/public/index.html
    #
    if [ "$argv" != "dev" ]; then
        npx mix --production -- --env --electron
    fi
    node ./electron/build.js $argv
}

run_exec() {
    local container=$1
    local cmd=$2
    local name=`docker_name $container`
    if [ -z "$name" ]; then
        echo -e "${Error} ${RedBG} 没有找到 $container 容器! ${Font}"
        exit 1
    fi
    if [ "$container" = "mariadb" ] || [ "$container" = "nginx" ] || [ "$container" = "redis" ]; then
        docker exec -it "$name" /bin/sh -c "$cmd"
    else
        docker exec -it "$name" /bin/bash -c "$cmd"
    fi
}

run_mysql() {
    if [ "$1" = "backup" ]; then
        # 备份数据库
        database=$(env_get DB_DATABASE)
        username=$(env_get DB_USERNAME)
        password=$(env_get DB_PASSWORD)
        mkdir -p ${cur_path}/docker/mysql/backup
        filename="${cur_path}/docker/mysql/backup/${database}_$(date "+%Y%m%d%H%M%S").sql.gz"
        run_exec mariadb "exec mysqldump --databases $database -u$username -p$password" | gzip > $filename
        judge "备份数据库"
        [ -f "$filename" ] && echo -e "备份文件：$filename"
    elif [ "$1" = "recovery" ]; then
        # 还原数据库
        database=$(env_get DB_DATABASE)
        username=$(env_get DB_USERNAME)
        password=$(env_get DB_PASSWORD)
        mkdir -p ${cur_path}/docker/mysql/backup
        list=`ls -1 "${cur_path}/docker/mysql/backup" | grep ".sql.gz"`
        if [ -z "$list" ]; then
            echo -e "${Error} ${RedBG} 没有备份文件！${Font}"
            exit 1
        fi
        echo "$list"
        read -rp "请输入备份文件名称还原：" inputname
        filename="${cur_path}/docker/mysql/backup/${inputname}"
        if [ ! -f "$filename" ]; then
            echo -e "${Error} ${RedBG} 备份文件：${inputname} 不存在！ ${Font}"
            exit 1
        fi
        container_name=`docker_name mariadb`
        if [ -z "$container_name" ]; then
            echo -e "${Error} ${RedBG} 没有找到 mariadb 容器! ${Font}"
            exit 1
        fi
        docker cp $filename $container_name:/
        run_exec mariadb "gunzip < /$inputname | mysql -u$username -p$password $database"
        judge "还原数据库"
    fi
}

env_get() {
    local key=$1
    local value=`cat ${cur_path}/.env | grep "^$key=" | awk -F '=' '{print $2}'`
    echo "$value"
}

env_set() {
    local key=$1
    local val=$2
    local exist=`cat ${cur_path}/.env | grep "^$key="`
    if [ -z "$exist" ]; then
        echo "$key=$val" >> $cur_path/.env
    else
        command="sed -i '/^$key=/c\\$key=$val' /www/.env"
        docker run -it --rm -v ${cur_path}:/www alpine sh -c "$command"
        if [ $? -ne  0 ]; then
            echo -e "${Error} ${RedBG} 设置env参数失败！${Font}"
            exit 1
        fi
    fi
}

env_init() {
    if [ ! -f ".env" ]; then
        cp .env.docker .env
    fi
    if [ -z "$(env_get DB_ROOT_PASSWORD)" ]; then
        env_set DB_ROOT_PASSWORD "$(docker run -it --rm alpine sh -c "date +%s%N | md5sum | cut -c 1-16")"
    fi
    if [ -z "$(env_get APP_ID)" ]; then
        env_set APP_ID "$(docker run -it --rm alpine sh -c "date +%s%N | md5sum | cut -c 1-6")"
    fi
    if [ -z "$(env_get APP_IPPR)" ]; then
        env_set APP_IPPR "10.$(rand 50 100).$(rand 100 200)"
    fi
}

####################################################################################
####################################################################################
####################################################################################

if [[ "$1" != "electron" ]]; then
    check_docker
    env_init
fi

if [ $# -gt 0 ]; then
    if [[ "$1" == "init" ]] || [[ "$1" == "install" ]]; then
        shift 1
        rm -rf composer.lock
        rm -rf package-lock.json
        mkdir -p ${cur_path}/docker/mysql/data
        chmod -R 777 ${cur_path}/docker/mysql/data
        docker-compose up -d
        docker-compose restart php
        run_exec php "composer install"
        [ -z "$(env_get APP_KEY)" ] && run_exec php "php artisan key:generate"
        run_exec php "php artisan migrate --seed"
        run_exec php "php bin/run --mode=prod"
        res=`run_exec mariadb "sh /etc/mysql/repassword.sh"`
        docker-compose stop
        docker-compose start
        echo -e "${OK} ${GreenBG} 安装完成 ${Font}"
        echo -e "地址: http://${GreenBG}127.0.0.1:$(env_get APP_PORT)${Font}"
        echo -e "$res"
    elif [[ "$1" == "update" ]]; then
        shift 1
        git fetch --all
        git reset --hard origin/$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
        git pull
        run_exec php "composer update"
        run_exec php "php artisan migrate"
        supervisorctl_restart php
        docker-compose up -d
    elif [[ "$1" == "uninstall" ]]; then
        shift 1
        read -rp "确定要卸载（含：删除容器、数据库、日志）吗？(y/n): " uninstall
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
        docker-compose down
        rm -rf "./docker/mysql/data"
        rm -rf "./docker/log/supervisor"
        find "./storage/logs" -name "*.log" | xargs rm -rf
        echo -e "${OK} ${GreenBG} 卸载完成 ${Font}"
    elif [[ "$1" == "repassword" ]]; then
        shift 1
        run_exec mariadb "sh /etc/mysql/repassword.sh \"$@\""
    elif [[ "$1" == "dev" ]] || [[ "$1" == "development" ]]; then
        shift 1
        run_compile dev
    elif [[ "$1" == "prod" ]] || [[ "$1" == "production" ]]; then
        shift 1
        run_compile prod
    elif [[ "$1" == "electron" ]]; then
        shift 1
        run_electron $@
    elif [[ "$1" == "doc" ]]; then
        shift 1
        run_exec php "php app/Http/Controllers/Api/apidoc.php"
        docker run -it --rm -v ${cur_path}:/home/node/apidoc kuaifan/apidoc -i app/Http/Controllers/Api -o public/docs
    elif [[ "$1" == "debug" ]]; then
        shift 1
        if [[ "$@" == "close" ]]; then
            env_set APP_DEBUG "false"
        else
            env_set APP_DEBUG "true"
        fi
        supervisorctl_restart php
    elif [[ "$1" == "https" ]]; then
        shift 1
        if [[ "$@" == "auto" ]]; then
            env_set APP_SCHEME "auto"
        else
            env_set APP_SCHEME "true"
        fi
        supervisorctl_restart php
    elif [[ "$1" == "artisan" ]]; then
        shift 1
        e="php artisan $@" && run_exec php "$e"
    elif [[ "$1" == "php" ]]; then
        shift 1
        e="php $@" && run_exec php "$e"
    elif [[ "$1" == "nginx" ]]; then
        shift 1
        e="nginx $@" && run_exec nginx "$e"
    elif [[ "$1" == "redis" ]]; then
        shift 1
        e="redis $@" && run_exec redis "$e"
    elif [[ "$1" == "mysql" ]]; then
        shift 1
        if [ "$1" = "backup" ]; then
            run_mysql backup
        elif [ "$1" = "recovery" ]; then
            run_mysql recovery
        else
            e="mysql $@" && run_exec mariadb "$e"
        fi
    elif [[ "$1" == "composer" ]]; then
        shift 1
        e="composer $@" && run_exec php "$e"
    elif [[ "$1" == "super" ]]; then
        shift 1
        supervisorctl_restart "$@"
    elif [[ "$1" == "supervisorctl" ]]; then
        shift 1
        e="supervisorctl $@" && run_exec php "$e"
    elif [[ "$1" == "models" ]]; then
        shift 1
        run_exec php "php artisan ide-helper:models -W"
    elif [[ "$1" == "test" ]]; then
        shift 1
        e="./vendor/bin/phpunit $@" && run_exec php "$e"
    elif [[ "$1" == "restart" ]]; then
        shift 1
        docker-compose stop "$@"
        docker-compose start "$@"
    else
        docker-compose "$@"
    fi
else
    docker-compose ps
fi
