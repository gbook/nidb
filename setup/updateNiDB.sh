PROGRAMDIR=/nidb/programs
WEBDIR=/var/www/html
MYSQLPASS=peRe7apr
SCRIPTDIR=`pwd`

# update the programs
echo "Backing up current programs directory"
cd ${PROGRAMDIR}
mkdir -pv ${PROGRAMDIR}.bak
cp -ru ${PROGRAMDIR}/* ${PROGRAMDIR}.bak
echo "Exporting latest version of programs from github repo"
svn export --force https://github.com/gbook/nidb-programs/trunk .

# update the web dir
echo "Backing up current Web directory"
cd ${WEBDIR}
mkdir -pv ${WEBDIR}.bak
cp -ru ${WEBDIR}/* ${WEBDIR}.bak
echo "Exporting latest version of www from github repo"
svn export --force https://github.com/gbook/nidb-www/trunk .

cd ${SCRIPTDIR}

# dump the existing database
echo "Dump the current 'nidb' database"
mysqldump -d -uroot -p${MYSQLPASS} nidb > nidb-preUpgrade.sql.bak

# create the template database
echo "Drop existing 'nidb_template' database and recreate it"
mysql -uroot -p${MYSQLPASS} -e "drop database if exists nidb_template; create database if not exists nidb_template"
echo "Importing new 'nidb_template' database from nidb.sql"
sed -i.bak s/TYPE=MyISAM/ENGINE=MyISAM/g nidb.sql
mysql -uroot -p${MYSQLPASS} -D nidb_template < nidb.sql

cd  ${scriptdir}
echo "about to run dbcompare.php"
php dbcompare/dbcompare.php --from-db-name=nidb_template --from-db-host=localhost --from-db-user=root --from-db-password=${MYSQLPASS} --to-db-name=nidb --to-db-host=localhost --to-db-user=root --to-db-password=${MYSQLPASS} > updateToMaster.sql
