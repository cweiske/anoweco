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
 | sed 's/AUTO_INCREMENT=[0-9]* //'\
 > ../data/schema.sql
