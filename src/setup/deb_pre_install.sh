#!/bin/bash
# ------------------------------------------------------------------------------
# NiDB Debian pre-install script (DEBIAN/preinst)
# dpkg runs this before unpacking the package's files.
#
# Remember the current web download link target (default or custom). Older
# packages shipped /var/www/html/download; when upgrading from one of those, dpkg
# removes that link during unpack. deb_post_install.sh restores the saved target.
# ------------------------------------------------------------------------------

# dpkg calls preinst as: preinst install|upgrade <old-version>
if [[ "$1" == "install" || "$1" == "upgrade" ]] && [[ -L /var/www/html/download ]]; then
    mkdir -p /var/lib/nidb
    readlink /var/www/html/download > /var/lib/nidb/download_link
fi

exit 0
