#!/bin/bash
# ------------------------------------------------------------------------------
# NiDB migrate_download_link.sh
#
# Removes the old public web download link /var/www/html/download (security issue P1).
# Web export zips used to be served by Apache straight from that link, with no login.
# They now live in [downloaddir] (outside the docroot) and are served by exportdownload.php,
# which checks that the user requested the export.
#
# Usage: migrate_download_link.sh [saved-link-target]
#   saved-link-target: the link's target, saved by the pre-install scriptlet. Needed when
#   upgrading from an old package that owned the link, because the package manager has
#   already removed the link by the time this runs.
#
# Steps:
#   1. Find the link target (the existing link, otherwise the saved target).
#   2. If [downloaddir] in /nidb/nidb.cfg doesn't match it, set [downloaddir] to the
#      target, so existing exports on custom download paths (e.g. /mnt/...) keep working.
#      A backup of nidb.cfg is saved first.
#   3. Remove the deprecated [webdownloaddir] line from nidb.cfg.
#   4. Delete the link. A real directory at that path is left alone (with a warning);
#      Apache denies access to /download either way (nidb.conf).
#
# Safe to run more than once.
# ------------------------------------------------------------------------------

# the paths can be overridden for testing
LINK="${NIDB_DOWNLOAD_LINK:-/var/www/html/download}"
CFG="${NIDB_CFG:-/nidb/nidb.cfg}"
TARGET="$1"

if [[ -L "$LINK" ]]; then
    TARGET="$(readlink "$LINK")"
fi
TARGET="${TARGET%/}"

# a relative link target is relative to the link's directory
if [[ -n "$TARGET" && "${TARGET:0:1}" != "/" ]]; then
    TARGET="$(readlink -m "$(dirname "$LINK")/$TARGET")"
fi

if [[ -f "$CFG" ]]; then
    # current [downloaddir] value
    CURRENT="$(sed -n '/^\[downloaddir\]/{s/^\[downloaddir\][[:space:]]*=[[:space:]]*//;s/[[:space:]]*$//;p;}' "$CFG" | head -n 1)"
    CURRENT="${CURRENT%/}"

    NEEDCFGCHANGE=0
    if [[ -n "$TARGET" && "$TARGET" != "$CURRENT" ]]; then NEEDCFGCHANGE=1; fi
    if grep -q '^\[webdownloaddir\]' "$CFG"; then NEEDCFGCHANGE=1; fi

    if [[ $NEEDCFGCHANGE -eq 1 ]]; then
        BACKUP="$CFG.bak.$(date +%Y%m%d%H%M%S)"
        cp -p "$CFG" "$BACKUP"
        echo "Saved a copy of $CFG as $BACKUP"

        if [[ -n "$TARGET" && "$TARGET" != "$CURRENT" ]]; then
            echo "Setting [downloaddir] to the old web download link target [$TARGET] (was [$CURRENT])"
        fi

        # rewrite line by line (no sed/awk replacement, so any characters in the path are safe).
        # Writing back with cat keeps the file's owner and permissions
        TMP="$(mktemp)"
        FOUND=0
        while IFS= read -r line || [[ -n "$line" ]]; do
            if [[ "$line" =~ ^\[webdownloaddir\] ]]; then
                continue
            fi
            if [[ "$line" =~ ^\[downloaddir\] ]]; then
                if [[ $FOUND -eq 1 ]]; then continue; fi
                FOUND=1
                if [[ -n "$TARGET" ]]; then
                    printf '[downloaddir] = %s\n' "$TARGET" >> "$TMP"
                    continue
                fi
            fi
            printf '%s\n' "$line" >> "$TMP"
        done < "$CFG"
        if [[ $FOUND -eq 0 && -n "$TARGET" ]]; then
            printf '[downloaddir] = %s\n' "$TARGET" >> "$TMP"
        fi
        cat "$TMP" > "$CFG"
        rm -f "$TMP"
    fi
elif [[ -n "$TARGET" ]]; then
    echo "WARNING: $CFG not found. Set [downloaddir] = $TARGET in the config so existing web exports can still be downloaded"
fi

if [[ -L "$LINK" ]]; then
    echo "Removing the public web download link $LINK (web exports are now served by exportdownload.php)"
    rm -f "$LINK"
elif [[ -e "$LINK" ]]; then
    echo "WARNING: $LINK is a real directory or file, not a link, so it was not removed. Apache blocks access to /download, but move its contents into [downloaddir] and delete it"
fi

exit 0
