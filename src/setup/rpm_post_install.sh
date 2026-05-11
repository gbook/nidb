#!/bin/bash

# look for and read an existing config file
new_installation=1
CONFIG_FILE=""
declare -A config
POSSIBLE_FILES=(
    "/etc/nidb/nidb.cfg"
    "/usr/local/etc/nidb/nidb.cfg"
    "$HOME/.config/nidb/nidb.cfg"
    "/nidb/nidb.cfg"
    "/nidb/bin/nidb.cfg"
    "./nidb.cfg"
    "/nidb/programs/nidb.cfg"
)

setup_dcmrcv_service() {
    echo 'Setting up dcmrcv...'

    if command -v systemctl >/dev/null 2>&1 && [[ -f /nidb/setup/dcmrcv.service ]]; then
        cp /nidb/setup/dcmrcv.service /etc/systemd/system/
        systemctl daemon-reload
        systemctl enable dcmrcv.service
        systemctl start dcmrcv.service
        return
    fi

    if [[ -f /nidb/setup/dcmrcv ]]; then
        cp /nidb/setup/dcmrcv /etc/init.d/
        chmod 755 /etc/init.d/dcmrcv

        if command -v chkconfig >/dev/null 2>&1; then
            chkconfig --add dcmrcv
        fi

        if command -v service >/dev/null 2>&1; then
            service dcmrcv start
        else
            /etc/init.d/dcmrcv start
        fi
    else
        echo 'No dcmrcv service file found; skipping dcmrcv service setup.'
    fi
}

# find the config file if it exists
for file in "${POSSIBLE_FILES[@]}"; do
    if [[ -f "$file" ]]; then
        CONFIG_FILE="$file"
        new_installation=0

        # copy config file to /etc/nidb/nidb.cfg if it is not there already
        if [[ "$file" != "/etc/nidb/nidb.cfg" ]]; then
            mkdir -p /etc/nidb
            cp -uv "$CONFIG_FILE" /etc/nidb/
            chmod 644 /etc/nidb/nidb.cfg
        fi
        break
    fi
done

# load the config variables if a config file was found
if [[ -n "$CONFIG_FILE" ]]; then
    while IFS= read -r line; do
        # Trim leading/trailing whitespace
        line="$(sed 's/^[[:space:]]*//;s/[[:space:]]*$//' <<< "$line")"

        # Skip comments and empty lines
        [[ -z "$line" || "$line" =~ ^# ]] && continue

        # Match: [key] = value
        if [[ "$line" =~ ^\[([a-zA-Z0-9_]+)\][[:space:]]*=[[:space:]]*(.*)$ ]]; then
            key="${BASH_REMATCH[1]}"
            value="${BASH_REMATCH[2]}"

            # Remove optional surrounding quotes
            value="${value%\"}"
            value="${value#\"}"

            config["$key"]="$value"
            echo "[$key]=$value"
        fi
    done < "$CONFIG_FILE"
fi

if ((new_installation)); then
    echo "This is a NEW installation"
else
    echo "This is an EXISTING installation"
fi

# create link to the mariadb libraries (may or may not be necessary)
echo 'Create libmariadb link...'
ln -sf /lib64/libmariadb.so.3 /lib64/libmysqlclient.so.18

# PHP packages
echo 'Install PHP packages...'
pear install Mail Mail_Mime Net_SMTP

# disable SE Linux
echo 'Disable SE Linux...'
setenforce 0
sed -i s/^SELINUX=.*/SELINUX=disabled/g /etc/selinux/config

# change php.ini settings
echo 'Change php.ini settings...'
sed -i 's/^short_open_tag = .*/short_open_tag = On/g' /etc/php.ini
sed -i 's/^session.gc_maxlifetime = .*/session.gc_maxlifetime = 28800/g' /etc/php.ini
sed -i 's/^memory_limit = .*/memory_limit = 5000M/g' /etc/php.ini
sed -i 's!^;.*upload_tmp_dir = .*!upload_tmp_dir = /nidb/uploadtmp!g' /etc/php.ini
sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 5000M/g' /etc/php.ini
sed -i 's/^max_file_uploads = .*/max_file_uploads = 1000/g' /etc/php.ini
sed -i 's/^;.*max_input_vars = .*/max_input_vars = 1000/g' /etc/php.ini # this line is probably commented out
sed -i 's/^max_input_time = .*/max_input_time = 600/g' /etc/php.ini
sed -i 's/^max_execution_time = .*/max_execution_time = 600/g' /etc/php.ini
sed -i 's/^post_max_size = .*/post_max_size = 5000M/g' /etc/php.ini
sed -i 's/^display_errors = .*/display_errors = On/g' /etc/php.ini
sed -i 's/^error_reporting = .*/error_reporting = E_ALL \& \~E_DEPRECATED \& \~E_STRICT \& \~E_NOTICE/' /etc/php.ini

# change PHP-fpm users
echo 'Change php-fpm settings...'
sed -i 's/user = apache/user = nidb/' /etc/php-fpm.d/www.conf
sed -i 's/group = apache/group = nidb/' /etc/php-fpm.d/www.conf
sed -i 's/;listen.owner = nobody/listen.owner = nidb/' /etc/php-fpm.d/www.conf
sed -i 's/;listen.group = nobody/listen.group = nidb/' /etc/php-fpm.d/www.conf
sed -i 's/;listen.mode/listen.mode/' /etc/php-fpm.d/www.conf
sed -i 's/listen.acl_users/;listen.acl_users/' /etc/php-fpm.d/www.conf

# enable and start services
echo 'Enable and start services...'
systemctl enable httpd.service   # enable the apache web service
systemctl enable mariadb.service # enable the MariaDB service
systemctl enable php-fpm.service # enable PHP-FastCGI Process Manager service
systemctl start httpd.service
systemctl start mariadb.service
systemctl start php-fpm.service

# make sure required ports are accessible through the firewall
echo 'Add ports to firewall...'
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --permanent --add-port=104/tcp
firewall-cmd --permanent --add-port=104/udp
firewall-cmd --permanent --add-port=8104/tcp
firewall-cmd --permanent --add-port=8104/udp
firewall-cmd --reload

# create nidb user if it does not exist, add nidb to the apache group, and apache to the nidb group
echo 'Add nidb user...'
id -u nidb &>/dev/null || useradd -p "$(openssl passwd -1 password)" nidb
groupadd -f nidb
usermod -a -G apache nidb
usermod -a -G nidb apache
# set nidb as the owner of these directories
chown nidb:nidb /run/php-fpm/www.sock
chown -R nidb:nidb /var/lib/php/session

systemctl restart php-fpm.service

# setup cron jobs
echo 'Installing crontab...'
crontab -u nidb /nidb/setup/crontab.txt

# database stuff
echo 'Set root MariaDB password...'
mysqladmin -uroot password password # set the root password
echo 'Create MariaDB nidb account...'
mysql -uroot -ppassword -e "CREATE USER IF NOT EXISTS 'nidb'@'%' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON *.* TO 'nidb'@'%'; FLUSH PRIVILEGES;"

setup_dcmrcv_service

if ((new_installation)); then
    # create data directories
    echo 'Create data directories and change owner...'
    mkdir -p -m 774 /nidb
    mkdir -p -m 764 /nidb/data
    mkdir -p -m 764 /nidb/data/archive
    mkdir -p -m 764 /nidb/data/backup
    mkdir -p -m 764 /nidb/data/backupstaging
    mkdir -p -m 764 /nidb/data/deleted
    mkdir -p -m 764 /nidb/data/dicomincoming
    mkdir -p -m 764 /nidb/data/download
    mkdir -p -m 764 /nidb/data/export
    mkdir -p -m 764 /nidb/data/problem
    mkdir -p -m 764 /nidb/data/tmp
    mkdir -p -m 764 /nidb/data/upload
    mkdir -p -m 764 /nidb/data/uploaded
    mkdir -p -m 764 /nidb/data/uploadstaging

    echo 'Change ownership of /nidb contents...'
    chown -R nidb:nidb /nidb/bin /nidb/lock /nidb/logs /nidb/qcmodules /nidb/setup
    chown nidb:nidb /nidb/*
    chown nidb:nidb /nidb/data
    chown nidb:nidb /nidb/data/archive /nidb/data/backup /nidb/data/backupstaging /nidb/data/deleted /nidb/data/dicomincoming /nidb/data/ftp /nidb/data/export /nidb/data/problem /nidb/data/tmp /nidb/data/upload /nidb/data/uploaded /nidb/data/uploadstaging
    chown -R nidb:nidb /var/www/html
else
    echo 'Existing installation detected; skipping data, web, and install directory ownership and permission changes.'
fi

touch /nidb/setup/dbupgrade

echo "*****************************************************************************************"
echo "  IMPORTANT!!"
echo "  - Go to http://localhost/setup.php to finish the upgrade process!!"
echo "  - If needed, edit /etc/systemd/system/dcmrcv.service to reflect the correct dicomincoming path."
echo "*****************************************************************************************"
