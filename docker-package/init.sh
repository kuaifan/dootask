#!/bin/bash

CMD_NAME=dootask
CMD_ECHO="\033[1;96m[${CMD_NAME}]\033[0m"

function info() {
  echo -e "$(date "+%Y-%m-%d %H:%M:%S") ${CMD_ECHO} - INFO - $@"
}

function warn() {
  echo -e "$(date "+%Y-%m-%d %H:%M:%S") ${CMD_ECHO} - \033[33mWARN\033[0m - $@"
}

SCOPE_NAME="\033[35m[docker]\033[0m"

if [[ ! -f ".env" ]]; then
  if cp ./.env.example .env; then
    info "${SCOPE_NAME} - 初始化环境变量文件"
  else
    warn "${SCOPE_NAME} - 初始化环境变量文件失败，初始化失败 - [\033[31m初始化失败\033[0m]"
    exit 1
  fi
fi

if docker-compose create; then
  info "${SCOPE_NAME} - 容器创建成功"
else
  warn "${SCOPE_NAME} - 容器创建失败 - [\033[31m初始化失败\033[0m]"
  exit 1
fi

SCOPE_NAME="\033[35m[nginx]\033[0m"

ASSETS_HOME="./volume/nginx/assets"

ASSETS_DOOTASK="${ASSETS_HOME}/dootask"

if [[ ! -d "${ASSETS_HOME}" ]]; then
  if mkdir -p ${ASSETS_HOME}; then
    if docker cp dootask-php:/var/www/public ${ASSETS_DOOTASK}; then
      info "${SCOPE_NAME} - 初始化静态资源 - [\033[32m成功\033[0m]"
    else
      warn "${SCOPE_NAME} - 静态资源初始化失败"
      exit 1
    fi
  else
    exit 1
  fi
elif [[ ! -d "${ASSETS_DOOTASK}" ]]; then
  if docker cp dootask-php:/var/www/public ${ASSETS_DOOTASK}; then
    info "${SCOPE_NAME} - 初始化静态资源 - [\033[32m成功\033[0m]"
  else
    warn "${SCOPE_NAME} - 静态资源初始化失败"
    exit 1
  fi
else
  TMP_DIRS=$(mktemp -d "volume/assets-XXXXXX")

  if docker cp dootask-php:/var/www/public/ "${TMP_DIRS}"; then
    # 强制使用发布版本的静态资源
    if [[ -d "./volume/nginx/assets/dootask" ]]; then
      if cp -r "${TMP_DIRS}"/public/* ./volume/nginx/assets/dootask/; then
        info "${SCOPE_NAME} - 覆盖静态资源 - [\033[32m成功\033[0m]"
        # 删除临时文件
        rm -rf "${TMP_DIRS}"
      else
        warn "${SCOPE_NAME} - 初始化静态资源失败"
        rm -rf "${TMP_DIRS}"
        exit 1
      fi
    elif mv "${TMP_DIRS}" ./volume/nginx/assets/dootask; then
      info "${SCOPE_NAME} - 初始化静态资源 - [\033[32m成功\033[0m]"
    fi
  else
    info "${SCOPE_NAME} - 初始化静态资源 - [\033[31m失败\033[0m]"
    rm -rf "${TMP_DIRS}"
    exit 1
  fi
fi

DB_DATABASE=${DB_DATABASE:-"dootask"}

if docker-compose up -d; then
  START_TIMEOUT=10
  count=0;
  until [[ -z "$(docker exec dootask-mariadb sh -c "mysql -u\$MYSQL_USER -p\$MYSQL_PASSWORD -e \"use ${DB_DATABASE}\"" 2>&1)" ]] || [[ ${count} -gt ${START_TIMEOUT} ]]
  do
    if [[ ${count} -lt 1 ]]; then
      echo -n "等待容器启动"
    fi
    echo -n -e ".";
    sleep 1
    count=$((count + 1))
  done

  if [[ ${count} -gt 0 ]]; then
    echo
  fi
  sleep 2
  # 初始化数据库
  docker exec dootask-php php artisan migrate --seed
  # 初始化管理员密码
  docker exec dootask-mariadb sh /volume/bin/repassword.sh
fi
