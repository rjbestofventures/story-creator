#!/usr/bin/env bash
set -euo pipefail

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is required but not installed."
  exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
  echo "Docker Compose is required but not installed."
  exit 1
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_ENV_FILE="${ROOT_DIR}/.env"

upsert_env_var() {
  local file="$1"
  local key="$2"
  local value="$3"

  if grep -qE "^${key}=" "$file"; then
    sed -i "s|^${key}=.*|${key}=${value}|" "$file"
  else
    echo "${key}=${value}" >> "$file"
  fi
}

if [ ! -f "${APP_ENV_FILE}" ]; then
  cp "${ROOT_DIR}/.env.example" "${APP_ENV_FILE}"
fi

upsert_env_var "${APP_ENV_FILE}" "UID" "$(id -u)"
upsert_env_var "${APP_ENV_FILE}" "GID" "$(id -g)"
upsert_env_var "${APP_ENV_FILE}" "APP_PORT" "8000"
upsert_env_var "${APP_ENV_FILE}" "DB_PORT" "3306"
upsert_env_var "${APP_ENV_FILE}" "REDIS_PORT" "6379"
upsert_env_var "${APP_ENV_FILE}" "DB_CONNECTION" "mysql"
upsert_env_var "${APP_ENV_FILE}" "DB_HOST" "db"
upsert_env_var "${APP_ENV_FILE}" "DB_DATABASE" "laravel"
upsert_env_var "${APP_ENV_FILE}" "DB_USERNAME" "laravel"
upsert_env_var "${APP_ENV_FILE}" "DB_PASSWORD" "secret"
upsert_env_var "${APP_ENV_FILE}" "DB_ROOT_PASSWORD" "root"
upsert_env_var "${APP_ENV_FILE}" "REDIS_HOST" "redis"

cd "${ROOT_DIR}"

echo "Building and starting containers ..."
docker compose up -d --build

echo "Waiting for MySQL to accept connections ..."
for i in $(seq 1 60); do
  if docker compose exec -T app php -r '
    $h = getenv("DB_HOST") ?: "db";
    $p = getenv("DB_PORT") ?: "3306";
    $d = getenv("DB_DATABASE") ?: "laravel";
    $u = getenv("DB_USERNAME") ?: "laravel";
    $pw = getenv("DB_PASSWORD") ?: "secret";
    try {
      new PDO("mysql:host=$h;port=$p;dbname=$d", $u, $pw);
      exit(0);
    } catch (Throwable $e) {
      exit(1);
    }
  '; then
    echo "MySQL is ready."
    break
  fi

  if [ "$i" -eq 60 ]; then
    echo "MySQL did not become ready in time."
    exit 1
  fi

  sleep 2
done

echo "Clearing config cache ..."
docker compose exec -T app php artisan config:clear

echo "Generating app key (if missing) ..."
docker compose exec -T app php artisan key:generate --force

echo "Running migrations ..."
docker compose exec -T app php artisan migrate --force

echo "Docker environment is ready."
echo "Open: http://localhost:8000"
