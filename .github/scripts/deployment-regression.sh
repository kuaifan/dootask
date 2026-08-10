#!/usr/bin/env bash

set -euo pipefail

env_value() {
    local key="$1"
    sed -n "s/^${key}=//p" .env | tail -n 1
}

db_exec() {
    local database username password
    database="$(env_value DB_DATABASE)"
    username="$(env_value DB_USERNAME)"
    password="$(env_value DB_PASSWORD)"

    docker compose exec -T mariadb \
        mariadb --batch --skip-column-names \
        -u"${username}" -p"${password}" "${database}"
}

table_prefix() {
    local prefix
    prefix="$(env_value DB_PREFIX)"
    if [[ ! "$prefix" =~ ^[A-Za-z0-9_]*$ ]]; then
        echo "Invalid database table prefix: ${prefix}" >&2
        exit 1
    fi
    printf '%s' "$prefix"
}

wait_for_services() {
    local deadline service container_id state health all_ready
    local -a services
    mapfile -t services < <(docker compose config --services)
    deadline=$((SECONDS + 300))

    while (( SECONDS < deadline )); do
        all_ready=true
        for service in "${services[@]}"; do
            container_id="$(docker compose ps -q "$service")"
            if [[ -z "$container_id" ]]; then
                all_ready=false
                break
            fi

            state="$(docker inspect --format '{{.State.Status}}' "$container_id")"
            health="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "$container_id")"
            if [[ "$state" != "running" || ( "$health" != "healthy" && "$health" != "none" ) ]]; then
                all_ready=false
                break
            fi
        done

        if [[ "$all_ready" == "true" ]]; then
            return 0
        fi
        sleep 5
    done

    echo "Services did not become healthy within 300 seconds." >&2
    docker compose ps >&2
    return 1
}

check_installation() {
    local port prefix user_count migration_status
    wait_for_services

    port="$(env_value APP_PORT)"
    curl --fail --silent --show-error \
        --retry 30 --retry-delay 2 --retry-connrefused \
        "http://127.0.0.1:${port}/health" >/dev/null

    prefix="$(table_prefix)"
    user_count="$(printf 'SELECT COUNT(*) FROM `%susers`;\n' "$prefix" | db_exec | tr -d '[:space:]')"
    if [[ ! "$user_count" =~ ^[1-9][0-9]*$ ]]; then
        echo "Expected seeded users, got: ${user_count:-empty}" >&2
        exit 1
    fi

    migration_status="$(sudo ./cmd artisan migrate:status --no-ansi)"
    printf '%s\n' "$migration_status"
    if grep -qE '[|[:space:]]Pending[|[:space:]]' <<<"$migration_status"; then
        echo "Pending database migrations remain." >&2
        exit 1
    fi
}

seed_upgrade_sentinel() {
    local prefix
    prefix="$(table_prefix)"
    printf "DELETE FROM \`%ssettings\` WHERE name = 'ci_upgrade_sentinel';\n" "$prefix" | db_exec
    printf "INSERT INTO \`%ssettings\` (name, \`desc\`, setting, created_at, updated_at) VALUES ('ci_upgrade_sentinel', 'deployment regression', 'dootask-upgrade-ci', NOW(), NOW());\n" "$prefix" | db_exec

    mkdir -p public/uploads
    printf '%s\n' 'dootask-upgrade-ci' > public/uploads/ci-upgrade-sentinel.txt
}

verify_upgrade_sentinel() {
    local prefix row_count
    prefix="$(table_prefix)"
    row_count="$(printf "SELECT COUNT(*) FROM \`%ssettings\` WHERE name = 'ci_upgrade_sentinel' AND setting = 'dootask-upgrade-ci';\n" "$prefix" | db_exec | tr -d '[:space:]')"
    if [[ "$row_count" != "1" ]]; then
        echo "Database upgrade sentinel was not preserved." >&2
        exit 1
    fi

    grep -qx 'dootask-upgrade-ci' public/uploads/ci-upgrade-sentinel.txt
}

assert_revision() {
    local expected_sha="$1"
    local actual_sha
    actual_sha="$(git rev-parse HEAD)"
    if [[ "$actual_sha" != "$expected_sha" ]]; then
        echo "Expected revision ${expected_sha}, got ${actual_sha}." >&2
        exit 1
    fi
}

case "${1:-}" in
    check-installation)
        check_installation
        ;;
    seed-upgrade-sentinel)
        seed_upgrade_sentinel
        ;;
    verify-upgrade-sentinel)
        verify_upgrade_sentinel
        ;;
    assert-revision)
        assert_revision "${2:?Expected revision is required}"
        ;;
    *)
        echo "Usage: $0 {check-installation|seed-upgrade-sentinel|verify-upgrade-sentinel|assert-revision <sha>}" >&2
        exit 2
        ;;
esac
