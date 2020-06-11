#!/bin/sh

# copy nidb source
cp -uv /nidb/programs/compiled/nidb/*.cpp /nidb/nidbgit/src/nidb/
cp -uv /nidb/programs/compiled/nidb/*.h /nidb/nidbgit/src/nidb/
cp -uv /nidb/programs/compiled/nidb/*.pro /nidb/nidbgit/src/nidb/

# copy web source
cp -uv /var/www/html/*.php /nidb/nidbgit/src/web/
cp -uv /var/www/html/*.css /nidb/nidbgit/src/web/

# dump SQL
#mysqldump
