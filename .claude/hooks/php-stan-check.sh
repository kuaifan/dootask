#!/usr/bin/env bash
# Claude Code PostToolUse hook:Edit/Write 改动 app/ 下的 PHP 文件后,自动在 PHP 容器内
# 对该文件跑 phpstan 单文件分析。失败时 exit 2,把错误回灌给 Claude 修复。
# 任何环境不满足(无 python3 / 容器未运行 / 未装 phpstan)都静默放行,绝不阻塞编辑。
set -u

INPUT=$(cat)
PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"

command -v python3 >/dev/null 2>&1 || exit 0
command -v docker >/dev/null 2>&1 || exit 0

FILE_PATH=$(printf '%s' "$INPUT" | python3 -c "import sys,json;print(json.load(sys.stdin).get('tool_input',{}).get('file_path',''))" 2>/dev/null) || exit 0
[ -n "$FILE_PATH" ] || exit 0

case "$FILE_PATH" in
    *.php) ;;
    *) exit 0 ;;
esac

REL_PATH="${FILE_PATH#"$PROJECT_DIR"/}"
case "$REL_PATH" in
    app/*) ;;
    *) exit 0 ;;
esac
[ -f "$PROJECT_DIR/$REL_PATH" ] || exit 0

# 定位挂载本项目的 PHP 容器:
# ① 环境变量 DOOTASK_PHP_CONTAINER;② .env 的 APP_ID;③ 扫描 /var/www 挂载源为本项目的容器
CONTAINER="${DOOTASK_PHP_CONTAINER:-}"
if [ -z "$CONTAINER" ] && [ -f "$PROJECT_DIR/.env" ]; then
    APP_ID=$(grep -E '^APP_ID=' "$PROJECT_DIR/.env" 2>/dev/null | head -1 | cut -d= -f2 | tr -d '"' | tr -d "'")
    if [ -n "$APP_ID" ] && docker ps --format '{{.Names}}' 2>/dev/null | grep -qx "dootask-php-$APP_ID"; then
        CONTAINER="dootask-php-$APP_ID"
    fi
fi
if [ -z "$CONTAINER" ]; then
    RUNNING=$(docker ps -q 2>/dev/null)
    [ -n "$RUNNING" ] && CONTAINER=$(docker inspect --format '{{.Name}}|{{range .Mounts}}{{if eq .Destination "/var/www"}}{{.Source}}{{end}}{{end}}' $RUNNING 2>/dev/null \
        | awk -F'|' -v dir="$PROJECT_DIR" '$2 == dir {gsub(/^\//, "", $1); print $1; exit}')
fi
[ -n "$CONTAINER" ] || exit 0
docker exec "$CONTAINER" test -f /var/www/vendor/bin/phpstan 2>/dev/null || exit 0

OUTPUT=$(docker exec "$CONTAINER" sh -c "cd /var/www && php vendor/bin/phpstan analyse --no-progress --error-format=raw --memory-limit=-1 '$REL_PATH'" 2>&1)
if [ $? -ne 0 ]; then
    {
        echo "phpstan 检查未通过($REL_PATH),请修复以下问题:"
        printf '%s\n' "$OUTPUT" | grep -v '^Note:' | tail -30
    } >&2
    exit 2
fi
exit 0
