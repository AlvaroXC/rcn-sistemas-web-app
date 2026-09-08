#!/usr/bin/env sh
set -eu

: "${DB_HOST:=127.0.0.1}"
: "${DB_PORT:=3306}"
: "${DB_NAME:=rcnsiste_axsoluciones}"
: "${DB_USER:=root}"
: "${BACKUP_DIR:=backups}"

mkdir -p "$BACKUP_DIR"
backup_file="$BACKUP_DIR/${DB_NAME}-$(date +%Y%m%d-%H%M%S).sql"
mysqldump --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" --password="${DB_PASS:-}" "$DB_NAME" > "$backup_file"
printf 'Respaldo creado: %s\n' "$backup_file"
