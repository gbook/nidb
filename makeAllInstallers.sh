#!/bin/bash
# Builds the NiDB .rpm and .deb installers on all supported WSL distros.
# Adapted from squirrel's makeAllInstallers.sh. Run from the project root in a
# WSL distro (requires wsl.exe); the host distro only launches the builds.
#
# The other distros access the source via /mnt/wsl/ (shared tmpfs across all
# WSL2 distros in the same VM), where it is bind-mounted.
#
#   rpm -> makeInstallerRHEL<N>.sh, which rebuilds from source inside rpmbuild
#   deb -> build-rpm.sh into bin/debian<N>/ (a per-distro build dir, so distros
#          never share object files), then makeInstallerDebian<N>.sh packages it.
#          The Debian version is read from the distro's /etc/os-release.
#
# Finished installers are collected into ./installers/ with distro-tagged names,
# along with a <distro>.log of each distro's build output.

SRCDIR="$(pwd)"
SHARED=/mnt/wsl/nidb-build
OUTDIR="$SRCDIR/installers"
QMAKEBIN='~/Qt/6.9.3/gcc_64/bin/qmake'
# Force a clean Linux PATH for wsl.exe invocations. WSL appends the Windows PATH,
# whose entries contain spaces (e.g. ".../Program Files/...") and break the builds.
CLEANPATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
# the bind-mounted repo may appear owned by another user inside a distro; let git use it anyway
GITSAFE="GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0=safe.directory GIT_CONFIG_VALUE_0=$SHARED"

# .deb package name, kept in sync by update_version.sh (e.g. nidb_2026.9.1608)
DEB_PACKAGE=nidb_$(grep -m1 '^VERSION=' makeInstallerDebian12.sh | cut -d= -f2)

# Each entry: "<wsl-distro>:<type>" (type is rpm or deb)
INSTALLERS=(
	"AlmaLinux-8:rpm"
	"AlmaLinux-9:rpm"
	"AlmaLinux-10:rpm"
	"Debian:deb"
)
declare -A RESULTS

if [ ! -f makeAllInstallers.sh ]; then
	echo "Run this script from the NiDB project root"
	exit 1
fi
command -v wsl.exe >/dev/null 2>&1 || { echo "wsl.exe not found; this script must be run from WSL"; exit 1; }

# start with an empty output dir so it only holds installers and logs from this run
mkdir -p "$OUTDIR"
rm -f "$OUTDIR"/nidb*.rpm "$OUTDIR"/nidb*.deb "$OUTDIR"/*.log

echo "Mounting source at $SHARED (shared across all WSL2 distros)..."
sudo mkdir -p $SHARED
sudo mount --bind "$SRCDIR" $SHARED || exit 1
trap 'echo "Unmounting $SHARED..."; sudo umount $SHARED' EXIT

for ENTRY in "${INSTALLERS[@]}"; do
	IFS=':' read -r DISTRO TYPE <<< "$ENTRY"
	echo ""
	echo "=========================================="
	echo "  $DISTRO: building .$TYPE"
	echo "=========================================="

	# --exec runs bash directly; with "--" wsl.exe passes the command through the distro's
	# default shell first, which expands the \$ variables below to empty strings.
	# --cd / avoids "Failed to translate" (this distro's cwd has no path in the other distros).
	# ${VERSION_ID:?} aborts the command if os-release didn't provide a version.
	# each distro's output is also saved to installers/<distro>.log for searching afterwards
	{ case "$TYPE" in
		rpm)
			# The %{?dist} tag (el8/el9/el10) keeps the .rpm filenames distinct.
			wsl.exe -d "$DISTRO" --cd / --exec bash -c \
				". /etc/os-release && VERSION_ID=\${VERSION_ID%%.*} && cd $SHARED && \
				 PATH=$CLEANPATH $GITSAFE sh makeInstallerRHEL\${VERSION_ID:?}.sh && \
				 mkdir -p $SHARED/installers && \
				 cp -v ~/rpmbuild/RPMS/x86_64/nidb-*.rpm $SHARED/installers/"
			;;
		deb)
			# Check for libgl-dev first (the Debian equivalent of the specs' BuildRequires
			# mesa-libGL-devel) so a missing package fails fast instead of at the final link.
			# Build and package in one step, and delete the previous binaries first
			# so a failed build can never package stale ones. Both Debian versions
			# produce the same $DEB_PACKAGE.deb, so it is renamed with the distro tag.
			wsl.exe -d "$DISTRO" --cd / --exec bash -c \
				". /etc/os-release && TAG=debian\${VERSION_ID:?} && cd $SHARED && \
				 { dpkg -s libgl-dev >/dev/null 2>&1 || { echo 'libgl-dev is required to link nidb (Qt adds -lGL). Install it with: sudo apt install libgl-dev'; exit 1; }; } && \
				 rm -f bin/\$TAG/nidb/nidb bin/\$TAG/squirrel/squirrel && \
				 PATH=$CLEANPATH bash build-rpm.sh $QMAKEBIN $SHARED/src $SHARED/bin/\$TAG && \
				 PATH=$CLEANPATH bash makeInstallerDebian\$VERSION_ID.sh bin/\$TAG && \
				 mkdir -p $SHARED/installers && \
				 mv -v $DEB_PACKAGE.deb $SHARED/installers/${DEB_PACKAGE}_\$TAG.deb"
			;;
	esac; } 2>&1 | tee "$OUTDIR/$DISTRO.log"
	if [ "${PIPESTATUS[0]}" -eq 0 ]; then
		RESULTS[$DISTRO]="SUCCESS"
	else
		RESULTS[$DISTRO]="FAILED"
	fi
done

echo ""
echo "========== Installer Summary =========="
for ENTRY in "${INSTALLERS[@]}"; do
	IFS=':' read -r DISTRO _ <<< "$ENTRY"
	printf "  %-20s %s\n" "$DISTRO" "${RESULTS[$DISTRO]}"
done
echo "======================================="
echo "Installers collected in: $OUTDIR"
ls -1 "$OUTDIR" 2>/dev/null | sed 's/^/  /'
