#!/bin/bash
# ------------------------------------------------------------------------------
# NiDB Debian 12 (bookworm) package builder
# Builds a .deb that mirrors the RHEL RPMs: it declares its package Depends: and
# ships a postinst maintainer script (src/setup/deb_post_install.sh) that dpkg
# runs after unpacking -- the Debian equivalents of the spec's Requires: and
# %post. Run from the project root AFTER building the binaries (build-rpm.sh or
# equivalent), so bin/nidb/nidb and bin/squirrel/ exist. An optional first argument
# names a different build directory (e.g. bin/debian12), as used by makeAllInstallers.sh.
# ------------------------------------------------------------------------------
set -e
cd "$(dirname "$0")"

VERSION=2026.9.1623
QTDIR=~/Qt/6.9.3/gcc_64
BUILDDIR=${1:-bin}                                  # where build-rpm.sh put the binaries

PACKAGE=nidb_${VERSION}
DEBDIR=$PACKAGE/DEBIAN
LIBDIR=$PACKAGE/usr/lib/x86_64-linux-gnu           # multiarch shared-lib location
BINDIR=$PACKAGE/usr/local/bin
NIDBDIR=$PACKAGE/nidb
WEBDIR=$PACKAGE/var/www/html
DCMTKSHARE=$PACKAGE/usr/local/share/dcmtk-3.7.0
SQLDRV_SYS=$LIBDIR/sqldrivers
SQLDRV_NIDB=$NIDBDIR/bin/sqldrivers

# start clean so a rebuild never carries stale files
rm -rf "$PACKAGE"

mkdir -p "$DEBDIR" "$LIBDIR" "$BINDIR" "$WEBDIR" "$DCMTKSHARE" \
         "$NIDBDIR/bin" "$NIDBDIR/lock" "$NIDBDIR/logs" "$NIDBDIR/qcmodules" "$NIDBDIR/setup" \
         "$SQLDRV_SYS" "$SQLDRV_NIDB"

# ----- program files (mirrors the RPM %install) -----
echo 'Copying NiDB binaries, web files, tools, qcmodules, and setup files...'
cp -f  $BUILDDIR/nidb/nidb  "$NIDBDIR/bin/"
cp -f  $BUILDDIR/squirrel/squirrel  "$BINDIR/"          # squirrel command-line utility
cp -rf tools/*                        "$NIDBDIR/bin/"     # bundled helper tools
cp -rf src/qcmodules/*                "$NIDBDIR/qcmodules/"
cp -f  src/setup/*                    "$NIDBDIR/setup/"
cp -rf src/web/*                      "$WEBDIR/"
rm -f  "$WEBDIR/download"             # do NOT package the download symlink; the postinst creates it if absent so upgrades never clobber a custom download path

# the postinst is the Debian equivalent of the RPM %post; ship a copy under
# /nidb/setup too (parallel to the RPM), but the one that dpkg runs is DEBIAN/postinst
cp -f  src/setup/deb_post_install.sh  "$DEBDIR/postinst"
chmod 0755 "$DEBDIR/postinst"
# the preinst saves the current download link target so the postinst can restore it after dpkg
# removes a link shipped by an older package
cp -f  src/setup/deb_pre_install.sh   "$DEBDIR/preinst"
chmod 0755 "$DEBDIR/preinst"

# ----- shared libraries -----
echo 'Copying shared libraries...'
cp -f $BUILDDIR/squirrel/libsquirrel*  "$LIBDIR/" 2>/dev/null || true
cp -f $BUILDDIR/bit7z/libbit7z64.a  "$LIBDIR/" 2>/dev/null || true
# DCMTK (versioned .so files; wildcard tolerates version differences)
cp -f /usr/local/lib/libcmr*          "$LIBDIR/" 2>/dev/null || true
cp -f /usr/local/lib/libdcm*          "$LIBDIR/" 2>/dev/null || true
cp -f /usr/local/lib/libi2d*          "$LIBDIR/" 2>/dev/null || true
cp -f /usr/local/lib/libijg*          "$LIBDIR/" 2>/dev/null || true
cp -f /usr/local/lib/libof*           "$LIBDIR/" 2>/dev/null || true
# Qt runtime libraries
cp -f $QTDIR/lib/libQt6Core.so.6      "$LIBDIR/" 2>/dev/null || true
cp -f $QTDIR/lib/libQt6Gui.so.6       "$LIBDIR/" 2>/dev/null || true
cp -f $QTDIR/lib/libQt6DBus.so.6      "$LIBDIR/" 2>/dev/null || true   # needed by libQt6Gui
cp -f $QTDIR/lib/libQt6Network.so.6   "$LIBDIR/" 2>/dev/null || true
cp -f $QTDIR/lib/libQt6Sql.so.6       "$LIBDIR/" 2>/dev/null || true
cp -f $QTDIR/lib/libicu*              "$LIBDIR/" 2>/dev/null || true
# Qt SQL drivers (in both the system path and next to the binary; the nidb binary
# sometimes only looks alongside itself)
cp -f $QTDIR/plugins/sqldrivers/libqsqlmysql.so  "$SQLDRV_SYS/"  2>/dev/null || true
cp -f $QTDIR/plugins/sqldrivers/libqsqlmysql.so  "$SQLDRV_NIDB/" 2>/dev/null || true
cp -f $QTDIR/plugins/sqldrivers/libqsqlite.so    "$SQLDRV_SYS/"  2>/dev/null || true
cp -f $QTDIR/plugins/sqldrivers/libqsqlite.so    "$SQLDRV_NIDB/" 2>/dev/null || true

# ----- DCMTK data dictionaries -----
cp -rf /usr/local/share/dcmtk-3.7.0/* "$DCMTKSHARE/" 2>/dev/null || true

# ----- control file (Depends: is the Debian equivalent of the RPM Requires:) -----
echo "Writing DEBIAN/control..."
cat > "$DEBDIR/control" <<EOF
Package: nidb
Version: ${VERSION}
Section: science
Priority: optional
Architecture: amd64
Maintainer: Greg Book <gregory.a.book@gmail.com>
Depends: apache2, mariadb-server, mariadb-client, mariadb-backup, libmariadb3, php-fpm, php-cli, php-mysql, php-gd, php-mbstring, php-opcache, php-curl, imagemagick, libimage-exiftool-perl, openssl, zip, unzip, p7zip-full, default-jre, libgl1, libegl1, libx11-6, libxkbcommon0, libfontconfig1, libfreetype6, libglib2.0-0, shellcheck
Description: NeuroInformatics Database
 NeuroInformatics Database (NiDB) is a full neuroimaging database system to
 store, retrieve, analyze, and distribute neuroscience data.
EOF

# ----- build the package (fakeroot so shipped files are owned root:root) -----
echo "Building package..."
if command -v fakeroot >/dev/null 2>&1; then
	fakeroot dpkg-deb --build "$PACKAGE"
else
	echo "WARNING: fakeroot not found; package files will be owned by the build user." >&2
	dpkg-deb --build "$PACKAGE"
fi

# remove the staging tree now that the .deb is built (on failure set -e exits first, leaving it for inspection)
rm -rf "$PACKAGE"

echo "Built ${PACKAGE}.deb"
