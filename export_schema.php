<?php
// ------------------------------------------------------------------------------
// NiDB export_schema.php
// Regenerates the NiDB schema file (nidb.sql) from a live database, matching the
// format that phpMyAdmin's structure-only export produces (which the installer's
// UpgradeDatabase() parser in setup.php expects).
//
// Prints the schema to stdout. Usage (from the project root):
//   php export_schema.php [db] [user] [pass] [host] > src/setup/nidb.sql
//   (defaults: db=nidb user=nidb pass=password host=localhost)
//
// Normally run via the wrapper, which writes src/setup/nidb.sql for you:
//   ./export_schema.sh
// ------------------------------------------------------------------------------

$db   = $argv[1] ?? 'nidb';
$user = $argv[2] ?? 'nidb';
$pass = $argv[3] ?? 'password';
$host = $argv[4] ?? 'localhost';

mysqli_report(MYSQLI_REPORT_OFF);
$link = mysqli_connect($host, $user, $pass, $db);
if (!$link) {
	fwrite(STDERR, "ERROR: could not connect to database '$db' as '$user'@'$host': " . mysqli_connect_error() . "\n");
	exit(1);
}

/* server version for the header */
$verRes = mysqli_query($link, "SELECT VERSION()");
$serverVersion = mysqli_fetch_row($verRes)[0] ?? '';

/* list base tables (exclude views and already-deprecated tables), in phpMyAdmin (natural) order */
$tables = array();
$res = mysqli_query($link, "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
while ($row = mysqli_fetch_row($res)) {
	if (strpos($row[0], 'deprecated_') === 0) continue;
	$tables[] = $row[0];
}
usort($tables, 'strnatcasecmp');

/* parse each table's SHOW CREATE TABLE into columns / indexes / constraints / auto_increment */
$columns      = array();   // table => [ "`col` type ..." ]
$indexes      = array();   // table => [ "PRIMARY KEY (...)", "KEY `k` (...)" ]
$constraints  = array();   // table => [ "CONSTRAINT `c` FOREIGN KEY ..." ]
$engineLine   = array();   // table => ") ENGINE=... DEFAULT CHARSET=... COLLATE=..."
$autoIncMod   = array();   // table => "`col` type NOT NULL AUTO_INCREMENT"

foreach ($tables as $t) {
	$res = mysqli_query($link, "SHOW CREATE TABLE `$t`");
	$create = mysqli_fetch_assoc($res)['Create Table'] ?? '';
	$lines = explode("\n", $create);

	$columns[$t] = array();
	$indexes[$t] = array();
	$constraints[$t] = array();

	$n = count($lines);
	for ($i = 1; $i < $n; $i++) {          // skip line 0 ("CREATE TABLE `t` (")
		$raw = trim($lines[$i]);
		if ($raw === '') continue;

		/* the closing engine line */
		if (preg_match('/^\)\s*ENGINE=/i', $raw)) {
			$eng = $raw;
			$eng = preg_replace('/\s+AUTO_INCREMENT=\d+/i', '', $eng);   // schema-only: no start value
			$eng = preg_replace('/\s+PAGE_CHECKSUM=\d+/i', '', $eng);    // Aria option phpMyAdmin drops
			$eng = preg_replace('/\s+TRANSACTIONAL=\d+/i', '', $eng);    // Aria option phpMyAdmin drops
			$engineLine[$t] = $eng;
			continue;
		}

		$def = rtrim($raw, ',');
		/* phpMyAdmin upper-cases these type attributes */
		$def = preg_replace('/\bunsigned\b/i', 'UNSIGNED', $def);
		$def = preg_replace('/\bzerofill\b/i', 'ZEROFILL', $def);

		if (strlen($def) > 0 && $def[0] === '`') {
			/* a column definition */
			if (preg_match('/\sAUTO_INCREMENT\b/i', $def)) {
				$autoIncMod[$t] = $def;                                      // keep AUTO_INCREMENT for the MODIFY
				$def = preg_replace('/\s+AUTO_INCREMENT\b/i', '', $def);     // strip it from CREATE TABLE
			}
			$columns[$t][] = $def;
		}
		elseif (preg_match('/^CONSTRAINT\b/i', $def)) {
			$constraints[$t][] = $def;
		}
		else {
			/* PRIMARY KEY / UNIQUE KEY / KEY / FULLTEXT KEY / SPATIAL KEY */
			$indexes[$t][] = $def;
		}
	}
}

/* ---------------- build the output ---------------- */
$out  = "-- phpMyAdmin SQL Dump\n";
$out .= "-- version 5.2.3\n";
$out .= "-- https://www.phpmyadmin.net/\n";
$out .= "--\n";
$out .= "-- Host: $host\n";
$out .= "-- Generation Time: " . date('M d, Y \a\t h:i A') . "\n";
$out .= "-- Server version: $serverVersion\n";
$out .= "-- PHP Version: " . phpversion() . "\n";
$out .= "\n";
$out .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$out .= "START TRANSACTION;\n";
$out .= "SET time_zone = \"+00:00\";\n";
$out .= "\n\n";
$out .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
$out .= "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
$out .= "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
$out .= "/*!40101 SET NAMES utf8mb4 */;\n";
$out .= "\n";
$out .= "--\n-- Database: `$db`\n--\n";

/* table structures */
foreach ($tables as $t) {
	$out .= "\n-- --------------------------------------------------------\n";
	$out .= "\n--\n-- Table structure for table `$t`\n--\n\n";
	$out .= "CREATE TABLE `$t` (\n";
	$out .= implode(",\n", array_map(function($c) { return "  $c"; }, $columns[$t])) . "\n";
	$out .= $engineLine[$t] . ";\n";
}

/* indexes: PRIMARY/UNIQUE/KEY are combined into one ALTER TABLE; FULLTEXT/SPATIAL keys each get
   their own single-line ALTER TABLE (matching phpMyAdmin, which can't combine them). */
$out .= "\n--\n-- Indexes for dumped tables\n--\n";
foreach ($tables as $t) {
	if (empty($indexes[$t])) continue;
	$regular  = array();
	$separate = array();
	foreach ($indexes[$t] as $k) {
		if (preg_match('/^(FULLTEXT|SPATIAL)\s+KEY\b/i', $k)) $separate[] = $k;
		else                                                 $regular[]  = $k;
	}
	$out .= "\n--\n-- Indexes for table `$t`\n--\n";
	if (!empty($regular)) {
		$out .= "ALTER TABLE `$t`\n";
		$out .= implode(",\n", array_map(function($k) { return "  ADD $k"; }, $regular)) . ";\n";
	}
	foreach ($separate as $k) {
		$out .= "ALTER TABLE `$t` ADD $k;\n";
	}
}

/* auto_increment */
$out .= "\n--\n-- AUTO_INCREMENT for dumped tables\n--\n";
foreach ($tables as $t) {
	if (empty($autoIncMod[$t])) continue;
	$out .= "\n--\n-- AUTO_INCREMENT for table `$t`\n--\n";
	$out .= "ALTER TABLE `$t`\n";
	$out .= "  MODIFY " . $autoIncMod[$t] . ";\n";
}

/* constraints (only if any table has foreign keys) */
$haveConstraints = false;
foreach ($tables as $t) { if (!empty($constraints[$t])) { $haveConstraints = true; break; } }
if ($haveConstraints) {
	$out .= "\n--\n-- Constraints for dumped tables\n--\n";
	foreach ($tables as $t) {
		if (empty($constraints[$t])) continue;
		$out .= "\n--\n-- Constraints for table `$t`\n--\n";
		$out .= "ALTER TABLE `$t`\n";
		$out .= implode(",\n", array_map(function($c) { return "  ADD $c"; }, $constraints[$t])) . ";\n";
	}
}

$out .= "COMMIT;\n";
$out .= "\n";
$out .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";
$out .= "/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n";
$out .= "/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n";

echo $out;
