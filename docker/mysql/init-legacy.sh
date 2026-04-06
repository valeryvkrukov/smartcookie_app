#!/bin/bash
set -e
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e \
    "CREATE DATABASE IF NOT EXISTS old_smartcookies_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -p"$MYSQL_ROOT_PASSWORD" old_smartcookies_db < /tmp/dump.sql
