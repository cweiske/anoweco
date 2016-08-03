#!/bin/sh
# update data/schema.sql
cd "`dirname "$0"`"
mysqldump\
 --skip-add-locks\
 --skip-disable-keys\
 --skip-add-drop-table\
 --no-data\
 -uanoweco -panoweco\
 anoweco\
 | grep -v '^/\\*'\
 | grep -v '^--'\
 > ../data/schema.sql
