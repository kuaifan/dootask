#!/bin/bash

#fonts color
Green="\033[32m"
Yellow="\033[33m"
Red="\033[31m"
GreenBG="\033[42;37m"
YellowBG="\033[43;37m"
RedBG="\033[41;37m"
Font="\033[0m"

#notification information
OK="${Green}[OK]${Font}"
Warn="${Yellow}[警告]${Font}"
Error="${Red}[错误]${Font}"

cur_path="$(pwd)"
cur_arg=$@
COMPOSE="docker-compose"

judge() {
    if [[ 0 -eq $? ]]; then
        success "$1 完成"
        sleep 1
    else
        error "$1 失败"
        exit 1
    fi
}

success() {
    echo -e "${OK} ${GreenBG}$1${Font}"
}

warning() {
    echo -e "${Warn} ${YellowBG}$1${Font}"
}

error() {
    echo -e "${Error} ${RedBG}$1${Font}"
}

info() {
    echo -e "$1"
}

rand() {
    local min=$1
    local max=$(($2-$min+1))
    local num=$(($RANDOM+1000000000))
    echo $(($num%$max+$min))
}

rand_string() {
    local lan=$1
    if [[ `uname` == 'Linux' ]]; then
        echo "$(date +%s%N | md5sum | cut -c 1-${lan})"
    else
        echo "$(docker run -it --rm alpine sh -c "date +%s%N | md5sum | cut -c 1-${lan}")"
    fi
}

restart_php() {
    local RES=`run_exec php "supervisorctl update php"`
    if [ -z "$RES" ]; then
        RES=`run_exec php "supervisorctl restart php"`
    fi
    local IN=`echo $RES | grep "ERROR"`
    if [[ "$IN" != "" ]]; then
        $COMPOSE stop php
        $COMPOSE start php
    else
        info "$RES"
    fi
}

switch_debug() {
    local debug="false"
    if [[ "$1" == "true" ]] || [[ "$1" == "dev" ]] || [[ "$1" == "open" ]]; then
        debug="true"
    fi
    if [[ "$(env_get APP_DEBUG)" != "$debug" ]]; then
        env_set APP_DEBUG "$debug"
        restart_php
    fi
}

check_docker() {
    docker --version &> /dev/null
    if [ $? -ne  0 ]; then
        error "未安装 Docker！"
        exit 1
    fi
    docker-compose version &> /dev/null
    if [ $? -ne  0 ]; then
        docker compose version &> /dev/null
        if [ $? -ne  0 ]; then
            error "未安装 Docker-compose！"
            exit 1
        fi
        COMPOSE="docker compose"
    fi
    if [[ -n `$COMPOSE version | grep -E "\sv1"` ]]; then
        $COMPOSE version
        error "Docker-compose 版本过低，请升级至v2+！"
        exit 1
    fi
}

check_node() {
    npm --version &> /dev/null
    if [ $? -ne  0 ]; then
        error "未安装 npm！"
        exit 1
    fi
    node --version &> /dev/null
    if [ $? -ne  0 ]; then
        error "未安装 Node.js！"
        exit 1
    fi
    if [[ -n `node --version | grep -E "v1"` ]]; then
        node --version
        error "Node.js 版本过低，请升级至v20+！"
        exit 1
    fi
}

docker_name() {
    echo `$COMPOSE ps | awk '{print $1}' | grep "\-$1\-"`
}

run_compile() {
    local type=$1
    check_node
    if [ ! -d "./node_modules" ]; then
        npm install
    fi
    if [ "$type" = "dev" ]; then
        echo "<script>window.location.href=window.location.href.replace(/:\d+/, ':' + $(env_get APP_PORT))</script>" > ./index.html
        env_set APP_DEV_PORT $(rand 20001 30000)
    fi
    switch_debug "$type"
    #
    if [ "$type" = "prod" ]; then
        rm -rf "./public/js/build"
        npx vite build -- fromcmd
    else
        npx vite -- fromcmd
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
    #
    BUILD_FRONTEND="build"
    if [ "$argv" == "dev" ]; then
        switch_debug "$argv"
        BUILD_FRONTEND="dev"
    fi
    env BUILD_FRONTEND=$BUILD_FRONTEND node ./electron/build.js $argv
}

run_exec() {
    local container=$1
    local cmd=$2
    local name=`docker_name $container`
    if [ -z "$name" ]; then
        error "没有找到 $container 容器!"
        exit 1
    fi
    docker exec -it "$name" /bin/sh -c "$cmd"
}

run_mysql() {
    if [ "$1" = "backup" ]; then
        database=$(env_get DB_DATABASE)
        username=$(env_get DB_USERNAME)
        password=$(env_get DB_PASSWORD)
        # 备份数据库
        mkdir -p ${cur_path}/docker/mysql/backup
        filename="${cur_path}/docker/mysql/backup/${database}_$(date "+%Y%m%d%H%M%S").sql.gz"
        run_exec mariadb "exec mysqldump --databases $database -u$username -p$password" | gzip > $filename
        judge "备份数据库"
        [ -f "$filename" ] && info "备份文件：$filename"
    elif [ "$1" = "recovery" ]; then
        database=$(env_get DB_DATABASE)
        username=$(env_get DB_USERNAME)
        password=$(env_get DB_PASSWORD)
        # 还原数据库
        mkdir -p ${cur_path}/docker/mysql/backup
        list=`ls -1 "${cur_path}/docker/mysql/backup" | grep ".sql.gz"`
        if [ -z "$list" ]; then
            error "没有备份文件！"
            exit 1
        fi
        echo "$list"
        read -rp "请输入备份文件名称还原：" inputname
        filename="${cur_path}/docker/mysql/backup/${inputname}"
        if [ ! -f "$filename" ]; then
            error "备份文件：${inputname} 不存在！"
            exit 1
        fi
        container_name=`docker_name mariadb`
        if [ -z "$container_name" ]; then
            error "没有找到 mariadb 容器!"
            exit 1
        fi
        docker cp $filename $container_name:/
        run_exec mariadb "gunzip < /$inputname | mysql -u$username -p$password $database"
        run_exec php "php artisan migrate"
        judge "还原数据库"
    elif [ "$1" = "open" ]; then
        container_name=`docker_name mariadb`
        if [ -z "$container_name" ]; then
            error "没有找到 mariadb 容器!"
            exit 1
        fi
        mkdir -p ${cur_path}/docker/mysql/tmp
        cat > ${cur_path}/docker/mysql/tmp/${container_name}.conf <<EOF
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log notice;
pid /var/run/nginx.pid;
events {
    worker_connections 1024;
}
stream {
    upstream mysql {
        server ${container_name}:3306 max_fails=1 fail_timeout=30s;
    }
    server {
        listen 3306;
        proxy_pass mysql;
        proxy_connect_timeout 5s;
    }
}
EOF
        default_value="$(env_get DB_PORT_OPEN)"
        if [ -n "$default_value" ]; then
            read_tip="请输入代理端口 (3300-65500, 默认: ${default_value}): "
        else
            read_tip="请输入代理端口 (3300-65500): "
        fi
        read -rp "$read_tip" inputport
        inputport=${inputport:-$default_value}
        if [ $inputport -lt 3300 ] || [ $inputport -gt 65500 ]; then
            error "端口范围不正确！"
            exit 1
        fi
        env_set DB_PORT_OPEN $inputport
        run_mysql rm-port
        docker run --name ${container_name}-port \
            --network dootask-networks-$(env_get APP_ID) \
            -p ${inputport}:3306 \
            -v ${cur_path}/docker/mysql/tmp/${container_name}.conf:/etc/nginx/nginx.conf \
            -d nginx:alpine > /dev/null
        judge "开启代理"
    elif [ "$1" = "close" ]; then
        container_name=`docker_name mariadb`
        if [ -z "$container_name" ]; then
            error "没有找到 mariadb 容器!"
            exit 1
        fi
        docker stop ${container_name}-port > /dev/null
        docker rm ${container_name}-port > /dev/null
        judge "关闭代理"
    elif [ "$1" = "rm-port" ]; then
        docker rm -f $(docker_name mariadb)-port &> /dev/null
    fi
}

https_auto() {
    restart_nginx="n"
    if [[ "$(env_get APP_PORT)" != "80" ]]; then
        warning "HTTP服务端口不是80，是否修改并继续操作？ [Y/n]"
        read -r continue_http
        [[ -z ${continue_http} ]] && continue_http="Y"
        case $continue_http in
        [yY][eE][sS] | [yY])
            success "继续操作"
            env_set "APP_PORT" "80"
            restart_nginx="y"
            ;;
        *)
            error "操作终止"
            exit 1
            ;;
        esac
    fi
    if [[ "$(env_get APP_SSL_PORT)" != "443" ]]; then
        warning "HTTPS服务端口不是443，是否修改并继续操作？ [Y/n]"
        read -r continue_https
        [[ -z ${continue_https} ]] && continue_https="Y"
        case $continue_https in
        [yY][eE][sS] | [yY])
            success "继续操作"
            env_set "APP_SSL_PORT" "443"
            restart_nginx="y"
            ;;
        *)
            error "操作终止"
            exit 1
            ;;
        esac
    fi
    if [[ "$restart_nginx" == "y" ]]; then
        $COMPOSE up -d
    fi
    docker run -it --rm -v $(pwd):/work nginx:alpine sh /work/bin/https install
    if [[ 0 -eq $? ]]; then
        run_exec nginx "nginx -s reload"
    fi
    new_job="* 6 * * * docker run -it --rm -v $(pwd):/work nginx:alpine sh /work/bin/https renew"
    current_crontab=$(crontab -l 2>/dev/null)
    if ! echo "$current_crontab" | grep -v "https renew"; then
        echo "任务已存在，无需添加。"
    else
        crontab -l |{
            cat
            echo "$new_job"
        } | crontab -
        echo "任务已添加。"
    fi
}

env_get() {
    local key=$1
    local value=`cat ${cur_path}/.env | grep "^$key=" | awk -F '=' '{print $2}' | tr -d '\r\n'`
    echo "$value"
}

env_set() {
    local key=$1
    local val=$2
    local exist=`cat ${cur_path}/.env | grep "^$key="`
    if [ -z "$exist" ]; then
        echo "$key=$val" >> $cur_path/.env
    else
        if [[ `uname` == 'Linux' ]]; then
            sed -i "/^${key}=/c\\${key}=${val}" ${cur_path}/.env
        else
            docker run -it --rm -v ${cur_path}:/www alpine sh -c "sed -i "/^${key}=/c\\${key}=${val}" /www/.env"
        fi
        if [ $? -ne  0 ]; then
            error "设置env参数失败！"
            exit 1
        fi
    fi
}

env_init() {
    if [ ! -f ".env" ]; then
        cp .env.docker .env
    fi
    if [ -z "$(env_get DB_ROOT_PASSWORD)" ]; then
        env_set DB_ROOT_PASSWORD "$(rand_string 16)"
    fi
    if [ -z "$(env_get APP_ID)" ]; then
        env_set APP_ID "$(rand_string 6)"
    fi
    if [ -z "$(env_get APP_IPPR)" ]; then
        env_set APP_IPPR "10.$(rand 50 100).$(rand 100 200)"
    fi
}

arg_get() {
    local find="n"
    local value=""
    for var in $cur_arg; do
        if [[ "$find" == "y" ]]; then
            if [[ ! $var =~ "--" ]]; then
                value=$var
            fi
            break
        fi
        if [[ "--$1" == "$var" ]] || [[ "-$1" == "$var" ]]; then
            find="y"
            value="yes"
        fi
    done
    echo $value
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
        # 初始化文件
        if [[ -n "$(arg_get relock)" ]]; then
            rm -rf node_modules
            rm -rf package-lock.json
            rm -rf vendor
            rm -rf composer.lock
        fi
        # 目录权限
        volumes=(
            "docker/log/supervisor"
            "docker/mysql/data"
            "docker/office/logs"
            "docker/office/data"
            "docker/es/data"
        )
        cmda=""
        cmdb=""
        for vol in "${volumes[@]}"; do
            mkdir -p "${cur_path}/${vol}"
            chmod -R 775 "${cur_path}/${vol}"
            rm -f "${cur_path}/${vol}/dootask.lock"
            cmda="${cmda} -v ./${vol}:/usr/share/${vol}"
            cmdb="${cmdb} touch /usr/share/${vol}/dootask.lock &&"
        done
        docker run --rm ${cmda} nginx:alpine sh -c "${cmdb} touch /usr/share/docker/dootask.lock"
        for vol in "${volumes[@]}"; do
            if [ ! -f "${cur_path}/${vol}/dootask.lock" ]; then
                error "目录【${vol}】权限不足！"
                exit 1
            fi
        done
        # 设置ES索引后缀
        env_set ES_INDEX_SUFFIX "$(rand_string 6)"
        # 启动容器
        [[ "$(arg_get port)" -gt 0 ]] && env_set APP_PORT "$(arg_get port)"
        $COMPOSE up php -d
        # 安装composer依赖
        run_exec php "composer install"
        if [ ! -f "${cur_path}/vendor/autoload.php" ]; then
            run_exec php "composer config repo.packagist composer https://packagist.phpcomposer.com"
            run_exec php "composer install"
            run_exec php "composer config --unset repos.packagist"
        fi
        if [ ! -f "${cur_path}/vendor/autoload.php" ]; then
            error "composer install 失败，请重试！"
            exit 1
        fi
        [[ -z "$(env_get APP_KEY)" ]] && run_exec php "php artisan key:generate"
        switch_debug "false"
        # 检查数据库
        remaining=20
        while [ ! -f "${cur_path}/docker/mysql/data/$(env_get DB_DATABASE)/db.opt" ]; do
            ((remaining=$remaining-1))
            if [ $remaining -lt 0 ]; then
                error "数据库初始化失败!"
                exit 1
            fi
            sleep 3
            chmod -R 775 "${cur_path}/docker/mysql/data"
        done
        # 数据库迁移
        remaining=20
        while [ ! -f "${cur_path}/docker/mysql/data/$(env_get DB_DATABASE)/$(env_get DB_PREFIX)migrations.ibd" ]; do
            ((remaining=$remaining-1))
            if [ $remaining -lt 0 ]; then
                error "数据库安装失败!"
                exit 1
            fi
            sleep 3
            run_exec php "php artisan migrate --seed"
        done
        # 设置初始化密码
        res=`run_exec mariadb "sh /etc/mysql/repassword.sh"`
        $COMPOSE up -d
        restart_php
        success "安装完成"
        info "地址: http://${GreenBG}127.0.0.1:$(env_get APP_PORT)${Font}"
        info "$res"
    elif [[ "$1" == "update" ]]; then
        shift 1
        if [[ "$@" != "nobackup" ]]; then
            run_mysql backup
        fi
        if [[ -z "$(arg_get local)" ]]; then
            git fetch --all
            git reset --hard origin/$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
            git pull
            run_exec php "composer update"
        fi
        run_exec php "php artisan migrate"
        run_exec nginx "nginx -s reload"
        restart_php
        $COMPOSE up -d
    elif [[ "$1" == "uninstall" ]]; then
        shift 1
        read -rp "确定要卸载（含：删除容器、数据库、日志）吗？(Y/n): " uninstall
        [[ -z ${uninstall} ]] && uninstall="Y"
        case $uninstall in
        [yY][eE][sS] | [yY])
            info "${RedBG}开始卸载...${Font}"
            ;;
        *)
            info "${GreenBG}终止卸载。${Font}"
            exit 2
            ;;
        esac
        run_mysql rm-port
        $COMPOSE down
        env_set APP_DEBUG "false"
        rm -rf "./docker/mysql/data"
        rm -rf "./docker/log/supervisor"
        find "./storage/logs" -name "*.log" | xargs rm -rf
        success "卸载完成"
    elif [[ "$1" == "reinstall" ]]; then
        shift 1
        ./cmd uninstall $@
        sleep 3
        ./cmd install $@
    elif [[ "$1" == "port" ]]; then
        shift 1
        env_set APP_PORT "$1"
        $COMPOSE up -d
        success "修改成功"
        info "地址: http://${GreenBG}127.0.0.1:$(env_get APP_PORT)${Font}"
    elif [[ "$1" == "url" ]]; then
        shift 1
        env_set APP_URL "$1"
        restart_php
        success "修改成功"
    elif [[ "$1" == "env" ]]; then
        shift 1
        if [ -n "$1" ]; then
            env_set $1 "$2"
        fi
        restart_php
        success "修改成功"
    elif [[ "$1" == "repassword" ]]; then
        shift 1
        run_exec mariadb "sh /etc/mysql/repassword.sh \"$@\""
    elif [[ "$1" == "serve" ]] || [[ "$1" == "dev" ]] || [[ "$1" == "development" ]]; then
        shift 1
        run_compile dev
    elif [[ "$1" == "build" ]] || [[ "$1" == "prod" ]] || [[ "$1" == "production" ]]; then
        shift 1
        run_compile prod
    elif [[ "$1" == "appbuild" ]] || [[ "$1" == "buildapp" ]]; then
        shift 1
        run_electron app $@
    elif [[ "$1" == "electron" ]]; then
        shift 1
        run_electron $@
    elif [[ "$1" == "eeui" ]]; then
        shift 1
        cli="$@"
        por=""
        if [[ "$cli" == "build" ]]; then
            cli="build --simple"
        elif [[ "$cli" == "dev" ]]; then
            por="-p 8880:8880"
        fi
        docker run -it --rm -v ${cur_path}/resources/mobile:/work -w /work ${por} kuaifan/eeui-cli:0.0.1 eeui ${cli}
    elif [[ "$1" == "npm" ]]; then
        shift 1
        npm $@
        cd electron
        npm $@
        cd ..
        docker run --rm -it -v ${cur_path}/resources/mobile:/work -w /work --entrypoint=/bin/bash node:16 -c "npm $@"
    elif [[ "$1" == "doc" ]]; then
        shift 1
        run_exec php "php app/Http/Controllers/Api/apidoc.php"
        docker run -it --rm -v ${cur_path}:/home/node/apidoc kuaifan/apidoc -i app/Http/Controllers/Api -o public/docs
    elif [[ "$1" == "debug" ]]; then
        shift 1
        switch_debug "$@"
        info "success"
    elif [[ "$1" == "https" ]]; then
        shift 1
        if [[ "$1" == "agent" ]] || [[ "$1" == "true" ]]; then
            env_set APP_SCHEME "true"
        elif [[ "$1" == "close" ]] || [[ "$1" == "auto" ]]; then
            env_set APP_SCHEME "auto"
        else
            https_auto
        fi
        restart_php
    elif [[ "$1" == "artisan" ]]; then
        shift 1
        e="php artisan $@" && run_exec php "$e"
    elif [[ "$1" == "php" ]]; then
        shift 1
        if [[ "$1" == "restart" ]] || [[ "$1" == "reboot" ]]; then
            restart_php
        else
            e="php $@" && run_exec php "$e"
        fi
    elif [[ "$1" == "nginx" ]]; then
        shift 1
        e="nginx $@" && run_exec nginx "$e"
    elif [[ "$1" == "redis" ]]; then
        shift 1
        e="redis $@" && run_exec redis "$e"
    elif [[ "$1" == "mysql" ]]; then
        shift 1
        if [[ "$1" == "backup" ]] || [[ "$1" == "b" ]]; then
            run_mysql backup
        elif [[ "$1" == "recovery" ]] || [[ "$1" == "r" ]]; then
            run_mysql recovery
        elif [[ "$1" == "agent" ]] || [[ "$1" == "open" ]]; then
            run_mysql open
        elif [[ "$1" == "unagent" ]] || [[ "$1" == "close" ]]; then
            run_mysql close
        else
            e="mysql $@" && run_exec mariadb "$e"
        fi
    elif [[ "$1" == "composer" ]]; then
        shift 1
        e="composer $@" && run_exec php "$e"
    elif [[ "$1" == "service" ]]; then
        shift 1
        e="service $@" && run_exec php "$e"
    elif [[ "$1" == "super" ]] || [[ "$1" == "supervisorctl" ]]; then
        shift 1
        e="supervisorctl $@" && run_exec php "$e"
    elif [[ "$1" == "models" ]]; then
        shift 1
        run_exec php "php app/Models/clearHelper.php"
        run_exec php "php artisan ide-helper:models -W"
    elif [[ "$1" == "translate" ]]; then
        shift 1
        run_exec php "cd /var/www/language && php translate.php"
    elif [[ "$1" == "test" ]]; then
        shift 1
        e="./vendor/bin/phpunit $@" && run_exec php "$e"
    elif [[ "$1" == "restart" ]]; then
        shift 1
        $COMPOSE stop "$@"
        $COMPOSE start "$@"
    else
        if [[ "$1" == "down" ]]; then
            run_mysql rm-port
        fi
        $COMPOSE "$@"
    fi
else
    $COMPOSE ps
fi
