#!/bin/sh
# ==============================================
# Database Backup Script for NotarisKu
# Usage: ./docker/scripts/backup-db.sh
# Recommended: Run via cron daily
# ==============================================

set -e

CONTAINER_NAME="notarisku_db"
BACKUP_DIR="./backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=7

# Load environment variables
if [ -f .env ]; then
    export $(grep -E '^(DB_DATABASE|DB_USERNAME|DB_PASSWORD)=' .env | xargs)
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"

echo "📦 Starting database backup..."

# Perform backup
docker exec "$CONTAINER_NAME" mysqldump \
    -u "$DB_USERNAME" \
    -p"$DB_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    "$DB_DATABASE" | gzip > "$BACKUP_DIR/notarisku_${DATE}.sql.gz"

echo "✅ Backup created: $BACKUP_DIR/notarisku_${DATE}.sql.gz"

# Remove old backups
echo "🧹 Removing backups older than ${RETENTION_DAYS} days..."
find "$BACKUP_DIR" -name "notarisku_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "✅ Backup completed!"
