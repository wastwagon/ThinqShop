#!/bin/bash

# Configuration
DB_CONTAINER="thinqshopping_mysql"
DB_USER="thinquser"
DB_PASS="thinqpass"
DB_NAME="thinjupz_db"
OUTPUT_FILE="thinjupz_db.sql"

echo "📦 Exporting database..."

# Dump database from docker container
docker exec $DB_CONTAINER mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $OUTPUT_FILE

if [ $? -eq 0 ]; then
    echo "✅ Database exported to $OUTPUT_FILE"
    echo "This file will be used to initialize the database on deployment."
else
    echo "❌ Error exporting database"
    exit 1
fi
