#!/bin/bash

# 缓存执行
if [ -z "$CACHED_EXECUTION" ] && [ "$1" == "update" ]; then
    cat "$0" > ._cmd
    chmod +x ._cmd
    export CACHED_EXECUTION=1
    ./._cmd "$@"
    EXIT_STATUS=$?
    rm -f ._cmd
    exit $EXIT_STATUS
fi

# 颜色
Green="\033[32m"
Yellow="\033[33m"
Red="\033[31m"
GreenBG="\033[42;37m"
YellowBG="\033[43;37m"
RedBG="\033[41;37m"
Font="\033[0m"

# 通知信息
OK="${Green}[OK]${Font}"
Warn="${Yellow}[警告]${Font}"
Error="${Red}[错误]${Font}"

cur_path="$(pwd)"
cur_arg=$@
COMPOSE="docker-compose"

# 判断是否成功
judge() {
    if [[ 0 -eq $? ]]; then
        success "$1 完成"
        sleep 1
    else
        error "$1 失败"
        exit 1
    fi
}

# 执行并判断是否成功
exec_judge() {
    local cmd="$1"
    local error_desc="$2"
    local success_desc="$3"
    eval "$cmd"
    if [[ 0 -ne $? ]]; then
        error "$error_desc"
        exit 1
    fi
    if [[ -n "$success_desc" ]]; then
        success "$success_desc"
    fi
}

# 成功
success() {
    echo -e "${OK} ${GreenBG}$1${Font}"
}

# 警告
warning() {
    echo -e "${Warn} ${YellowBG}$1${Font}"
}

# 错误
error() {
    echo -e "${Error} ${RedBG}$1${Font}"
}

# 信息
info() {
    echo -e "$1"
}

# 随机数
rand() {
    local min=$1
    local max=$(($2-$min+1))
    local num=$(($RANDOM+1000000000))
    echo $(($num%$max+$min))
}

# 随机字符串
rand_string() {
    local lan=$1
    if [[ `uname` == 'Linux' ]]; then
        echo "$(date +%s%N | md5sum | cut -c 1-${lan})"
    else
        echo "$(docker run -it --rm nginx:alpine sh -c "date +%s%N | md5sum | cut -c 1-${lan}")"
    fi
}

# 重启php
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

# 切换调试模式
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

# 检查docker、docker-compose
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

# 检查node
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

# 获取容器名称
docker_name() {
    echo `$COMPOSE ps | awk '{print $1}' | grep "\-$1\-"`
}

# 编译前端
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

# 运行electron
run_electron() {
    local argv=$@
    check_node
    if [ ! -d "./node_modules" ]; then
        npm install
    fi
    if [ ! -d "./electron/node_modules" ]; then
        pushd electron || exit
        npm install
        popd || exit
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

# 执行容器命令
run_exec() {
    local container=$1
    shift 1
    local cmd=$@
    local name=$(docker_name "$container")
    if [ -z "$name" ]; then
        error "没有找到 $container 容器!"
        exit 1
    fi
    docker exec -it "$name" /bin/sh -c "$cmd"
}

# 备份数据库、还原数据库
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
    fi
}

# 根据网络名称删除所有容器
remove_by_network() {
    local app_id=$(env_get APP_ID)
    local network_name="dootask-networks-${app_id}"
    for container_id in $(docker ps -q --filter network="$network_name"); do
        docker rm -f "$container_id" 1>/dev/null
    done
}

# 自动配置https
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

# 获取env参数
env_get() {
    local key=$1
    local value=`cat ${cur_path}/.env | grep "^$key=" | awk -F '=' '{print $2}' | tr -d '\r\n'`
    echo "$value"
}

# 设置env参数
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
            docker run -it --rm -v ${cur_path}:/www nginx:alpine sh -c "sed -i "/^${key}=/c\\${key}=${val}" /www/.env"
        fi
        if [ $? -ne  0 ]; then
            error "设置env参数失败！"
            exit 1
        fi
    fi
}

# 初始化env
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

# 获取命令参数
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

# 显示帮助信息
show_help() {
    cat << 'EOF'
DooTask 管理脚本

用法: ./cmd <命令> [参数]

📦 核心操作:
  install                     安装 DooTask (支持 --port <端口> --relock)
  update                      更新 DooTask (支持 --branch <分支> --force --local)
  uninstall                   卸载 DooTask

⚙️  配置管理:
  port <端口>                 修改服务端口
  url <地址>                  修改访问地址
  env <键> <值>               设置环境变量
  debug [true|false]          切换调试模式
  repassword [用户名]         重置数据库密码

🚀 开发构建:
  serve, dev                  启动开发模式
  build, prod                 生产环境构建
  electron                    构建桌面应用

🔧 服务管理:
  up [服务名]                 启动容器
  down [服务名]               停止容器
  restart [服务名]            重启容器
  reup                        重新构建并启动

💾 数据库操作:
  mysql backup                备份数据库
  mysql recovery              还原数据库

🛠️  开发工具:
  artisan <命令>              执行 Laravel Artisan 命令
  composer <命令>             执行 Composer 命令
  php <命令>                  执行 PHP 命令

📚 其他:
  doc                         生成 API 文档
  https                       配置 HTTPS
  --help, -h                  显示此帮助信息

示例:
  ./cmd install --port 8080   安装并指定端口 8080
  ./cmd update --branch dev   切换到 dev 分支并更新
  ./cmd mysql backup          备份数据库
  ./cmd artisan migrate       执行数据库迁移
EOF
}

# 安装函数
run_install() {
    local relock=$(arg_get relock)
    local port=$(arg_get port)
    
    # 初始化文件
    if [[ -n "$relock" ]]; then
        rm -rf node_modules package-lock.json vendor composer.lock
    fi
    
    # 目录权限设置
    volumes=(
        "bootstrap/cache"
        "docker"
        "public"
        "storage"
    )
    cmda=""
    cmdb=""
    for vol in "${volumes[@]}"; do
        tmp_path="${cur_path}/${vol}"
        mkdir -p "${tmp_path}"
        chmod -R 775 "${tmp_path}"
        rm -f "${tmp_path}/dootask.lock"
        cmda="${cmda} -v ${tmp_path}:/usr/share/${vol}"
        cmdb="${cmdb} touch /usr/share/${vol}/dootask.lock &&"
    done
    
    # 目录权限检测
    remaining=10
    while true; do
        ((remaining=$remaining-1))
        writable="yes"
        docker run --rm ${cmda} nginx:alpine sh -c "${cmdb} touch /usr/share/docker/dootask.lock" &> /dev/null
        if [ $? -ne 0 ]; then
            error "目录权限检测失败！请检查目录权限设置"
            exit 1
        fi
        for vol in "${volumes[@]}"; do
            if [ ! -f "${vol}/dootask.lock" ]; then
                if [ $remaining -lt 0 ]; then
                    error "目录【${vol}】权限不足！"
                    exit 1
                else
                    writable="no"
                    break
                fi
            fi
        done
        if [ "$writable" == "yes" ]; then
            break
        else
            sleep 3
        fi
    done
    
    # 设置端口
    [[ "$port" -gt 0 ]] && env_set APP_PORT "$port"
    
    # 启动PHP容器
    $COMPOSE up php -d
    
    # 安装PHP依赖
    exec_judge "run_exec php 'composer install --no-dev --optimize-autoloader'" "安装依赖失败"
    
    # 最终检查
    if [ ! -f "${cur_path}/vendor/autoload.php" ]; then
        error "安装依赖失败，请重试！"
        exit 1
    fi
    
    # 生成应用密钥
    [[ -z "$(env_get APP_KEY)" ]] && exec_judge "run_exec php 'php artisan key:generate'" "生成密钥失败"
    
    # 设置生产模式
    switch_debug "false"
    
    # 数据库迁移
    exec_judge "run_exec php 'php artisan migrate --seed'" "数据库迁移失败"
    
    # 启动所有容器
    $COMPOSE up -d --remove-orphans
    
    success "安装完成"
    info "地址: http://${GreenBG}127.0.0.1:$(env_get APP_PORT)${Font}"
    run_exec mariadb "sh /etc/mysql/repassword.sh"
}

# 更新函数
run_update() {
    local target_branch=$(arg_get branch)
    local is_local=$(arg_get local)
    local force_update=$(arg_get force)
    
    if [[ -z "$is_local" ]]; then
        # 远程更新模式
        exec_judge "git fetch --all" "获取远程更新失败"
        
        # 确定目标分支
        if [[ -n "$target_branch" ]]; then
            current_branch="$target_branch"
            exec_judge "git checkout $target_branch" "切换分支到 $target_branch 失败"
        else
            current_branch=$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
        fi
        
        # 检查数据库迁移变动
        db_changes=$(git diff --name-only HEAD..origin/$current_branch | grep -E "^database/" || true)
        if [[ -n "$db_changes" ]]; then
            info "数据库有迁移变动，执行数据库备份..."
            exec_judge "run_mysql backup" "数据库备份失败" "数据库备份完成"
        fi
        
        # 检查本地修改
        if ! git diff --quiet || ! git diff --cached --quiet; then
            if [[ "$force_update" != "yes" ]]; then
                warning "检测到本地修改，是否强制更新？[Y/n]"
                read -r confirm_force
                [[ -z ${confirm_force} ]] && confirm_force="Y"
                case $confirm_force in
                [yY][eE][sS] | [yY])
                    force_update="yes"
                    ;;
                *)
                    error "取消更新，请先处理本地修改"
                    exit 1
                    ;;
                esac
            fi
        fi
        
        # 更新代码
        if [[ "$force_update" == "yes" ]]; then
            exec_judge "git reset --hard origin/$current_branch" "强制更新代码失败"
        else
            exec_judge "git pull --ff-only origin $current_branch" "代码拉取失败，可能存在冲突，请使用 --force 参数"
        fi
        
        # 更新依赖
        exec_judge "run_exec php 'composer install --no-dev --optimize-autoloader'" "更新PHP依赖失败"
    else
        # 本地更新模式
        info "执行数据库备份..."
        exec_judge "run_mysql backup" "数据库备份失败" "数据库备份完成"
    fi
    
    # 数据库迁移
    exec_judge "run_exec php 'php artisan migrate'" "数据库迁移失败"
    
    # 重启服务
    exec_judge "run_exec nginx 'nginx -s reload'" "重载Nginx失败"
    restart_php
    $COMPOSE up -d --remove-orphans
    
    success "更新完成！"
}

# 卸载函数
run_uninstall() {
    # 确认卸载
    read -rp "确定要卸载（含：删除容器、数据库、日志）吗？(Y/n): " confirm_uninstall
    [[ -z ${confirm_uninstall} ]] && confirm_uninstall="Y"
    case $confirm_uninstall in
    [yY][eE][sS] | [yY])
        info "${RedBG}开始卸载...${Font}"
        ;;
    *)
        info "${GreenBG}终止卸载。${Font}"
        exit 1
        ;;
    esac
    
    # 清理网络相关容器
    remove_by_network
    
    # 停止并删除容器
    $COMPOSE down --remove-orphans
    
    # 重置调试模式
    env_set APP_DEBUG "false"
    
    # 清理数据目录
    find "./docker/mysql/data" -mindepth 1 -delete 2>/dev/null
    find "./docker/logs/supervisor" -mindepth 1 -delete 2>/dev/null
    find "./docker/appstore/config" -mindepth 1 -type d -exec rm -rf {} + 2>/dev/null
    find "./docker/appstore/log" -name "*.log" -delete 2>/dev/null
    find "./storage/logs" -name "*.log" -delete 2>/dev/null
    
    success "卸载完成"
}

####################################################################################
####################################################################################
####################################################################################

# 优先处理帮助命令
if [[ "$1" == "help" ]] || [[ "$1" == "--help" ]] || [[ "$1" == "-h" ]] || [[ $# -eq 0 ]]; then
    show_help
    exit 0
fi

# 非electron命令需要检查Docker环境
if [[ "$1" != "electron" ]]; then
    check_docker
    env_init
fi

# 执行命令
if [[ "$1" == "install" ]]; then
    shift 1
    run_install
elif [[ "$1" == "update" ]]; then
    shift 1
    run_update
elif [[ "$1" == "uninstall" ]]; then
    shift 1
    run_uninstall
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
    run_exec mariadb "sh /etc/mysql/repassword.sh $@"
elif [[ "$1" == "serve" ]] || [[ "$1" == "dev" ]]; then
    shift 1
    run_compile dev
elif [[ "$1" == "build" ]] || [[ "$1" == "prod" ]]; then
    shift 1
    run_compile prod
elif [[ "$1" == "appbuild" ]] || [[ "$1" == "buildapp" ]]; then
    shift 1
    run_electron app "$@"
elif [[ "$1" == "electron" ]]; then
    shift 1
    run_electron "$@"
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
    npm "$@"
    pushd electron || exit
    npm "$@"
    popd || exit
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
elif [[ "$1" == "restart" ]]; then
    shift 1
    $COMPOSE stop "$@"
    $COMPOSE start "$@"
elif [[ "$1" == "reup" ]]; then
    shift 1
    remove_by_network
    $COMPOSE down --remove-orphans
    $COMPOSE up -d
elif [[ "$1" == "down" ]]; then
    shift 1
    remove_by_network
    if [[ $# -eq 0 ]]; then
        $COMPOSE down --remove-orphans
    else
        $COMPOSE down "$@"
    fi
elif [[ "$1" == "up" ]]; then
    shift 1
    if [[ $# -eq 0 ]]; then
        $COMPOSE up -d --remove-orphans
    else
        $COMPOSE up "$@"
    fi
else
    $COMPOSE "$@"
fi
