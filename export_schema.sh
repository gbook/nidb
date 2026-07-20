#!/bin/bash
# ------------------------------------------------------------------------------
# NiDB export_schema.sh
# Regenerates src/setup/nidb.sql from a live database, in the phpMyAdmin export
# format that the installer (setup.php UpgradeDatabase) expects. Replaces the
# manual "export schema from phpMyAdmin" step.
#
# Run from the project root. Usage:
#   ./export_schema.sh [db] [user] [pass] [host]
#   (defaults: db=nidb user=nidb pass=password host=localhost)
# ------------------------------------------------------------------------------
set -e
cd "$(dirname "$0")"

DB="${1:-nidb}"
DBUSER="${2:-nidb}"
DBPASS="${3:-password}"
DBHOST="${4:-localhost}"

SCHEMA="src/setup/nidb.sql"

TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT

php export_schema.php "$DB" "$DBUSER" "$DBPASS" "$DBHOST" > "$TMP"

if [ ! -s "$TMP" ]; then
	echo "ERROR: schema export produced no output. $SCHEMA not changed." >&2
	exit 1
fi

if [ -f "$SCHEMA" ]; then
	echo "Schema changes vs current $SCHEMA (ignoring generation timestamp):"
	echo "-----------------------------------------------------------------"
	diff <(grep -v '^-- Generation Time:' "$SCHEMA") <(grep -v '^-- Generation Time:' "$TMP") || true
	echo "-----------------------------------------------------------------"
	cp -f "$SCHEMA" "$SCHEMA.bak"
	echo "Previous schema backed up to $SCHEMA.bak"
fi

cp -f "$TMP" "$SCHEMA"
echo "Wrote $SCHEMA ($(wc -l < "$SCHEMA") lines) from database '$DB' on '$DBHOST'"
