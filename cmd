#!/bin/bash

# 颜色
Green="\033[32m"
Yellow="\033[33m"
Red="\033[31m"
GreenBG="\033[42;37m"
YellowBG="\033[43;37m"
RedBG="\033[41;37m"
Font="\033[0m"

# 语言判定：默认英文；仅当 locale 明确是「中文 UTF-8」时才用中文（中文非 UTF-8 如 GBK 也用英文以免乱码）。
DT_LANG="en"
__loc="${LC_ALL:-${LC_MESSAGES:-${LANG:-}}}"
case "$__loc" in
    zh_*|zh-*|zh)
        case "$__loc" in
            *[Uu][Tt][Ff]*) DT_LANG="zh" ;;
            *.*)            DT_LANG="en" ;;
            *)              DT_LANG="zh" ;;
        esac
        ;;
esac
unset __loc

# 文案：调用处只写中文（动态值用 (*) 占位，顺序对应后续参数）。
# 中文环境直接用原文；英文环境在此集中查表翻译，未登记的中文原样返回。
msg() {
    local tpl="$1"; shift
    local out="$tpl"
    if [ "$DT_LANG" != "zh" ]; then
        case "$tpl" in
            "警告") out="WARN" ;;
            "错误") out="ERROR" ;;
            "地址") out="URL" ;;
            "(*) 完成") out="(*) done" ;;
            "(*) 失败") out="(*) failed" ;;
            "备份数据库") out="Backing up database" ;;
            "还原数据库") out="Restoring database" ;;
            "无法创建脚本副本") out="Failed to create script copy" ;;
            "没有找到 (*) 容器!") out="Container (*) not found!" ;;
            "请使用 sudo 运行此脚本") out="Please run this script with sudo" ;;
            "未安装 Docker！") out="Docker is not installed!" ;;
            "未安装 Docker-compose！") out="Docker-compose is not installed!" ;;
            "Docker-compose 版本过低，请升级至v2+！") out="Docker-compose is too old. Please upgrade to v2+!" ;;
            "未安装 npm！") out="npm is not installed!" ;;
            "未安装 Node.js！") out="Node.js is not installed!" ;;
            "Node.js 版本过低，请升级至v20+！") out="Node.js is too old. Please upgrade to v20+!" ;;
            "备份文件：(*)") out="Backup file: (*)" ;;
            "没有备份文件！") out="No backup files found!" ;;
            "可用备份列表：") out="Available backups:" ;;
            "请输入备份文件编号还原：") out="Enter the backup number to restore: " ;;
            "编号无效，请重新输入。") out="Invalid number, please try again." ;;
            "HTTP服务端口不是80，是否修改并继续操作？ [Y/n]") out="HTTP port is not 80. Change it and continue? [Y/n]" ;;
            "HTTPS服务端口不是443，是否修改并继续操作？ [Y/n]") out="HTTPS port is not 443. Change it and continue? [Y/n]" ;;
            "继续操作") out="Continuing" ;;
            "操作终止") out="Operation aborted" ;;
            "任务已存在，无需添加。") out="Cron job already exists, skipped." ;;
            "任务已添加。") out="Cron job added." ;;
            "设置env参数失败！") out="Failed to set env variable!" ;;
            "APP_ID（(*)）已被其他实例使用：(*)") out="APP_ID ((*)) is already used by another instance: (*)" ;;
            "请先清空 .env 中的 APP_ID 和 APP_IPPR 再重新安装") out="Please clear APP_ID and APP_IPPR in .env, then reinstall" ;;
            "端口 (*) 已被占用，请指定其他端口") out="Port (*) is already in use, please specify another port" ;;
            "目录权限检测失败！请检查目录权限设置") out="Directory permission check failed! Please check directory permissions" ;;
            "目录【(*)】权限不足！") out="Directory [(*)] is not writable!" ;;
            "项目权限修复失败") out="Failed to repair project permissions" ;;
            "项目权限修复完成") out="Project permissions repaired" ;;
            "安装依赖失败") out="Failed to install dependencies" ;;
            "安装依赖失败，请重试！") out="Failed to install dependencies, please retry!" ;;
            "生成密钥失败") out="Failed to generate app key" ;;
            "数据库迁移失败") out="Database migration failed" ;;
            "安装完成") out="Installation complete" ;;
            "请先执行安装命令") out="Please run the install command first" ;;
            "检测到本地修改，是否强制更新？[Y/n]") out="Local changes detected. Force update? [Y/n]" ;;
            "取消更新，请先处理本地修改") out="Update cancelled, please handle local changes first" ;;
            "获取远程更新失败") out="Failed to fetch remote updates" ;;
            "设置远程Fetch配置失败") out="Failed to set remote fetch config" ;;
            "获取远程分支 (*) 失败") out="Failed to fetch remote branch (*)" ;;
            "切换分支到 (*) 失败") out="Failed to switch to branch (*)" ;;
            "数据库有迁移变动，执行数据库备份...") out="Database migrations changed, backing up database..." ;;
            "数据库备份失败") out="Database backup failed" ;;
            "数据库备份完成") out="Database backup complete" ;;
            "强制更新代码失败") out="Failed to force-update code" ;;
            "代码拉取失败，可能存在冲突，请使用 --force 参数") out="Failed to pull code (possible conflict), please use --force" ;;
            "更新PHP依赖失败") out="Failed to update PHP dependencies" ;;
            "执行数据库备份...") out="Backing up database..." ;;
            "重启服务失败") out="Failed to restart services" ;;
            "检测到本地修改，已中止在线更新，请在服务器处理后重试") out="Local changes detected, online update aborted. Please resolve them on the server and retry" ;;
            "服务未能在规定时间内恢复") out="Services did not recover in time" ;;
            "更新完成") out="Update complete" ;;
            "警告：此操作将永久删除以下内容：") out="WARNING: This will permanently delete:" ;;
            "- 数据库") out="- Database" ;;
            "- 应用程序") out="- Application" ;;
            "- 日志文件") out="- Log files" ;;
            "确认要继续卸载吗？(y/N): ") out="Confirm uninstall? (y/N): " ;;
            "开始卸载...") out="Uninstalling..." ;;
            "终止卸载。") out="Uninstall aborted." ;;
            "卸载完成") out="Uninstall complete" ;;
            "修改成功") out="Changed successfully" ;;
        esac
    fi
    # 动态值：依次把 (*) 替换为参数
    local a
    for a in "$@"; do
        out="${out/(\*)/$a}"
    done
    printf '%s' "$out"
}

# 通知信息
OK="${Green}[OK]${Font}"
Warn="${Yellow}[$(msg 警告)]${Font}"
Error="${Red}[$(msg 错误)]${Font}"

# 基本参数
WORK_DIR="$(pwd)"
INPUT_ARGS=$@
COMPOSE="docker-compose"

# 项目运行时需要写入并检查权限的目录
PROJECT_WRITABLE_DIRS=(
    "bootstrap/cache"
    "docker"
    "public"
    "storage"
)

# TTY 参数检测
TTY_FLAG=""
if [ -t 0 ] && [ -t 1 ]; then
    TTY_FLAG="-it"
fi

# 缓存执行
if [ -z "$CACHED_EXECUTION" ] && [ "$1" == "update" ] && [[ " $* " != *" --check "* ]]; then
    if ! cat "$0" > ._cmd 2>/dev/null; then
        error "$(msg '无法创建脚本副本')"
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
        success "$(msg '(*) 完成' "$1")"
        sleep 1
    else
        error "$(msg '(*) 失败' "$1")"
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
    UPDATE_LAST_ERROR="$1"
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
        error "$(msg '请使用 sudo 运行此脚本')"
        exit 1
    fi
}

# 修复项目权限
permission_fix() {
    # 确定可写目录的归属用户，sudo 执行时恢复为原调用用户。
    local owner_uid="${SUDO_UID:-$(id -u)}"
    local owner_gid="${SUDO_GID:-$(id -g)}"

    # 项目根目录需要允许服务进程穿越。
    chmod 755 "${WORK_DIR}"
    if [ $? -ne 0 ]; then
        error "$(msg '项目权限修复失败')"
        exit 1
    fi

    # 可写目录归还给调用用户，并统一补充组写入权限。
    for vol in "${PROJECT_WRITABLE_DIRS[@]}"; do
        tmp_path="${WORK_DIR}/${vol}"
        mkdir -p "${tmp_path}"
        if [ $? -ne 0 ]; then
            error "$(msg '项目权限修复失败')"
            exit 1
        fi
        chown -R "${owner_uid}:${owner_gid}" "${tmp_path}"
        if [ $? -ne 0 ]; then
            error "$(msg '项目权限修复失败')"
            exit 1
        fi
        find "${tmp_path}" -type d -exec chmod 775 {} \;
        if [ $? -ne 0 ]; then
            error "$(msg '项目权限修复失败')"
            exit 1
        fi
    done

    # public 中的静态文件需要允许 Web 服务读取。
    find "${WORK_DIR}/public" -type f -exec chmod a+r {} \;
    if [ $? -ne 0 ]; then
        error "$(msg '项目权限修复失败')"
        exit 1
    fi

    # AI 容器以非 root 用户只读挂载主程序知识库。
    local kb_path="${WORK_DIR}/resources/ai-kb"
    if [[ -d "${kb_path}" ]]; then
        find "${kb_path}" -type d -exec chmod a+rx {} \;
        if [ $? -ne 0 ]; then
            error "$(msg '项目权限修复失败')"
            exit 1
        fi
        find "${kb_path}" -type f -exec chmod a+r {} \;
        if [ $? -ne 0 ]; then
            error "$(msg '项目权限修复失败')"
            exit 1
        fi
    fi

    # 应用知识库由 config.yml 声明并统一放在 ai-kb 目录；不修改应用运行数据文件。
    local appstore_apps_path="${WORK_DIR}/docker/appstore/apps"
    if [[ -d "${appstore_apps_path}" ]]; then
        find "${appstore_apps_path}" -type f -name "config.yml" -exec chmod a+r {} \;
        if [ $? -ne 0 ]; then
            error "$(msg '项目权限修复失败')"
            exit 1
        fi
        find "${appstore_apps_path}" -type f -path "*/ai-kb/*" -name "*.md" -exec chmod a+r {} \;
        if [ $? -ne 0 ]; then
            error "$(msg '项目权限修复失败')"
            exit 1
        fi
    fi
}

# 检查docker、docker-compose
check_docker() {
    docker --version &> /dev/null
    if [ $? -ne  0 ]; then
        error "$(msg '未安装 Docker！')"
        exit 1
    fi
    docker-compose version &> /dev/null
    if [ $? -ne  0 ]; then
        docker compose version &> /dev/null
        if [ $? -ne  0 ]; then
            error "$(msg '未安装 Docker-compose！')"
            exit 1
        fi
        COMPOSE="docker compose"
    fi
    if [[ -n `$COMPOSE version | grep -E "\s+v1\."` ]]; then
        $COMPOSE version
        error "$(msg 'Docker-compose 版本过低，请升级至v2+！')"
        exit 1
    fi
}

# 检查node
check_node() {
    npm --version &> /dev/null
    if [ $? -ne  0 ]; then
        error "$(msg '未安装 npm！')"
        exit 1
    fi
    node --version &> /dev/null
    if [ $? -ne  0 ]; then
        error "$(msg '未安装 Node.js！')"
        exit 1
    fi
    if [[ -n `node --version | grep -E "v1"` ]]; then
        node --version
        error "$(msg 'Node.js 版本过低，请升级至v20+！')"
        exit 1
    fi
}

# 获取容器名称
docker_name() {
    echo `$COMPOSE ps | awk '{print $1}' | grep "\-$1\-"`
}

# 等待 php 容器健康（最多约 90s）
wait_php_healthy() {
    local name st wait=0
    name="$(docker_name php)"
    [ -z "$name" ] && return 0
    while [ $wait -lt 90 ]; do
        st="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$name" 2>/dev/null)"
        { [ "$st" = "healthy" ] || [ "$st" = "running" ]; } && return 0
        sleep 3
        wait=$((wait + 3))
    done
    return 1
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

# Expo 内测：同 electron 使用本机 Node，不依赖 Docker 或旧 eeui 容器。
appexpo_operate() {
    check_node
    case "${1:-build}" in
        "build")
            if [[ $# -gt 1 ]]; then
                error "用法: ./cmd appexpo [build] | ./cmd appexpo npm <参数...>"
                return 1
            fi
            if [ ! -d "./node_modules" ]; then
                npm install --no-package-lock || return $?
            fi
            if [ ! -d "./electron/node_modules" ]; then
                (cd electron && ELECTRON_SKIP_BINARY_DOWNLOAD=1 npm install --no-package-lock) || return $?
            fi
            node ./electron/build.js app-expo build
            ;;
        "npm")
            shift
            (cd "${WORK_DIR}/resources/app" && npm "$@")
            ;;
        *)
            error "用法: ./cmd appexpo [build] | ./cmd appexpo npm <参数...>"
            return 1
            ;;
    esac
}

# 执行容器命令
container_exec() {
    local container=$1
    shift 1
    local cmd=$@
    local name=$(docker_name "$container")
    if [ -z "$name" ]; then
        error "$(msg '没有找到 (*) 容器!' "$container")"
        exit 1
    fi
    docker exec $TTY_FLAG "$name" /bin/sh -c "$cmd"
}

# 使用当前 docker-compose.yml 定义的服务镜像执行一次性容器命令
container_run() {
    local container=$1
    shift 1
    local cmd=$@
    if [ -t 0 ] && [ -t 1 ]; then
        $COMPOSE run --rm --entrypoint /bin/sh "$container" -c "$cmd"
    else
        $COMPOSE run --rm -T --entrypoint /bin/sh "$container" -c "$cmd"
    fi
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
        judge "$(msg '备份数据库')"
        [ -f "$filename" ] && echo "$(msg '备份文件：(*)' "$filename")"
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
            error "$(msg '没有备份文件！')"
            exit 1
        fi
        echo "$(msg '可用备份列表：')"
        for idx in "${!backup_files[@]}"; do
            printf "%2d) %s\n" "$((idx + 1))" "$(basename "${backup_files[$idx]}")"
        done
        while true; do
            read -rp "$(msg '请输入备份文件编号还原：')" selection
            if [[ "$selection" =~ ^[0-9]+$ ]] && [ "$selection" -ge 1 ] && [ "$selection" -le ${#backup_files[@]} ]; then
                break
            fi
            warning "$(msg '编号无效，请重新输入。')"
        done
        filename="${backup_files[$((selection - 1))]}"
        inputname="$(basename "$filename")"
        container_name=`docker_name mariadb`
        if [ -z "$container_name" ]; then
            error "$(msg '没有找到 (*) 容器!' mariadb)"
            exit 1
        fi
        docker cp "$filename" "${container_name}:/"
        container_exec mariadb "gunzip < '/${inputname}' | mysql -u${username} -p${password} $database"
        container_exec php "php artisan migrate"
        judge "$(msg '还原数据库')"
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
        warning "$(msg 'HTTP服务端口不是80，是否修改并继续操作？ [Y/n]')"
        read -r continue_http
        [[ -z ${continue_http} ]] && continue_http="Y"
        case $continue_http in
        [yY][eE][sS] | [yY])
            success "$(msg '继续操作')"
            env_set "APP_PORT" "80"
            restart_nginx="y"
            ;;
        *)
            error "$(msg '操作终止')"
            exit 1
            ;;
        esac
    fi
    if [[ "$(env_get APP_SSL_PORT)" != "443" ]]; then
        warning "$(msg 'HTTPS服务端口不是443，是否修改并继续操作？ [Y/n]')"
        read -r continue_https
        [[ -z ${continue_https} ]] && continue_https="Y"
        case $continue_https in
        [yY][eE][sS] | [yY])
            success "$(msg '继续操作')"
            env_set "APP_SSL_PORT" "443"
            restart_nginx="y"
            ;;
        *)
            error "$(msg '操作终止')"
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
        echo "$(msg '任务已存在，无需添加。')"
    else
        crontab -l |{
            cat
            echo "$new_job"
        } | crontab -
        echo "$(msg '任务已添加。')"
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
        echo "" >> $WORK_DIR/.env
        echo "$key=$val" >> $WORK_DIR/.env
    else
        # 值未变化则直接返回，避免无谓重写 .env（重写会改 mtime，触发 vite 全量重启/前端刷新）
        if [[ "$(env_get "$key")" == "$val" ]]; then
            return 0
        fi
        if [[ `uname` == 'Linux' ]]; then
            sed -i "/^${key}=/c\\${key}=${val}" ${WORK_DIR}/.env
        else
            docker run $TTY_FLAG --rm -v ${WORK_DIR}:/www nginx:alpine sh -c "sed -i "/^${key}=/c\\${key}=${val}" /www/.env"
        fi
        if [ $? -ne  0 ]; then
            error "$(msg '设置env参数失败！')"
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
    if [ "$DT_LANG" = "zh" ]; then
        cat << 'EOF'
DooTask 管理脚本

用法: ./cmd <命令> [参数]

📦 核心操作:
  install                     安装 DooTask (支持 --port <端口> --relock)
  update                      更新 DooTask (支持 --branch <分支> --force --local --check --non-interactive)
  uninstall                   卸载 DooTask
  permission                  修复整个项目的权限

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
  appexpo [build]             编译并同步 Expo 内测前端
  appexpo npm <参数...>       在 Expo 子模块执行本机 npm

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
  ./cmd permission            修复整个项目的权限
  ./cmd mysql backup          备份数据库
  ./cmd artisan migrate       执行数据库迁移
EOF
    else
        cat << 'EOF'
DooTask Management Script

Usage: ./cmd <command> [options]

📦 Core:
  install                     Install DooTask (supports --port <port> --relock)
  update                      Update DooTask (supports --branch <branch> --force --local --check --non-interactive)
  uninstall                   Uninstall DooTask
  permission                  Repair permissions for the whole project

⚙️  Configuration:
  port <port>                 Change service port
  url <address>               Change access URL
  env <key> <value>           Set environment variable
  debug [true|false]          Toggle debug mode
  repassword [username]       Reset database password

🚀 Build:
  serve, dev                  Start dev mode
  build, prod                 Production build
  electron                    Build desktop app
  appexpo [build]             Build and copy Expo internal web assets
  appexpo npm <args...>       Run local npm in the Expo submodule

🔧 Services:
  up [service]                Start containers
  down [service]              Stop containers
  restart [service]           Restart containers
  reup                        Rebuild and start

💾 Database:
  mysql backup                Back up database
  mysql recovery              Restore database

🛠️  Dev tools:
  artisan <command>           Run Laravel Artisan command
  composer <command>          Run Composer command
  php <command>               Run PHP command

📚 Others:
  doc                         Generate API docs
  https                       Configure HTTPS
  --help, -h                  Show this help

Examples:
  ./cmd install --port 8080   Install on port 8080
  ./cmd update --branch dev   Switch to dev branch and update
  ./cmd permission            Repair permissions for the whole project
  ./cmd mysql backup          Back up database
  ./cmd artisan migrate       Run database migration
EOF
    fi
}

# 检测APP_ID是否与其他实例冲突
check_instance() {
    local app_id=$(env_get APP_ID)
    local container_name="dootask-php-${app_id}"
    local mount_path=$(docker inspect "$container_name" --format '{{range .Mounts}}{{if eq .Destination "/var/www"}}{{.Source}}{{end}}{{end}}' 2>/dev/null)
    if [[ -n "$mount_path" ]] && [[ "$mount_path" != "$WORK_DIR" ]]; then
        error "$(msg 'APP_ID（(*)）已被其他实例使用：(*)' "$app_id" "$mount_path")"
        error "$(msg '请先清空 .env 中的 APP_ID 和 APP_IPPR 再重新安装')"
        exit 1
    fi
}

# 检测端口是否被占用
# 参数1: 端口号, 参数2: 当前端口号（可选，相同则跳过检测）
check_port() {
    local port=$1
    local current_port=$2
    if [[ "$port" -gt 0 ]] && [[ "$port" != "$current_port" ]]; then
        if ! docker run --rm -p "${port}:80" --entrypoint true nginx:alpine 2>/dev/null; then
            error "$(msg '端口 (*) 已被占用，请指定其他端口' "$port")"
            exit 1
        fi
    fi
}

# 安装函数
handle_install() {
    check_sudo
    check_instance

    local relock=$(arg_get relock)
    local port=$(arg_get port)

    # 初始化文件
    if [[ -n "$relock" ]]; then
        rm -rf node_modules package-lock.json vendor composer.lock
    fi

    # 目录和静态文件权限设置
    permission_fix
    cmda=""
    cmdb=""
    for vol in "${PROJECT_WRITABLE_DIRS[@]}"; do
        tmp_path="${WORK_DIR}/${vol}"
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
            error "$(msg '目录权限检测失败！请检查目录权限设置')"
            exit 1
        fi
        for vol in "${PROJECT_WRITABLE_DIRS[@]}"; do
            if [ ! -f "${vol}/dootask.lock" ]; then
                if [ $remaining -lt 0 ]; then
                    error "$(msg '目录【(*)】权限不足！' "$vol")"
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
    local old_port=$(env_get APP_PORT)
    [[ "$port" -gt 0 ]] && env_set APP_PORT "$port"

    # 检测端口占用（首次安装或端口变更时）
    local new_port=$(env_get APP_PORT)
    if [ -z "$(docker_name nginx)" ] || [[ "$new_port" != "$old_port" ]]; then
        check_port "$new_port"
        local ssl_port=$(env_get APP_SSL_PORT)
        check_port "$ssl_port"
    fi

    # 启动PHP容器
    $COMPOSE up php -d

    # 安装PHP依赖
    exec_judge "container_exec php 'composer install --optimize-autoloader'" "$(msg '安装依赖失败')"

    # 最终检查
    if [ ! -f "${WORK_DIR}/vendor/autoload.php" ]; then
        error "$(msg '安装依赖失败，请重试！')"
        exit 1
    fi

    # 生成应用密钥
    [[ -z "$(env_get APP_KEY)" ]] && exec_judge "container_exec php 'php artisan key:generate'" "$(msg '生成密钥失败')"

    # 设置生产模式
    switch_debug "false"

    # 数据库迁移
    exec_judge "container_exec php 'php artisan migrate --seed'" "$(msg '数据库迁移失败')"

    # 启动所有容器
    $COMPOSE up -d --remove-orphans

    # 兜底拉起 nginx（避免首启时序竞态）
    wait_php_healthy
    [ -z "$(docker_name nginx)" ] && $COMPOSE up -d --remove-orphans

    success "$(msg '安装完成')"
    echo -e "$(msg '地址'): http://${GreenBG}127.0.0.1:$(env_get APP_PORT)${Font}"
    container_exec mariadb "sh /etc/mysql/repassword.sh"
}

# 信任项目目录，避免 git 归属检查拦截（sudo / 容器内以 root 执行时）
git_trust_workdir() {
    git config --global --get-all safe.directory 2>/dev/null | grep -qxF "${WORK_DIR}" \
        || git config --global --add safe.directory "${WORK_DIR}" 2>/dev/null
}

# 读取 package.json 中的版本号（从标准输入读取）
pkg_version() {
    grep -m1 '"version"' | sed -E 's/.*"version"[^"]*"([^"]+)".*/\1/'
}

# JSON 字符串转义（单行）
json_str() {
    printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g' | tr -d '\r' | tr '\n\t' '  '
}

# 在线更新状态文件（仅 --non-interactive 模式写入，供 Web 端轮询进度）
UPDATE_STATE_FILE=""
UPDATE_LAST_ERROR=""
US_STATUS=""
US_STAGE=""
US_FROM_VERSION=""
US_TO_VERSION=""
US_FROM_COMMIT=""
US_TO_COMMIT=""
US_BACKUP=""
US_STARTED=""
US_FINISHED=0
US_ERROR=""

update_state_write() {
    [[ -z "$UPDATE_STATE_FILE" ]] && return 0
    local tmp="${UPDATE_STATE_FILE}.tmp"
    printf '{"id":"%s","status":"%s","stage":"%s","from_version":"%s","to_version":"%s","from_commit":"%s","to_commit":"%s","backup_file":"%s","operator":%d,"log":"%s","started_at":%d,"finished_at":%d,"error":"%s"}\n' \
        "$(json_str "${UPDATE_ID}")" "$US_STATUS" "$US_STAGE" \
        "$(json_str "$US_FROM_VERSION")" "$(json_str "$US_TO_VERSION")" \
        "$US_FROM_COMMIT" "$US_TO_COMMIT" "$(json_str "$US_BACKUP")" \
        "${UPDATE_OPERATOR:-0}" "$(json_str "${UPDATE_LOG}")" \
        "$US_STARTED" "$US_FINISHED" "$(json_str "$US_ERROR")" > "$tmp" && mv -f "$tmp" "$UPDATE_STATE_FILE"
}

update_stage() {
    US_STAGE="$1"
    update_state_write
}

# 异常退出时（exec_judge 失败 exit 1 等）标记失败
update_state_exit() {
    if [[ "$US_STATUS" == "running" ]]; then
        US_STATUS="failed"
        US_ERROR="${UPDATE_LAST_ERROR:-unknown error}"
        US_FINISHED=$(date +%s)
        update_state_write
    fi
}

update_state_init() {
    mkdir -p "${WORK_DIR}/docker/logs/update"
    UPDATE_STATE_FILE="${WORK_DIR}/docker/logs/update/state.json"
    UPDATE_ID="${UPDATE_ID:-$(date +%Y%m%d%H%M%S)}"
    US_STATUS="running"
    US_STARTED=$(date +%s)
    US_FROM_VERSION="$(pkg_version < "${WORK_DIR}/package.json")"
    git_trust_workdir
    US_FROM_COMMIT="$(git rev-parse HEAD 2>/dev/null)"
    trap update_state_exit EXIT
    update_stage "preparing"
}

# 检查更新（只读：仅 git fetch，不改动工作区），结果以 JSON 输出到 stdout
update_check_json() {
    local reason="" branch="" current="" latest="" behind=0 local_changes=false db_changes=false changelog=""
    current="$(pkg_version < "${WORK_DIR}/package.json")"
    if [ ! -f "${WORK_DIR}/vendor/autoload.php" ]; then
        reason="not_installed"
    elif [ ! -d "${WORK_DIR}/.git" ]; then
        reason="no_git"
    else
        git_trust_workdir
        branch="$(git symbolic-ref --short -q HEAD)"
        if [ -z "$branch" ]; then
            reason="detached_head"
        elif ! git fetch origin "$branch" &> /dev/null; then
            reason="fetch_failed"
        else
            latest="$(git show "origin/${branch}:package.json" 2>/dev/null | pkg_version)"
            behind="$(git rev-list --count "HEAD..origin/${branch}" 2>/dev/null || echo 0)"
            if ! git diff --quiet || ! git diff --cached --quiet; then
                local_changes=true
            fi
            if [ -n "$(git diff --name-only "HEAD...origin/${branch}" -- database/ 2>/dev/null)" ]; then
                db_changes=true
            fi
            # 截取远端 CHANGELOG 中比当前版本新的段落
            changelog="$(git show "origin/${branch}:CHANGELOG.md" 2>/dev/null \
                | awk -v v="## [${current}]" 'index($0, v) == 1 { exit } /^## \[/ { started = 1 } started { print }' \
                | base64 | tr -d '\n')"
        fi
    fi
    printf '{"supported":%s,"reason":"%s","branch":"%s","current_version":"%s","latest_version":"%s","behind":%d,"has_local_changes":%s,"has_db_changes":%s,"changelog_base64":"%s"}\n' \
        "$([ -z "$reason" ] && echo true || echo false)" "$reason" "$(json_str "$branch")" \
        "$(json_str "$current")" "$(json_str "$latest")" "${behind:-0}" \
        "$local_changes" "$db_changes" "$changelog"
}

# 更新函数
handle_update() {
    check_sudo

    local target_branch=$(arg_get branch)
    local is_local=$(arg_get local)
    local force_update=$(arg_get force)
    local online_update=$(arg_get non-interactive)

    # 在线更新（Web 端发起）不能交互，凭据缺失时让 git 直接失败而不是等待输入
    if [[ "$(arg_get check)" == "yes" ]] || [[ "$online_update" == "yes" ]]; then
        export GIT_TERMINAL_PROMPT=0
        export GIT_SSH_COMMAND="ssh -o BatchMode=yes"
    fi

    if [[ "$(arg_get check)" == "yes" ]]; then
        update_check_json
        return 0
    fi

    if [[ "$online_update" == "yes" ]]; then
        # 在线更新只允许更新当前分支：不切分支、不强制覆盖、不走本地模式
        target_branch=""
        is_local=""
        force_update=""
        update_state_init
    fi

    # 检查是否已经安装
    if [ ! -f "${WORK_DIR}/vendor/autoload.php" ]; then
        error "$(msg '请先执行安装命令')"
        exit 1
    fi

    # 尝试确定php容器启动
    if [ -z "$(docker_name php)" ]; then
        $COMPOSE start php
    fi

    if [[ -z "$is_local" ]]; then
        git_trust_workdir
        # 检查本地修改
        if ! git diff --quiet || ! git diff --cached --quiet; then
            if [[ "$online_update" == "yes" ]]; then
                error "$(msg '检测到本地修改，已中止在线更新，请在服务器处理后重试')"
                exit 1
            fi
            if [[ "$force_update" != "yes" ]]; then
                warning "$(msg '检测到本地修改，是否强制更新？[Y/n]')"
                read -r confirm_force
                [[ -z ${confirm_force} ]] && confirm_force="Y"
                case $confirm_force in
                [yY][eE][sS] | [yY])
                    force_update="yes"
                    ;;
                *)
                    error "$(msg '取消更新，请先处理本地修改')"
                    exit 1
                    ;;
                esac
            fi
        fi

        # 远程更新模式
        update_stage "fetching"
        exec_judge "git fetch --all" "$(msg '获取远程更新失败')"

        # 确定目标分支
        if [[ -n "$target_branch" ]]; then
            current_branch="$target_branch"
            if ! git config --get "branch.${current_branch}.remote" | grep -q "origin"; then
                exec_judge "git config remote.origin.fetch '+refs/heads/*:refs/remotes/origin/*'" "$(msg '设置远程Fetch配置失败')"
            fi
            if ! git show-ref --verify --quiet refs/heads/${current_branch}; then
                exec_judge "git fetch origin ${current_branch}:${current_branch}" "$(msg '获取远程分支 (*) 失败' "$current_branch")"
            fi
            if [[ "$force_update" == "yes" ]]; then
                exec_judge "git checkout -f ${current_branch}" "$(msg '切换分支到 (*) 失败' "$current_branch")"
            else
                exec_judge "git checkout ${current_branch}" "$(msg '切换分支到 (*) 失败' "$current_branch")"
            fi
        else
            current_branch=$(git branch | sed -n -e 's/^\* \(.*\)/\1/p')
        fi

        # 检查数据库迁移变动
        db_changes=$(git diff --name-only HEAD..origin/${current_branch} 2>/dev/null | grep -E "^database/" || true)
        if [[ -n "$db_changes" ]] || [[ "$online_update" == "yes" ]]; then
            # 在线更新无人值守，无论是否有迁移都先备份
            update_stage "backup"
            if [[ -n "$db_changes" ]]; then
                echo "$(msg '数据库有迁移变动，执行数据库备份...')"
            else
                echo "$(msg '执行数据库备份...')"
            fi
            exec_judge "mysql_snapshot backup" "$(msg '数据库备份失败')" "$(msg '数据库备份完成')"
            US_BACKUP="${filename#${WORK_DIR}/}"
        fi

        # 更新代码
        update_stage "pulling"
        if [[ "$force_update" == "yes" ]]; then
            exec_judge "git reset --hard origin/${current_branch}" "$(msg '强制更新代码失败')"
        else
            exec_judge "git pull --ff-only origin ${current_branch}" "$(msg '代码拉取失败，可能存在冲突，请使用 --force 参数')"
        fi

        US_TO_VERSION="$(pkg_version < "${WORK_DIR}/package.json")"
        US_TO_COMMIT="$(git rev-parse HEAD 2>/dev/null)"

        # 更新依赖
        update_stage "composer"
        exec_judge "container_run php 'composer install --optimize-autoloader'" "$(msg '更新PHP依赖失败')"
    else
        # 本地更新模式
        echo "$(msg '执行数据库备份...')"
        exec_judge "mysql_snapshot backup" "$(msg '数据库备份失败')" "$(msg '数据库备份完成')"
    fi

    # 数据库迁移
    update_stage "migrating"
    exec_judge "container_run php 'php artisan migrate'" "$(msg '数据库迁移失败')"

    # 停止服务
    update_stage "restarting"
    $COMPOSE stop php nginx &> /dev/null
    $COMPOSE rm -f php nginx &> /dev/null

    # 启动服务
    $COMPOSE up -d --remove-orphans
    if [[ 0 -ne $? ]]; then
        $COMPOSE down --remove-orphans
        exec_judge "$COMPOSE up -d" "$(msg '重启服务失败')"
    fi
    
    # 兜底拉起 nginx（避免首启时序竞态）
    update_stage "verifying"
    local php_healthy="yes"
    wait_php_healthy || php_healthy=""
    [ -z "$(docker_name nginx)" ] && $COMPOSE up -d --remove-orphans

    env_set UPDATE_TIME "$(date +%s)"
    if [[ "$online_update" == "yes" ]] && [[ -z "$php_healthy" ]]; then
        error "$(msg '服务未能在规定时间内恢复')"
        exit 1
    fi
    if [[ "$online_update" == "yes" ]]; then
        US_STATUS="success"
        US_FINISHED=$(date +%s)
        update_stage "done"
    fi
    success "$(msg '更新完成')"
}

# 卸载函数
handle_uninstall() {
    check_sudo
    # 确认卸载
    echo -e "${RedBG}$(msg '警告：此操作将永久删除以下内容：')${Font}"
    echo "$(msg '- 数据库')"
    echo "$(msg '- 应用程序')"
    echo "$(msg '- 日志文件')"
    echo ""
    read -rp "$(msg '确认要继续卸载吗？(y/N): ')" confirm_uninstall
    [[ -z ${confirm_uninstall} ]] && confirm_uninstall="N"
    case $confirm_uninstall in
    [yY][eE][sS] | [yY])
        echo -e "${RedBG}$(msg '开始卸载...')${Font}"
        ;;
    *)
        echo -e "${GreenBG}$(msg '终止卸载。')${Font}"
        exit 1
        ;;
    esac

    # 清理网络相关容器
    remove_by_network

    # 停止并删除容器（含命名卷）
    $COMPOSE down --remove-orphans --volumes

    # 重置调试模式
    env_set APP_DEBUG "false"

    # 清理数据目录
    find "./docker/mysql/data" -mindepth 1 -delete 2>/dev/null
    find "./docker/logs/supervisor" -mindepth 1 -delete 2>/dev/null
    find "./docker/appstore/config" -mindepth 1 -type d -exec rm -rf {} + 2>/dev/null
    find "./docker/appstore/log" -name "*.log" -delete 2>/dev/null
    find "./storage/logs" -name "*.log" -delete 2>/dev/null

    success "$(msg '卸载完成')"
}

####################################################################################
####################################################################################
####################################################################################

# 优先处理帮助命令
if [[ "$1" == "help" ]] || [[ "$1" == "--help" ]] || [[ "$1" == "-h" ]] || [[ $# -eq 0 ]]; then
    show_help
    exit 0
fi

# 非electron、appexpo和permission命令需要检查Docker环境
if [[ "$1" != "electron" ]] && [[ "$1" != "appexpo" ]] && [[ "$1" != "permission" ]]; then
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
    "permission")
        shift 1
        check_sudo
        permission_fix
        success "$(msg '项目权限修复完成')"
        ;;
    "port")
        shift 1
        check_port "$1" "$(env_get APP_PORT)"
        env_set APP_PORT "$1"
        $COMPOSE up -d
        success "$(msg '修改成功')"
        echo -e "$(msg '地址'): http://${GreenBG}127.0.0.1:$(env_get APP_PORT)${Font}"
        ;;
    "url")
        shift 1
        env_set APP_URL "$1"
        restart_php
        success "$(msg '修改成功')"
        ;;
    "env")
        shift 1
        if [ -n "$1" ]; then
            env_set $1 "$2"
        fi
        restart_php
        success "$(msg '修改成功')"
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
    "appexpo")
        shift 1
        appexpo_operate "$@"
        exit $?
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
        $COMPOSE up -d
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
