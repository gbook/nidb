mysqldump -d -uroot -p --no-create-db --no-data --skip-add-drop-table $1|egrep -v "(^SET|^/\*\!)" |sed "s/AUTO_INCREMENT=[0-9]*//g" > nidb.sql
