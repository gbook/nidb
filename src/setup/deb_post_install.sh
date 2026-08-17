#!/bin/bash
# ------------------------------------------------------------------------------
# NiDB Debian post-install script (DEBIAN/postinst)
# This is the Debian/Ubuntu equivalent of rpm_post_install.sh. dpkg runs it
# automatically after the package's files are unpacked. It configures the OS
# (users, services, PHP, MariaDB, firewall) so NiDB is ready to finish setup at
# http://localhost/setup.php.
#
# Differences from the RHEL post-install:
#   - web server is apache2 (not httpd)
#   - PHP config lives under /etc/php/<version>/ (detected at runtime)
#   - the web-server user/group is www-data (not apache)
#   - firewall is ufw (not firewalld); SELinux does not exist
# ------------------------------------------------------------------------------

# dpkg calls postinst as: postinst configure <old-version>
# Only run our configuration on 'configure' (skip abort-upgrade etc.)
if [[ -n "$1" && "$1" != "configure" ]]; then
    exit 0
fi

# detect the installed PHP version (e.g. "8.2"); Debian paths are versioned
PHPVER="$(ls /etc/php/ 2>/dev/null | grep -E '^[0-9]+\.[0-9]+$' | sort -V | tail -1)"
if [[ -z "$PHPVER" ]]; then
    echo "WARNING: could not detect an installed PHP version under /etc/php/; PHP configuration will be skipped."
fi

# look for and read an existing config file
new_installation=1
CONFIG_FILE=""
declare -A config
POSSIBLE_FILES=(
    "/nidb/nidb.cfg"
    "/nidb/bin/nidb.cfg"
    "/etc/nidb/nidb.cfg"
    "/usr/local/etc/nidb/nidb.cfg"
    "$HOME/.config/nidb/nidb.cfg"
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

        if command -v service >/dev/null 2>&1; then
            service dcmrcv start
        else
            /etc/init.d/dcmrcv start
        fi
    else
        echo 'No dcmrcv service file found; skipping dcmrcv service setup.'
    fi
}

# find the config file if it exists; migrate to /nidb/nidb.cfg if found elsewhere
for file in "${POSSIBLE_FILES[@]}"; do
    if [[ -f "$file" ]]; then
        new_installation=0
        if [[ "$file" != "/nidb/nidb.cfg" ]]; then
            echo "Migrating config from $file to /nidb/nidb.cfg"
            mkdir -p /nidb
            cp -v "$file" /nidb/nidb.cfg
            chmod 640 /nidb/nidb.cfg
            if [[ "$file" == "/etc/nidb/nidb.cfg" ]]; then
                rm -f /etc/nidb/nidb.cfg
                rmdir --ignore-fail-on-non-empty /etc/nidb
            fi
        fi
        CONFIG_FILE="/nidb/nidb.cfg"
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

# create link to the mariadb client library (may or may not be necessary)
echo 'Create libmariadb link...'
MARIADB_LIB="$(ls /usr/lib/x86_64-linux-gnu/libmariadb.so.3 2>/dev/null | head -1)"
if [[ -n "$MARIADB_LIB" ]]; then
    ln -sf "$MARIADB_LIB" /usr/lib/x86_64-linux-gnu/libmysqlclient.so.18
fi

# refresh the shared library cache (NiDB ships its DCMTK/Qt libs under /usr/lib)
echo 'Running ldconfig...'
ldconfig

# ----- PHP configuration -----
if [[ -n "$PHPVER" ]]; then
    echo "Change php.ini settings (PHP $PHPVER)..."
    # Debian keeps separate php.ini files for the fpm and cli SAPIs; update both
    for INI in "/etc/php/$PHPVER/fpm/php.ini" "/etc/php/$PHPVER/cli/php.ini"; do
        [[ -f "$INI" ]] || continue
        sed -i 's/^short_open_tag = .*/short_open_tag = On/g' "$INI"
        sed -i 's/^session.gc_maxlifetime = .*/session.gc_maxlifetime = 28800/g' "$INI"
        sed -i 's/^memory_limit = .*/memory_limit = 5000M/g' "$INI"
        sed -i 's!^;.*upload_tmp_dir = .*!upload_tmp_dir = /nidb/uploadtmp!g' "$INI"
        sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 5000M/g' "$INI"
        sed -i 's/^max_file_uploads = .*/max_file_uploads = 1000/g' "$INI"
        sed -i 's/^;.*max_input_vars = .*/max_input_vars = 1000/g' "$INI"
        sed -i 's/^max_input_time = .*/max_input_time = 600/g' "$INI"
        sed -i 's/^max_execution_time = .*/max_execution_time = 600/g' "$INI"
        sed -i 's/^post_max_size = .*/post_max_size = 5000M/g' "$INI"
        sed -i 's/^display_errors = .*/display_errors = On/g' "$INI"
        sed -i 's/^error_reporting = .*/error_reporting = E_ALL \& \~E_DEPRECATED \& \~E_STRICT \& \~E_NOTICE/' "$INI"
    done

    # change PHP-fpm pool user/group to nidb
    echo 'Change php-fpm settings...'
    POOL="/etc/php/$PHPVER/fpm/pool.d/www.conf"
    if [[ -f "$POOL" ]]; then
        sed -i 's/^user = www-data/user = nidb/' "$POOL"
        sed -i 's/^group = www-data/group = nidb/' "$POOL"
        sed -i 's/^listen.owner = www-data/listen.owner = nidb/' "$POOL"
        sed -i 's/^listen.group = www-data/listen.group = nidb/' "$POOL"
        sed -i 's/^;listen.mode/listen.mode/' "$POOL"
    fi
fi

# enable the MariaDB event scheduler (needed for automatic timeseries partition
# maintenance -- see src/setup/timeseries_partition_maintenance.sql)
echo 'Enabling MariaDB event scheduler...'
mkdir -p /etc/mysql/mariadb.conf.d
cat > /etc/mysql/mariadb.conf.d/99-nidb.cnf <<'EOF'
[mariadb]
event_scheduler=ON
EOF

# ----- web server / PHP-fpm apache wiring -----
echo 'Enable apache modules for PHP-FPM...'
if command -v a2enmod >/dev/null 2>&1; then
    a2enmod proxy_fcgi setenvif >/dev/null 2>&1
    [[ -n "$PHPVER" ]] && a2enconf "php$PHPVER-fpm" >/dev/null 2>&1
fi

# enable and start services
echo 'Enable and start services...'
systemctl enable apache2.service   # enable the apache web service
systemctl enable mariadb.service   # enable the MariaDB service
[[ -n "$PHPVER" ]] && systemctl enable "php$PHPVER-fpm.service" # enable PHP-FastCGI Process Manager service
systemctl start mariadb.service
[[ -n "$PHPVER" ]] && systemctl start "php$PHPVER-fpm.service"
systemctl start apache2.service

# make sure required ports are accessible through the firewall (ufw, if present/active)
if command -v ufw >/dev/null 2>&1; then
    echo 'Add ports to firewall (ufw)...'
    ufw allow 80/tcp
    ufw allow 104/tcp
    ufw allow 104/udp
    ufw allow 8104/tcp
    ufw allow 8104/udp
fi

# create nidb user if it does not exist, add nidb to the www-data group, and www-data to the nidb group
echo 'Add nidb user...'
id -u nidb &>/dev/null || useradd -m -p "$(openssl passwd -1 password)" nidb
groupadd -f nidb
usermod -a -G www-data nidb
usermod -a -G nidb www-data
# set nidb as the owner of these directories
[[ -n "$PHPVER" && -S "/run/php/php$PHPVER-fpm.sock" ]] && chown nidb:nidb "/run/php/php$PHPVER-fpm.sock"
[[ -d /var/lib/php/sessions ]] && chown -R nidb:nidb /var/lib/php/sessions

[[ -n "$PHPVER" ]] && systemctl restart "php$PHPVER-fpm.service"

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
    mkdir -p -m 764 /nidb/data/ftp
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
    echo 'Existing installation detected. Ensuring ownership of key directories is nidb'
    chown -R nidb:nidb /var/www/html
    chown nidb:nidb /nidb/*
fi

# create the web download symlink only if it does not already exist. The package no longer ships
# /var/www/html/download (see makeInstallerDebian*.sh) so that upgrades never clobber a custom
# download path. On a fresh install this points at the default /nidb/data/download; on an existing
# install a pre-existing link (default or custom, even one whose target is currently unmounted) is
# left as-is.
if [[ ! -e /var/www/html/download && ! -L /var/www/html/download ]]; then
    echo 'Creating web download link /var/www/html/download -> /nidb/data/download...'
    ln -s /nidb/data/download /var/www/html/download
    chown -h nidb:nidb /var/www/html/download
fi

# make sure nidb.cfg is owned by and writeable by the nidb account. settings.php (running as the
# nidb php-fpm user) rewrites this file via WriteConfig(), so it must be nidb-writeable. Package
# install can leave it root-owned, which breaks saving settings.
echo 'Ensuring nidb.cfg is writeable by the nidb account...'
if [[ -f /nidb/nidb.cfg ]]; then
    chown nidb:nidb /nidb/nidb.cfg
    chmod 640 /nidb/nidb.cfg
fi

# remove deprecated web files left over from previous versions (package upgrades do not delete them automatically)
echo 'Removing deprecated web files...'
DEPRECATED_WEB_FILES=(
    "GetRCInst.php"
    "adminassessmentforms.php"
    "assessments.php"
    "calendar_allocations.php"
    "calendar_projects.php"
    "calendar_users.php"
    "drugs.php"
    "f.php"
    "horizontalchart.php"
    "importcsvdata.php"
    "importexperiment.php"
    "importredcapcsvdata.php"
    "kashi.php"
    "longformat.csv"
    "measures.php"
    "mrqcchecklist_old.php"
    "prescriptions.php"
    "projectassessments.php"
    "projects_ado2dev.php"
    "rcmaninsert.php"
    "series_inlineupdate.php"
    "objectexists.php"
    "stddevchart.php"
    "redcap2ADO.php"
    "redcapimport.php"
    "redcapimportsubjects.php"
    "redcapmaping.php"
    "redcapmapping.php"
    "redcapsubjectsimport.php"
    "redcaptonidb.php"
    "redcaptonidb_Old.php"
    "shortformat.csv"
    "subject_inlineupdate.php"
    "subjectlist.php"
    "validatepath.php"
    "vitals.php"
)
for f in "${DEPRECATED_WEB_FILES[@]}"; do
    rm -f "/var/www/html/$f"
done
# also remove any files explicitly marked as deprecated
rm -f /var/www/html/deprecated_*

touch /nidb/setup/dbupgrade

echo "*****************************************************************************************"
echo "  IMPORTANT!!"
echo "  - Go to http://localhost/setup.php to finish the upgrade process!!"
echo "  - If needed, edit /etc/systemd/system/dcmrcv.service to reflect the correct dicomincoming path."
echo "*****************************************************************************************"

exit 0
