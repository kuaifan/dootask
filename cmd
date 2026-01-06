#!/bin/bash

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

# 基本参数
WORK_DIR="$(pwd)"
INPUT_ARGS=$@
COMPOSE="docker-compose"

# TTY 参数检测
TTY_FLAG=""
if [ -t 0 ] && [ -t 1 ]; then
    TTY_FLAG="-it"
fi

# 缓存执行
if [ -z "$CACHED_EXECUTION" ] && [ "$1" == "update" ]; then
    if ! cat "$0" > ._cmd 2>/dev/null; then
        error "无法创建脚本副本"
        exit 1
    fi
    chmod +x ._cmd
    export CACHED_EXECUTION=1
    ./._cmd "$@"
    EXIT_STATUS=$?
    rm -f ._cmd
    exit $EXIT_STATUS
fi

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

# 输出成功
success() {
    echo -e "${OK} ${GreenBG}$1${Font}"
}

# 输出警告
warning() {
    echo -e "${Warn} ${YellowBG}$1${Font}"
}

# 输出错误
error() {
    echo -e "${Error} ${RedBG}$1${Font}"
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
        echo "$(docker run $TTY_FLAG --rm nginx:alpine sh -c "date +%s%N | md5sum | cut -c 1-${lan}")"
    fi
}

# 重启php
restart_php() {
    local RES=`container_exec php "supervisorctl update php"`
    if [ -z "$RES" ]; then
        RES=`container_exec php "supervisorctl restart php"`
    fi
    local IN=`echo $RES | grep "ERROR"`
    if [[ "$IN" != "" ]]; then
        $COMPOSE stop php
        $COMPOSE start php
    else
        echo "$RES"
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

# 检查是否有sudo
check_sudo() {
    if [ "$EUID" -ne 0 ]; then
        error "请使用 sudo 运行此脚本"
        exit 1
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
    if [[ -n `$COMPOSE version | grep -E "\s+v1\."` ]]; then
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
web_build() {
    local type=$1
    check_node
    if [ ! -d "./node_modules" ]; then
        npm install
    fi
    if [ "$type" = "dev" ]; then
        echo "<script>window.location.href=window.location.href.replace(/:\d+/, ':' + $(env_get APP_PORT))</script>" > ./index.html
        if [ -z "$(env_get APP_DEV_PORT)" ]; then
            env_set APP_DEV_PORT $(rand 20001 30000)
        fi
        if [ -n "${VSCODE_PROXY_URI:-}" ]; then
            APP_REAL_URI=$(TARGET_PORT="$(env_get APP_PORT)" node -p "process.env.VSCODE_PROXY_URI.replace(/\{\{port\}\}/g, process.env.TARGET_PORT || '')")
            VSCODE_PROXY_URI=$(APP_DEV_PORT="$(env_get APP_DEV_PORT)" node -p "process.env.VSCODE_PROXY_URI.replace(/\{\{port\}\}/g, process.env.APP_DEV_PORT || '')")
            echo "<script>window.location.href='${APP_REAL_URI}'</script>" > ./index.html
        fi
        env_set VSCODE_PROXY_URI "${VSCODE_PROXY_URI:-}"
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
electron_operate() {
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
container_exec() {
    local container=$1
    shift 1
    local cmd=$@
    local name=$(docker_name "$container")
    if [ -z "$name" ]; then
        error "没有找到 ${container} 容器!"
        exit 1
    fi
    docker exec $TTY_FLAG "$name" /bin/sh -c "$cmd"
}

# 备份数据库、还原数据库
mysql_snapshot() {
    if [ "$1" = "backup" ]; then
        database=$(env_get DB_DATABASE)
        username=$(env_get DB_USERNAME)
        password=$(env_get DB_PASSWORD)
        # 备份数据库
        mkdir -p ${WORK_DIR}/docker/mysql/backup
        filename="${WORK_DIR}/docker/mysql/backup/${database}_$(date "+%Y%m%d%H%M%S").sql.gz"
        container_exec mariadb "exec mysqldump --databases $database -u${username} -p${password}" | gzip > $filename
        judge "备份数据库"
        [ -f "$filename" ] && echo "备份文件：${filename}"
    elif [ "$1" = "recovery" ]; then
        database=$(env_get DB_DATABASE)
        username=$(env_get DB_USERNAME)
        password=$(env_get DB_PASSWORD)
        # 还原数据库
        mkdir -p ${WORK_DIR}/docker/mysql/backup
        shopt -s nullglob
        backup_files=("${WORK_DIR}/docker/mysql/backup/"*.sql.gz)
        shopt -u nullglob
        if [ ${#backup_files[@]} -eq 0 ]; then
            error "没有备份文件！"
            exit 1
        fi
        echo "可用备份列表："
        for idx in "${!backup_files[@]}"; do
            printf "%2d) %s\n" "$((idx + 1))" "$(basename "${backup_files[$idx]}")"
        done
        while true; do
            read -rp "请输入备份文件编号还原：" selection
            if [[ "$selection" =~ ^[0-9]+$ ]] && [ "$selection" -ge 1 ] && [ "$selection" -le ${#backup_files[@]} ]; then
                break
            fi
            warning "编号无效，请重新输入。"
        done
        filename="${backup_files[$((selection - 1))]}"
        inputname="$(basename "$filename")"
        container_name=`docker_name mariadb`
        if [ -z "$container_name" ]; then
            error "没有找到 mariadb 容器!"
            exit 1
        fi
        docker cp "$filename" "${container_name}:/"
        container_exec mariadb "gunzip < '/${inputname}' | mysql -u${username} -p${password} $database"
        container_exec php "php artisan migrate"
        judge "还原数据库"
    fi
}

# 根据网络名称删除所有容器
remove_by_network() {
    local app_id=$(env_get APP_ID)
    local network_name="dootask-networks-${app_id}"

    # 批量删除所有状态的容器（包括已停止的）
    local container_ids=$(docker ps -aq --filter network="$network_name")
    if [ -n "$container_ids" ]; then
        echo "$container_ids" | xargs -r docker rm -f 1>/dev/null
    fi

    # 等待网络完全清空（最多等待10秒）
    local retry=0
    while [ $retry -lt 10 ]; do
        local count=$(docker network inspect "$network_name" --format '{{len .Containers}}' 2>/dev/null | tr -d '[:space:]')
        if [ -z "$count" ] || [ "$count" = "0" ]; then
            break
        fi
        sleep 1
        ((retry++))
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
    docker run $TTY_FLAG --rm -v $(pwd):/work nginx:alpine sh /work/bin/https install
    if [[ 0 -eq $? ]]; then
        container_exec nginx "nginx -s reload"
    fi
    new_job="* 6 * * * docker run --rm -v $(pwd):/work nginx:alpine sh /work/bin/https renew"
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
    local value=`cat ${WORK_DIR}/.env | grep "^$key=" | awk -F '=' '{print $2}' | tr -d '\r\n'`
    echo "$value"
}

# 设置env参数
env_set() {
    local key=$1
    local val=$2
    local exist=`cat ${WORK_DIR}/.env | grep "^$key="`
    if [ -z "$exist" ]; then
        echo "$key=$val" >> $WORK_DIR/.env
    else
        if [[ `uname` == 'Linux' ]]; then
            sed -i "/^${key}=/c\\${key}=${val}" ${WORK_DIR}/.env
        else
            docker run $TTY_FLAG --rm -v ${WORK_DIR}:/www nginx:alpine sh -c "sed -i "/^${key}=/c\\${key}=${val}" /www/.env"
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
    if [ -z "$(env_get UPDATE_TIME)" ]; then
        env_set DB_HOST "mariadb"
        env_set REDIS_HOST "redis"
        docker run $TTY_FLAG --rm -v ${WORK_DIR}:/www nginx:alpine sh -c "sed -i 's|/etc/nginx/conf.d/site/|/var/www/docker/nginx/site/|g' /www/docker/nginx/site/*.conf &> /dev/null"
    fi
}

# 获取命令参数
arg_get() {
    local find="n"
    local value=""
    for var in $INPUT_ARGS; do
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
handle_install() {
    check_sudo
    
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
        tmp_path="${WORK_DIR}/${vol}"
        mkdir -p "${tmp_path}"
        find "${tmp_path}" -type d -exec chmod 775 {} \;

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
    exec_judge "container_exec php 'composer install --optimize-autoloader'" "安装依赖失败"

    # 最终检查
    if [ ! -f "${WORK_DIR}/vendor/autoload.php" ]; then
        error "安装依赖失败，请重试！"
        exit 1
    fi

    # 生成应用密钥
    [[ -z "$(env_get APP_KEY)" ]] && exec_judge "container_exec php 'php artisan key:generate'" "生成密钥失败"

    # 设置生产模式
    switch_debug "false"

    # 数据库迁移
    exec_judge "container_exec php 'php artisan migrate --seed'" "数据库迁移失败"

    # 启动所有容器
    $COMPOSE up -d --remove-orphans

    success "安装完成"
    echo -e "地址: http://${GreenBG}127.0.0.1:$(env_get APP_PORT)${Font}"
    container_exec mariadb "sh /etc/mysql/repassword.sh"
}

# 更新函数
handle_update() {
    check_sudo

    local target_branch=$(arg_get branch)
    local is_local=$(arg_get local)
    local force_update=$(arg_get force)

    # 检查是否已经安装
    if [ ! -f "${WORK_DIR}/vendor/autoload.php" ]; then
        error "请先执行安装命令"
        exit 1
    fi

    # 尝试确定php容器启动
    if [ -z "$(docker_name php)" ]; then
        $COMPOSE start php
    fi

    if [[ -z "$is_local" ]]; then
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

        # 远程更新模式
        exec_judge "git fetch --all" "获取远程更新失败"

        # 确定目标分支
        if [[ -n "$target_branch" ]]; then
            current_branch="$target_branch"
            if ! git config --get "branch.${current_branch}.remote" | grep -q "origin"; then
                exec_judge "git config remote.origin.fetch '+refs/heads/*:refs/remotes/origin/*'" "设置远程Fetch配置失败"
            fi
            if ! git show-ref --verify --quiet refs/heads/${current_branch}; then
                exec_judge "git fetch origin ${current_branch}:${current_branch}" "获取远程分支 ${current_branch} 失败"
            fi
            if [[ "$force_update" == "yes" ]]; then
                exec_judge "git checkout -f ${current_branch}" "切换分支到 ${current_branch} 失败"
            else
                exec_judge "git checkout ${current_branch}" "切换分支到 ${current_branch} 失败"
            fi
        else
            current_branch=$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
        fi

        # 检查数据库迁移变动
        db_changes=$(git diff --name-only HEAD..origin/${current_branch} 2>/dev/null | grep -E "^database/" || true)
        if [[ -n "$db_changes" ]]; then
            echo "数据库有迁移变动，执行数据库备份..."
            exec_judge "mysql_snapshot backup" "数据库备份失败" "数据库备份完成"
        fi

        # 更新代码
        if [[ "$force_update" == "yes" ]]; then
            exec_judge "git reset --hard origin/${current_branch}" "强制更新代码失败"
        else
            exec_judge "git pull --ff-only origin ${current_branch}" "代码拉取失败，可能存在冲突，请使用 --force 参数"
        fi

        # 更新依赖
        exec_judge "container_exec php 'composer install --optimize-autoloader'" "更新PHP依赖失败"
    else
        # 本地更新模式
        echo "执行数据库备份..."
        exec_judge "mysql_snapshot backup" "数据库备份失败" "数据库备份完成"
    fi

    # 数据库迁移
    exec_judge "container_exec php 'php artisan migrate'" "数据库迁移失败"

    # 停止服务
    $COMPOSE stop php nginx &> /dev/null
    $COMPOSE rm -f php nginx &> /dev/null

    # 启动服务
    $COMPOSE up -d --remove-orphans
    if [[ 0 -ne $? ]]; then
        $COMPOSE down --remove-orphans
        exec_judge "$COMPOSE up -d" "重启服务失败"
    fi

    env_set UPDATE_TIME "$(date +%s)"
    success "更新完成"
}

# 卸载函数
handle_uninstall() {
    check_sudo
    # 确认卸载
    echo -e "${RedBG}警告：此操作将永久删除以下内容：${Font}"
    echo "- 数据库"
    echo "- 应用程序"
    echo "- 日志文件"
    echo ""
    read -rp "确认要继续卸载吗？(y/N): " confirm_uninstall
    [[ -z ${confirm_uninstall} ]] && confirm_uninstall="N"
    case $confirm_uninstall in
    [yY][eE][sS] | [yY])
        echo -e "${RedBG}开始卸载...${Font}"
        ;;
    *)
        echo -e "${GreenBG}终止卸载。${Font}"
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
case "$1" in
    "install")
        shift 1
        handle_install
        ;;
    "update")
        shift 1
        handle_update
        ;;
    "uninstall")
        shift 1
        handle_uninstall
        ;;
    "port")
        shift 1
        env_set APP_PORT "$1"
        $COMPOSE up -d
        success "修改成功"
        echo -e "地址: http://${GreenBG}127.0.0.1:$(env_get APP_PORT)${Font}"
        ;;
    "url")
        shift 1
        env_set APP_URL "$1"
        restart_php
        success "修改成功"
        ;;
    "env")
        shift 1
        if [ -n "$1" ]; then
            env_set $1 "$2"
        fi
        restart_php
        success "修改成功"
        ;;
    "repassword")
        shift 1
        container_exec mariadb "sh /etc/mysql/repassword.sh $@"
        ;;
    "serve"|"dev")
        shift 1
        web_build dev
        ;;
    "build"|"prod")
        shift 1
        web_build prod
        ;;
    "appbuild"|"buildapp")
        shift 1
        electron_operate app "$@"
        ;;
    "electron")
        shift 1
        electron_operate "$@"
        ;;
    "eeui")
        shift 1
        cli="$@"
        por=""
        if [[ "$cli" == "build" ]]; then
            cli="build --simple"
        elif [[ "$cli" == "dev" ]]; then
            por="-p 8880:8880"
        fi
        docker run $TTY_FLAG --rm -v ${WORK_DIR}/resources/mobile:/work -w /work ${por} kuaifan/eeui-cli:0.0.1 eeui ${cli}
        ;;
    "npm")
        shift 1
        npm "$@"
        pushd electron || exit
        npm "$@"
        popd || exit
        docker run $TTY_FLAG --rm -v ${WORK_DIR}/resources/mobile:/work -w /work --entrypoint=/bin/bash node:16 -c "npm $@"
        ;;
    "doc")
        shift 1
        container_exec php "php app/Http/Controllers/Api/apidoc.php"
        docker run $TTY_FLAG --rm -v ${WORK_DIR}:/home/node/apidoc kuaifan/apidoc -i app/Http/Controllers/Api -o public/docs
        container_exec php "php app/Http/Controllers/Api/apidoc.php restore"
        ;;
    "debug")
        shift 1
        switch_debug "$@"
        echo "success"
        ;;
    "https")
        shift 1
        if [[ "$1" == "agent" ]] || [[ "$1" == "true" ]]; then
            env_set APP_SCHEME "true"
        elif [[ "$1" == "close" ]] || [[ "$1" == "auto" ]]; then
            env_set APP_SCHEME "auto"
        else
            https_auto
        fi
        restart_php
        ;;
    "artisan")
        shift 1
        e="php artisan $@" && container_exec php "$e"
        ;;
    "php")
        shift 1
        if [[ "$1" == "restart" ]] || [[ "$1" == "reboot" ]]; then
            restart_php
        else
            e="php $@" && container_exec php "$e"
        fi
        ;;
    "nginx")
        shift 1
        e="nginx $@" && container_exec nginx "$e"
        ;;
    "redis")
        shift 1
        e="redis $@" && container_exec redis "$e"
        ;;
    "mysql")
        shift 1
        if [[ "$1" == "backup" ]] || [[ "$1" == "b" ]]; then
            mysql_snapshot backup
        elif [[ "$1" == "recovery" ]] || [[ "$1" == "r" ]]; then
            mysql_snapshot recovery
        else
            e="mysql $@" && container_exec mariadb "$e"
        fi
        ;;
    "composer")
        shift 1
        e="composer $@" && container_exec php "$e"
        ;;
    "service")
        shift 1
        e="service $@" && container_exec php "$e"
        ;;
    "super"|"supervisorctl")
        shift 1
        e="supervisorctl $@" && container_exec php "$e"
        ;;
    "models")
        shift 1
        container_exec php "php app/Models/clearHelper.php"
        container_exec php "php artisan ide-helper:models -W"
        ;;
    "translate")
        shift 1
        container_exec php "cd /var/www/language && php translate.php"
        ;;
    "restart")
        shift 1
        $COMPOSE stop "$@"
        $COMPOSE start "$@"
        ;;
    "reup")
        shift 1
        remove_by_network
        $COMPOSE down --remove-orphans
        $COMPOSE up -d
        ;;
    "down")
        shift 1
        remove_by_network
        if [[ $# -eq 0 ]]; then
            $COMPOSE down --remove-orphans
        else
            $COMPOSE down "$@"
        fi
        ;;
    "up")
        shift 1
        if [[ $# -eq 0 ]]; then
            $COMPOSE up -d --remove-orphans
        else
            $COMPOSE up "$@"
        fi
        ;;
    *)
        $COMPOSE "$@"
        ;;
esac
