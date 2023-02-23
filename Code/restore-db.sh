#!/bin/bash

echo "CREATE DATABASE divenn;" | docker exec -i -e MYSQL_PWD=divenn bch-divenn-mysql-1 mysql -u root
tar -JxOf ~/Downloads/divenn_db_20220304.tar.xz | \
    cat <(echo "SET AUTOCOMMIT=0, UNIQUE_CHECKS=0, FOREIGN_KEY_CHECKS=0;") \
        - \
        <(echo "SET AUTOCOMMIT=1, UNIQUE_CHECKS=1, FOREIGN_KEY_CHECKS=1; COMMIT;") | \
    docker exec -i -e MYSQL_PWD=divenn bch-divenn-mysql-1 mysql -u root divenn