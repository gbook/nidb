<?
 // ------------------------------------------------------------------------------
 // NiDB importinspect_functions.php
 // Copyright (C) 2004 - 2026
 // Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
 // Olin Neuropsychiatry Research Center, Hartford Hospital
 // ------------------------------------------------------------------------------
 // GPLv3 License:

 // This program is free software: you can redistribute it and/or modify
 // it under the terms of the GNU General Public License as published by
 // the Free Software Foundation, either version 3 of the License, or
 // (at your option) any later version.

 // This program is distributed in the hope that it will be useful,
 // but WITHOUT ANY WARRANTY; without even the implied warranty of
 // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 // GNU General Public License for more details.

 // You should have received a copy of the GNU General Public License
 // along with this program.  If not, see <http://www.gnu.org/licenses/>.
 // ------------------------------------------------------------------------------

 /* Universal import: inspecting data before it is uploaded. See doc/universal-import.md §5 and §6.2

	A profile describes the data: every file's relative path, size, and modification time, plus
	header details read from a sample of the files. For local data the browser builds the profile
	(scripts/importinspect.js). For an NFS path, IIBuildNfsProfile() builds the same profile on the
	server. IIAnalyzeProfile() then runs the detectors on the profile and returns NiDB's guesses.

	Patient information is never put in a profile: header readers report only whether a patient
	field is populated, and identifiers (PatientID, study and series UIDs) are salted hashes, used
	only to count how many distinct values were seen.

	The sampling rules, group keys, and header readers exist in both this file and
	scripts/importinspect.js. Keep the two in step. */

	if (!defined("LEGIT_REQUEST")) die ("This page cannot be called directly.");

	define("II_PROFILEVERSION", 1);
	define("II_MAXSAMPLESPERGROUP", 6);   /* files sampled per extension group */
	define("II_MAXSAMPLES", 400);         /* total files sampled */
	define("II_HEADBYTES", 65536);        /* bytes read from the start of each sampled file */
	define("II_MAXJSONBYTES", 65536);     /* larger .json files are not parsed */
	define("II_MAXNFSFILES", 500000);     /* NFS listing stops after this many files */
	define("II_MAXPROFILEFILES", 2000000);
	define("II_MAXMATCHSUBJECTS", 20000); /* subject IDs checked against NiDB per request */
	define("II_MAXMATCHSTUDIES", 100000);


	/* -------------------------------------------- */
	/* ------- IIRegistry ------------------------- */
	/* -------------------------------------------- */
	/* Known file types (doc/universal-import.md §6.2). A future import_filetypes table would hold this.
		exts       extensions checked against the magic bytes ('' = no extension)
		magic      alternatives; each is a list of [offset, bytes] that must all match
		modality   default modality code (modalities table), or '' if it comes from the header or is unknown
		reader     header reader: dicom, nifti, niftigz, edf, brainvision, par, json, table, or ''
		companions extensions that travel with this type, matched by file name (e.g. .json next to .nii.gz)
		kind       primary (the default), bundle (a directory is the file, e.g. x.mff/), archive, table */
	function IIRegistry() {
		static $registry = null;
		if ($registry !== null) return $registry;

		$r = array();
		$r[] = IIType('DICOM', 'DICOM', array('dcm','dicom','ima',''), array(array(array(128, 'DICM'))), '', 'dicom');
		$r[] = IIType('NIFTI', 'NIfTI', array('nii'), array(array(array(344, "n+1\0")), array(array(4, "n+2\0"))), 'MR', 'nifti', array('json','bval','bvec'));
		$r[] = IIType('NIFTIGZ', 'NIfTI (gzipped)', array('nii.gz'), array(array(array(0, "\x1f\x8b"))), 'MR', 'niftigz', array('json','bval','bvec'));
		$r[] = IIType('ANALYZE', 'Analyze / NIfTI pair', array('hdr'), array(array(array(0, "\x5c\x01\x00\x00")), array(array(0, "\x00\x00\x01\x5c"))), 'MR', 'nifti', array('img'));
		$r[] = IIType('PARREC', 'Philips PAR/REC', array('par'), array(array(array(0, "# === DATA DESCRIPTION FILE"))), 'MR', 'par', array('rec'));
		$r[] = IIType('BRAINVISION', 'BrainVision', array('vhdr'), array(array(array(0, "Brain"))), 'EEG', 'brainvision', array('vmrk','eeg'));
		$r[] = IIType('EDF', 'EDF / EDF+', array('edf','rec'), array(array(array(0, "0       "))), 'EEG', 'edf');
		$r[] = IIType('BDF', 'BioSemi BDF', array('bdf'), array(array(array(0, "\xffBIOSEMI"))), 'EEG', 'edf');
		$r[] = IIType('EYELINK', 'EyeLink EDF', array('edf'), array(array(array(0, "SR_RESEARCH"))), 'ET', '');
		$r[] = IIType('EYELINKASC', 'EyeLink ASCII', array('asc'), array(array(array(0, "** CONVERTED"))), 'ET', '');
		$r[] = IIType('EEGLAB', 'EEGLAB', array('set'), array(array(array(0, "MATLAB"))), 'EEG', '', array('fdt'));
		$r[] = IIType('NEUROSCAN', 'Neuroscan CNT', array('cnt'), array(), 'EEG', '');
		$r[] = IIType('EGIMFF', 'EGI MFF', array('mff'), array(), 'EEG', '', array(), 'bundle');
		$r[] = IIType('FIF', 'Elekta/MEGIN FIF', array('fif'), array(array(array(0, "\x00\x00\x00\x64"))), 'MEG', '');
		$r[] = IIType('CTF', 'CTF MEG', array('ds'), array(), 'MEG', '', array(), 'bundle');
		$r[] = IIType('KIT', 'KIT/Yokogawa MEG', array('con','sqd'), array(), 'MEG', '');
		$r[] = IIType('MP4', 'MP4 / QuickTime video', array('mp4','m4v','mov'), array(array(array(4, "ftyp"))), 'VIDEO', '');
		$r[] = IIType('AVI', 'AVI video', array('avi'), array(array(array(0, "RIFF"), array(8, "AVI "))), 'VIDEO', '');
		$r[] = IIType('MKV', 'Matroska / WebM video', array('mkv','webm'), array(array(array(0, "\x1a\x45\xdf\xa3"))), 'VIDEO', '');
		$r[] = IIType('WMV', 'Windows Media video', array('wmv'), array(array(array(0, "\x30\x26\xb2\x75\x8e\x66\xcf\x11"))), 'VIDEO', '');
		$r[] = IIType('WAV', 'WAV audio', array('wav'), array(array(array(0, "RIFF"), array(8, "WAVE"))), 'AUDIO', '');
		$r[] = IIType('FLAC', 'FLAC audio', array('flac'), array(array(array(0, "fLaC"))), 'AUDIO', '');
		$r[] = IIType('MP3', 'MP3 audio', array('mp3'), array(array(array(0, "ID3")), array(array(0, "\xff\xfb"))), 'AUDIO', '');
		$r[] = IIType('M4A', 'M4A audio', array('m4a'), array(array(array(4, "ftyp"))), 'AUDIO', '');
		$r[] = IIType('EPRIME', 'E-Prime data', array('edat','edat2','edat3'), array(), 'TASK', '');
		$r[] = IIType('EPRIMETXT', 'E-Prime text log', array('txt'), array(array(array(0, "\xff\xfe*\x00*\x00*\x00")), array(array(0, "*** Header Start"))), 'TASK', '');
		$r[] = IIType('PRESENTATION', 'Presentation log', array('log'), array(array(array(0, "Scenario -"))), 'TASK', '');
		$r[] = IIType('PSYDAT', 'PsychoPy data', array('psydat'), array(), 'TASK', '');
		$r[] = IIType('BIOPAC', 'BIOPAC AcqKnowledge', array('acq'), array(), 'GSR', '');
		$r[] = IIType('PLINK', 'PLINK binary genotypes', array('bed'), array(array(array(0, "\x6c\x1b\x01"))), 'SNP', '', array('bim','fam'));
		$r[] = IIType('VCF', 'VCF genotypes', array('vcf','vcf.gz'), array(array(array(0, "##fileformat=VCF")), array(array(0, "\x1f\x8b"))), 'SNP', '');
		$r[] = IIType('IMAGE', 'Image', array('png','jpg','jpeg','tif','tiff','gif','bmp'), array(array(array(0, "\x89PNG")), array(array(0, "\xff\xd8\xff")), array(array(0, "II*\x00")), array(array(0, "MM\x00*")), array(array(0, "GIF8")), array(array(0, "BM"))), 'IMAGE', '');
		$r[] = IIType('PDF', 'PDF document', array('pdf'), array(array(array(0, "%PDF"))), '', '');
		$r[] = IIType('MAT', 'MATLAB data', array('mat'), array(array(array(0, "MATLAB"))), '', '');
		$r[] = IIType('JSON', 'JSON', array('json'), array(), '', 'json');
		$r[] = IIType('TABLE', 'Text table', array('csv','tsv','txt'), array(), '', 'table', array(), 'table');
		$r[] = IIType('SPREADSHEET', 'Spreadsheet', array('xlsx','xls','ods'), array(array(array(0, "PK\x03\x04")), array(array(0, "\xd0\xcf\x11\xe0"))), '', '', array(), 'table');
		$r[] = IIType('SQUIRREL', 'squirrel package', array('sqrl'), array(array(array(0, "7z\xbc\xaf\x27\x1c")), array(array(0, "PK\x03\x04"))), '', '', array(), 'archive');
		$r[] = IIType('ZIP', 'ZIP archive', array('zip'), array(array(array(0, "PK\x03\x04"))), '', '', array(), 'archive');
		$r[] = IIType('TARGZ', 'tar.gz archive', array('tar.gz','tgz'), array(array(array(0, "\x1f\x8b"))), '', '', array(), 'archive');
		$r[] = IIType('TAR', 'tar archive', array('tar'), array(array(array(257, "ustar"))), '', '', array(), 'archive');

		$registry = $r;
		return $registry;
	}


	/* -------------------------------------------- */
	/* ------- IIType ----------------------------- */
	/* -------------------------------------------- */
	/* one registry entry. Magic bytes are stored as hex, which is also what the browser gets */
	function IIType($code, $name, $exts, $magic, $modality, $reader, $companions = array(), $kind = 'primary') {
		$hexmagic = array();
		foreach ($magic as $alt) {
			$hexalt = array();
			foreach ($alt as $m)
				$hexalt[] = array($m[0], bin2hex($m[1]));
			$hexmagic[] = $hexalt;
		}
		return array('code' => $code, 'name' => $name, 'exts' => $exts, 'magic' => $hexmagic, 'modality' => $modality, 'reader' => $reader, 'companions' => $companions, 'kind' => $kind);
	}


	/* -------------------------------------------- */
	/* ------- IIRegistryByCode ------------------- */
	/* -------------------------------------------- */
	function IIRegistryByCode() {
		$bycode = array();
		foreach (IIRegistry() as $t)
			$bycode[$t['code']] = $t;
		return $bycode;
	}


	/* -------------------------------------------- */
	/* ------- IIBundleExts ----------------------- */
	/* -------------------------------------------- */
	/* extensions of types where a directory is the file (e.g. x.mff/) */
	function IIBundleExts() {
		$exts = array();
		foreach (IIRegistry() as $t)
			if ($t['kind'] == 'bundle')
				$exts = array_merge($exts, $t['exts']);
		return $exts;
	}


	/* -------------------------------------------- */
	/* ------- IIBrowserConfig -------------------- */
	/* -------------------------------------------- */
	/* what scripts/importinspect.js needs: the registry and the sampling limits */
	function IIBrowserConfig() {
		$types = array();
		foreach (IIRegistry() as $t)
			$types[] = array('code' => $t['code'], 'exts' => $t['exts'], 'magic' => $t['magic'], 'reader' => $t['reader']);

		return array(
			'version' => II_PROFILEVERSION,
			'types' => $types,
			'bundles' => IIBundleExts(),
			'maxpergroup' => II_MAXSAMPLESPERGROUP,
			'maxsamples' => II_MAXSAMPLES,
			'headbytes' => II_HEADBYTES,
			'maxjsonbytes' => II_MAXJSONBYTES
		);
	}


	/* ------------------------------------ grouping and sampling ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- IIIsIgnoredName -------------------- */
	/* -------------------------------------------- */
	/* operating system and editor clutter, which is suggested as ignored */
	function IIIsIgnoredName($name) {
		$name = strtolower($name);
		if (in_array($name, array('.ds_store', 'thumbs.db', 'desktop.ini', '.localized', "icon\r"), true)) return true;
		if ((substr($name, 0, 2) == '._') || (substr($name, 0, 2) == '~$')) return true;
		return false;
	}


	/* -------------------------------------------- */
	/* ------- IIGroupKey ------------------------- */
	/* -------------------------------------------- */
	/* the group a file belongs to: its extension (lowercase, compound extensions like nii.gz kept
	   whole), '' for no extension, '#ignore' for clutter, or a bundle extension if the file is
	   inside a bundle directory. Extensions that aren't word-like (e.g. '.0001' or the tail of a
	   DICOM UID) count as no extension. Same rules as GroupKey() in scripts/importinspect.js */
	function IIGroupKey($relpath, $bundles) {
		$parts = explode('/', $relpath);
		$n = count($parts);
		for ($i = 0; $i < $n - 1; $i++) {
			$seg = strtolower($parts[$i]);
			foreach ($bundles as $b)
				if ((strlen($seg) > strlen($b) + 1) && (substr($seg, -strlen($b) - 1) == ".$b"))
					return $b;
		}

		$name = strtolower($parts[$n - 1]);
		if (IIIsIgnoredName($name)) return '#ignore';

		foreach (array('nii.gz', 'tar.gz', 'vcf.gz') as $c)
			if ((strlen($name) > strlen($c) + 1) && (substr($name, -strlen($c) - 1) == ".$c"))
				return $c;

		$dot = strrpos($name, '.');
		if (($dot === false) || ($dot == 0)) return '';
		$ext = substr($name, $dot + 1);
		if (preg_match('/^[a-z][a-z0-9]{0,7}$/', $ext)) return $ext;
		return '';
	}


	/* -------------------------------------------- */
	/* ------- IISpecialName ---------------------- */
	/* -------------------------------------------- */
	/* files that describe a whole dataset, always sampled when near the top */
	function IISpecialName($name) {
		$name = strtolower($name);
		if (in_array($name, array('dataset_description.json', 'participants.tsv', 'participants.json', 'squirrel.json'), true)) return $name;
		if ($name == 'dicomdir') return $name;
		return '';
	}


	/* -------------------------------------------- */
	/* ------- IISelectSamples -------------------- */
	/* -------------------------------------------- */
	/* indexes (into $paths) of the files to read headers from: up to II_MAXSAMPLESPERGROUP
	   per group, spread evenly through the group, plus dataset description files near the top.
	   Same rules as SelectSamples() in scripts/importinspect.js */
	function IISelectSamples($paths, $bundles) {
		$groups = array();
		$samples = array();
		foreach ($paths as $i => $p) {
			$parts = explode('/', $p);
			$special = IISpecialName(end($parts));
			if (($special != '') && ($special != 'dicomdir') && (count($parts) <= 3))
				$samples[$i] = true;

			$key = IIGroupKey($p, $bundles);
			if (($key == '#ignore') || in_array($key, $bundles, true)) continue;
			$groups[$key][] = $i;
		}

		foreach ($groups as $key => $idx) {
			$n = count($idx);
			$m = min($n, II_MAXSAMPLESPERGROUP);
			for ($j = 0; $j < $m; $j++) {
				if (count($samples) >= II_MAXSAMPLES) break 2;
				$pos = ($m == 1) ? 0 : (int)floor($j * ($n - 1) / ($m - 1));
				$samples[$idx[$pos]] = true;
			}
		}
		return array_keys($samples);
	}


	/* -------------------------------------------- */
	/* ------- IIMatchMagic ----------------------- */
	/* -------------------------------------------- */
	/* true if $bytes matches any of the magic alternatives (each a list of [offset, hex]) */
	function IIMatchMagic($bytes, $magic) {
		foreach ($magic as $alt) {
			$ok = (count($alt) > 0);
			foreach ($alt as $m) {
				$want = hex2bin($m[1]);
				if (substr($bytes, $m[0], strlen($want)) !== $want) { $ok = false; break; }
			}
			if ($ok) return true;
		}
		return false;
	}


	/* -------------------------------------------- */
	/* ------- IIDetectType ----------------------- */
	/* -------------------------------------------- */
	/* the registry type whose magic bytes match: types for this extension first, then any type.
	   Returns the type code, or '' */
	function IIDetectType($bytes, $key) {
		$registry = IIRegistry();
		foreach ($registry as $t)
			if (in_array($key, $t['exts'], true) && (count($t['magic']) > 0) && IIMatchMagic($bytes, $t['magic']))
				return $t['code'];
		foreach ($registry as $t)
			if ((count($t['magic']) > 0) && IIMatchMagic($bytes, $t['magic']))
				return $t['code'];
		return '';
	}


	/* -------------------------------------------- */
	/* ------- IIIdShape -------------------------- */
	/* -------------------------------------------- */
	/* the format of an identifier without its value: letters become A/a, digits #. 'S012' -> 'A###' */
	function IIIdShape($s) {
		$s = substr((string)$s, 0, 64);
		$s = preg_replace('/[A-Z]/', 'A', $s);
		$s = preg_replace('/[a-z]/', 'a', $s);
		return preg_replace('/[0-9]/', '#', $s);
	}


	/* ------------------------------------ server-side (NFS) profile ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- IIResolveNfsPath ------------------- */
	/* -------------------------------------------- */
	/* the real path of an NFS directory, if it is inside [mountdir]. Returns '' and sets $err otherwise */
	function IIResolveNfsPath($path, &$err) {
		$err = '';
		$mountdir = realpath(trim($GLOBALS['cfg']['mountdir'] ?? ''));
		if (($mountdir === false) || ($mountdir == '/')) {
			$err = "Inspecting server paths is not available: [mountdir] is not set in the NiDB configuration";
			return '';
		}

		$real = realpath(trim($path));
		if (($real === false) || !is_dir($real)) {
			$err = "The path was not found on the server, or is not a directory";
			return '';
		}
		if (($real != $mountdir) && (substr($real, 0, strlen($mountdir) + 1) !== $mountdir . '/')) {
			$err = "Only paths under the server's mount directory ($mountdir) can be inspected";
			return '';
		}
		if (!is_readable($real)) {
			$err = "The path is not readable by the NiDB web server";
			return '';
		}
		return $real;
	}


	/* -------------------------------------------- */
	/* ------- IIBuildNfsProfile ------------------ */
	/* -------------------------------------------- */
	/* the same profile scripts/importinspect.js builds, for a directory on the server.
	   Symlinked directories are not followed, and a sampled file is only read if its real path is
	   inside $root */
	function IIBuildNfsProfile($root) {
		set_time_limit(600);

		$files = array();
		$truncated = false;
		$rootlen = strlen($root);
		try {
			$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
			foreach ($it as $f) {
				if (!$f->isFile()) continue;
				/* a symlinked file is listed only if it points inside $root */
				if ($f->isLink()) {
					$target = realpath($f->getPathname());
					if (($target === false) || (substr($target, 0, $rootlen + 1) !== $root . '/')) continue;
				}
				if (count($files) >= II_MAXNFSFILES) { $truncated = true; break; }
				$rel = substr($f->getPathname(), $rootlen + 1);
				$files[] = array($rel, (int)$f->getSize(), (int)$f->getMTime());
			}
		}
		catch (Exception $e) {
			/* unreadable entries are skipped by CATCH_GET_CHILD; anything else ends the listing */
			$truncated = true;
		}

		usort($files, function($a, $b) { return strcmp($a[0], $b[0]); });

		$paths = array();
		foreach ($files as $f) $paths[] = $f[0];

		$salt = bin2hex(random_bytes(16));
		$sniffs = array();
		foreach (IISelectSamples($paths, IIBundleExts()) as $i) {
			$full = realpath($root . '/' . $files[$i][0]);
			if (($full === false) || (substr($full, 0, $rootlen + 1) !== $root . '/')) continue;
			$sniffs[] = IIReadSample($full, $files[$i][0], $files[$i][1], $salt);
		}

		return array('version' => II_PROFILEVERSION, 'source' => 'nfs', 'root' => $root, 'files' => $files, 'sniffs' => $sniffs, 'truncated' => $truncated);
	}


	/* -------------------------------------------- */
	/* ------- IIReadSample ----------------------- */
	/* -------------------------------------------- */
	/* read one sampled file: detect its type from the magic bytes, then run the type's header reader.
	   Same output as ReadSample() in scripts/importinspect.js */
	function IIReadSample($full, $rel, $size, $salt) {
		$sniff = array('path' => $rel, 'type' => '', 'binary' => false, 'header' => array(), 'phi' => array());

		$fp = @fopen($full, 'rb');
		if ($fp === false) { $sniff['error'] = 'unreadable'; return $sniff; }
		$bytes = (string)fread($fp, II_HEADBYTES);
		fclose($fp);

		$parts = explode('/', $rel);
		$key = IIGroupKey($rel, IIBundleExts());
		$sniff['type'] = IIDetectType($bytes, $key);
		$sniff['binary'] = (strpos(substr($bytes, 0, 4096), "\0") !== false);

		$reader = '';
		$bycode = IIRegistryByCode();
		if ($sniff['type'] != '') {
			$reader = $bycode[$sniff['type']]['reader'];
		}
		else {
			/* no magic match: use the reader of a magic-less type for this extension (json, table) */
			foreach (IIRegistry() as $t)
				if (in_array($key, $t['exts'], true) && (count($t['magic']) == 0) && ($t['reader'] != '')) { $reader = $t['reader']; break; }
		}

		switch ($reader) {
			case 'dicom':       IIReadDicom($full, $salt, $sniff); break;
			case 'nifti':       IIReadNifti($bytes, $sniff); break;
			case 'niftigz':     IIReadNiftiGz($full, $sniff); break;
			case 'edf':         IIReadEdf($bytes, $sniff); break;
			case 'brainvision': IIReadBrainVision($bytes, $sniff); break;
			case 'par':         IIReadPar($bytes, $sniff); break;
			case 'json':        IIReadJson($bytes, $size, $sniff); break;
			case 'table':       if (!$sniff['binary']) IIReadTable($bytes, $sniff); break;
		}
		return $sniff;
	}


	/* -------------------------------------------- */
	/* ------- IIReadDicom ------------------------ */
	/* -------------------------------------------- */
	/* a few DICOM tags, read with dcmdump (DCMTK). Pixel data is not loaded */
	function IIReadDicom($full, $salt, &$sniff) {
		$dcmdump = '';
		foreach (array('/usr/local/bin/dcmdump', '/usr/bin/dcmdump') as $p)
			if (is_executable($p)) { $dcmdump = $p; break; }
		if ($dcmdump == '') { $sniff['error'] = 'nodcmdump'; return; }

		$tags = array('0008,0060' => 'modality', '0008,0016' => 'sopclass', '0008,0070' => 'manufacturer', '0008,1090' => 'model', '0008,103e' => 'seriesdesc', '0008,0020' => 'studydate', '0020,0011' => 'seriesnumber', '0010,0010' => 'patientname', '0010,0030' => 'patientbirthdate', '0010,0020' => 'patientid', '0010,1040' => 'patientaddress', '0010,1000' => 'otherpatientids', '0020,000d' => 'studyuid', '0020,000e' => 'seriesuid');
		$cmd = escapeshellarg($dcmdump) . " -q -M +sb 7fe0,0010";
		foreach (array_keys($tags) as $t)
			$cmd .= " +P $t";
		$cmd .= " " . escapeshellarg($full) . " 2>/dev/null";

		$v = array();
		foreach (explode("\n", (string)shell_exec($cmd)) as $line) {
			/* (0008,0060) CS [MR]    # 2, 1 Modality. Only the first instance (top level) of each tag */
			if (!preg_match('/^\s*\(([0-9a-f]{4},[0-9a-f]{4})\)\s+\S\S\s+(.*?)\s+#/i', $line, $m)) continue;
			$tag = strtolower($m[1]);
			if (!isset($tags[$tag]) || isset($v[$tags[$tag]])) continue;
			$val = $m[2];
			if (preg_match('/^\[(.*)\]$/', $val, $mm)) $val = $mm[1];
			elseif (preg_match('/^=(\S+)/', $val, $mm)) $val = $mm[1]; /* UIDs print as =Name */
			elseif (stripos($val, 'no value') !== false) $val = '';
			$v[$tags[$tag]] = trim($val);
		}

		IIDicomFields($v, $salt, $sniff);
	}


	/* -------------------------------------------- */
	/* ------- IIDicomFields ---------------------- */
	/* -------------------------------------------- */
	/* turn raw tag values into the profile's DICOM header: PHI becomes yes/no, IDs become hashes */
	function IIDicomFields($v, $salt, &$sniff) {
		$sniff['header'] = array(
			'modality' => substr($v['modality'] ?? '', 0, 16),
			'sopclass' => substr($v['sopclass'] ?? '', 0, 64),
			'manufacturer' => substr($v['manufacturer'] ?? '', 0, 64),
			'model' => substr($v['model'] ?? '', 0, 64),
			'seriesdesc' => substr($v['seriesdesc'] ?? '', 0, 64),
			'hasstudydate' => (($v['studydate'] ?? '') != ''),
			'hasseriesnumber' => (($v['seriesnumber'] ?? '') != ''),
			'patientidshape' => IIIdShape($v['patientid'] ?? ''),
			'keys' => array(
				'patient' => IIKeyHash($salt, $v['patientid'] ?? ''),
				'study' => IIKeyHash($salt, $v['studyuid'] ?? ''),
				'series' => IIKeyHash($salt, $v['seriesuid'] ?? '')
			)
		);
		$sniff['phi'] = array(
			'patientname' => (trim(str_replace('^', '', $v['patientname'] ?? '')) != ''),
			'patientbirthdate' => (($v['patientbirthdate'] ?? '') != ''),
			'patientaddress' => (($v['patientaddress'] ?? '') != ''),
			'otherpatientids' => (($v['otherpatientids'] ?? '') != '')
		);
	}


	/* -------------------------------------------- */
	/* ------- IIKeyHash -------------------------- */
	/* -------------------------------------------- */
	/* a salted hash of an identifier, to count distinct values without keeping them. '' stays '' */
	function IIKeyHash($salt, $value) {
		if (trim($value) == '') return '';
		return substr(hash('sha256', $salt . trim($value)), 0, 16);
	}


	/* -------------------------------------------- */
	/* ------- IIReadNifti ------------------------ */
	/* -------------------------------------------- */
	/* NIfTI-1, NIfTI-2, or Analyze header, either byte order */
	function IIReadNifti($bytes, &$sniff) {
		if (strlen($bytes) < 348) { $sniff['error'] = 'short'; return; }
		$le = unpack('V', substr($bytes, 0, 4))[1];
		$be = unpack('N', substr($bytes, 0, 4))[1];
		if (($le == 348) || ($be == 348)) {
			$f = ($le == 348) ? 'v' : 'n';
			$dims = array_values(unpack("{$f}8", substr($bytes, 40, 16)));
			$dims = array_map('IISigned16', $dims);
			$datatype = IISigned16(unpack($f, substr($bytes, 70, 2))[1]);
			$version = (substr($bytes, 344, 3) == 'n+1') ? 1 : ((substr($bytes, 344, 3) == 'ni1') ? 1 : 0);
		}
		elseif ((($le == 540) || ($be == 540)) && (strlen($bytes) >= 540)) {
			$le2 = ($le == 540);
			$dims = array();
			for ($i = 0; $i < 8; $i++)
				$dims[] = IIReadInt64(substr($bytes, 16 + 8 * $i, 8), $le2);
			$datatype = IISigned16(unpack($le2 ? 'v' : 'n', substr($bytes, 12, 2))[1]);
			$version = 2;
		}
		else {
			$sniff['error'] = 'notnifti';
			return;
		}
		$ndim = max(0, min(7, (int)$dims[0]));
		$sniff['header'] = array('version' => $version, 'ndim' => $ndim, 'dims' => array_slice($dims, 1, $ndim), 'datatype' => $datatype);
	}

	function IISigned16($v) { return ($v >= 32768) ? $v - 65536 : $v; }

	function IIReadInt64($b, $le) {
		if (!$le) $b = strrev($b);
		$lo = unpack('V', substr($b, 0, 4))[1];
		$hi = unpack('V', substr($b, 4, 4))[1];
		return $hi * 4294967296 + $lo;
	}


	/* -------------------------------------------- */
	/* ------- IIReadNiftiGz ---------------------- */
	/* -------------------------------------------- */
	function IIReadNiftiGz($full, &$sniff) {
		$gz = @gzopen($full, 'rb');
		if ($gz === false) { $sniff['error'] = 'unreadable'; return; }
		$bytes = (string)gzread($gz, 544);
		gzclose($gz);
		IIReadNifti($bytes, $sniff);
	}


	/* -------------------------------------------- */
	/* ------- IIReadEdf -------------------------- */
	/* -------------------------------------------- */
	/* EDF, EDF+, and BDF headers: signal labels, and whether the patient field identifies anyone */
	function IIReadEdf($bytes, &$sniff) {
		if (strlen($bytes) < 256) { $sniff['error'] = 'short'; return; }
		$patient = trim(substr($bytes, 8, 80));
		$reserved = trim(substr($bytes, 192, 44));
		$ns = max(0, min(512, (int)trim(substr($bytes, 252, 4))));
		$labels = array();
		for ($i = 0; ($i < $ns) && ($i < 64) && (256 + 16 * ($i + 1) <= strlen($bytes)); $i++)
			$labels[] = trim(substr($bytes, 256 + 16 * $i, 16));

		$variant = (substr($bytes, 0, 1) == "\xff") ? 'BDF' : ((substr($reserved, 0, 4) == 'EDF+') ? substr($reserved, 0, 5) : 'EDF');
		$sniff['header'] = array('variant' => $variant, 'numsignals' => $ns, 'labels' => $labels, 'hasstartdate' => (trim(substr($bytes, 168, 8)) != ''));
		$sniff['phi'] = array('patient' => IIEdfPatientIdentifies($patient, $variant));
	}


	/* -------------------------------------------- */
	/* ------- IIEdfPatientIdentifies ------------- */
	/* -------------------------------------------- */
	/* EDF+ patient field is "code sex birthdate name", with X for an anonymized subfield. The code
	   alone is an ID, not patient info. Plain EDF has a free-text field */
	function IIEdfPatientIdentifies($patient, $variant) {
		if ($patient == '') return false;
		if (substr($variant, 0, 4) != 'EDF+') return true;
		$sub = preg_split('/\s+/', $patient);
		for ($i = 2; $i < count($sub); $i++)
			if (($sub[$i] != 'X') && ($sub[$i] != '')) return true;
		return false;
	}


	/* -------------------------------------------- */
	/* ------- IIReadBrainVision ------------------ */
	/* -------------------------------------------- */
	function IIReadBrainVision($bytes, &$sniff) {
		$h = array('numchannels' => 0, 'datafile' => false, 'markerfile' => false);
		if (preg_match('/^NumberOfChannels\s*=\s*(\d+)/mi', $bytes, $m)) $h['numchannels'] = (int)$m[1];
		$h['datafile'] = (preg_match('/^DataFile\s*=\s*\S/mi', $bytes) == 1);
		$h['markerfile'] = (preg_match('/^MarkerFile\s*=\s*\S/mi', $bytes) == 1);
		$sniff['header'] = $h;
	}


	/* -------------------------------------------- */
	/* ------- IIReadPar -------------------------- */
	/* -------------------------------------------- */
	function IIReadPar($bytes, &$sniff) {
		$name = (preg_match('/^\.\s*Patient name\s*:\s*(.*)$/mi', $bytes, $m) == 1) ? trim($m[1]) : '';
		$protocol = (preg_match('/^\.\s*Protocol name\s*:\s*(.*)$/mi', $bytes, $m) == 1) ? trim($m[1]) : '';
		$date = (preg_match('/^\.\s*Examination date\/time\s*:\s*(.*)$/mi', $bytes, $m) == 1) ? trim($m[1]) : '';
		$sniff['header'] = array('protocol' => substr($protocol, 0, 64), 'hasexamdate' => ($date != ''));
		$sniff['phi'] = array('patientname' => ($name != ''));
	}


	/* -------------------------------------------- */
	/* ------- IIReadJson ------------------------- */
	/* -------------------------------------------- */
	/* a few keys from a BIDS sidecar or dataset description */
	function IIReadJson($bytes, $size, &$sniff) {
		if ($size > II_MAXJSONBYTES) { $sniff['error'] = 'toolarge'; return; }
		$j = json_decode($bytes, true);
		if (!is_array($j)) { $sniff['error'] = 'badjson'; return; }

		$keys = array();
		foreach (array('Modality', 'Manufacturer', 'ManufacturersModelName', 'TaskName', 'BIDSVersion', 'DatasetType', 'Name', 'SeriesDescription', 'ProtocolName') as $k)
			if (isset($j[$k]) && is_scalar($j[$k]))
				$keys[$k] = substr((string)$j[$k], 0, 64);
		$sniff['header'] = array('keys' => $keys);
		$sniff['phi'] = array('patientname' => !empty($j['PatientName']), 'patientbirthdate' => !empty($j['PatientBirthDate']));
	}


	/* -------------------------------------------- */
	/* ------- IIReadTable ------------------------ */
	/* -------------------------------------------- */
	/* the header row of a .csv/.tsv/.txt table, and whether it looks like task software output */
	function IIReadTable($bytes, &$sniff) {
		$lines = preg_split('/\r\n|\r|\n/', $bytes, 3);
		$first = $lines[0] ?? '';
		$signature = '';
		if (substr($first, 0, 16) == '*** Header Start') $signature = 'eprime';
		elseif (substr($first, 0, 10) == 'Scenario -') $signature = 'presentation';

		$delim = (substr_count($first, "\t") >= substr_count($first, ",")) ? "\t" : ",";
		$columns = array();
		if (strpos($first, $delim) !== false) {
			foreach (array_slice(explode($delim, $first), 0, 40) as $c)
				$columns[] = substr(trim($c, " \"'"), 0, 40);
		}
		$lc = array_map('strtolower', $columns);
		if (in_array('psychopyversion', $lc, true) || in_array('framerate', $lc, true)) $signature = 'psychopy';

		$sniff['header'] = array('delimiter' => ($delim == "\t") ? 'tab' : 'comma', 'columns' => $columns, 'signature' => $signature);
	}


	/* ------------------------------------ profile validation ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- IIValidateProfile ------------------ */
	/* -------------------------------------------- */
	/* check the shape of a profile sent by the browser, and keep only what the detectors use.
	   Returns the cleaned profile, or null and sets $err */
	function IIValidateProfile($p, &$err) {
		$err = '';
		if (!is_array($p) || !isset($p['files']) || !is_array($p['files'])) { $err = "The inspection data was not understood"; return null; }
		if (count($p['files']) > II_MAXPROFILEFILES) { $err = "Too many files to inspect at once (more than " . number_format(II_MAXPROFILEFILES) . ")"; return null; }

		$files = array();
		foreach ($p['files'] as $f) {
			if (!is_array($f) || !isset($f[0]) || !is_string($f[0]) || ($f[0] == '')) continue;
			$files[] = array(substr(ltrim($f[0], '/'), 0, 4096), max(0, (int)($f[1] ?? 0)), max(0, (int)($f[2] ?? 0)));
		}

		$sniffs = array();
		foreach (array_slice((isset($p['sniffs']) && is_array($p['sniffs'])) ? $p['sniffs'] : array(), 0, II_MAXSAMPLES * 2) as $s) {
			if (!is_array($s) || !isset($s['path']) || !is_string($s['path'])) continue;
			$sniffs[] = array(
				'path' => substr($s['path'], 0, 4096),
				'type' => (isset($s['type']) && is_string($s['type'])) ? substr($s['type'], 0, 32) : '',
				'binary' => !empty($s['binary']),
				'header' => IICleanValue($s['header'] ?? array(), 0),
				'phi' => IICleanValue($s['phi'] ?? array(), 0),
				'error' => (isset($s['error']) && is_string($s['error'])) ? substr($s['error'], 0, 64) : ''
			);
		}

		$source = in_array($p['source'] ?? '', array('localfiles', 'localfolder'), true) ? $p['source'] : 'localfolder';
		$root = (isset($p['root']) && is_string($p['root'])) ? substr($p['root'], 0, 255) : '';
		return array('version' => II_PROFILEVERSION, 'source' => $source, 'root' => $root, 'files' => $files, 'sniffs' => $sniffs, 'truncated' => !empty($p['truncated']));
	}


	/* -------------------------------------------- */
	/* ------- IICleanValue ----------------------- */
	/* -------------------------------------------- */
	/* scalars and small nested arrays only, strings truncated */
	function IICleanValue($v, $depth) {
		if (is_bool($v) || is_int($v) || is_float($v)) return $v;
		if (is_string($v)) return substr($v, 0, 128);
		if (is_array($v) && ($depth < 3)) {
			$out = array();
			foreach (array_slice($v, 0, 64, true) as $k => $x)
				$out[is_int($k) ? $k : substr((string)$k, 0, 64)] = IICleanValue($x, $depth + 1);
			return $out;
		}
		return null;
	}


	/* ------------------------------------ detectors ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- ImportDetector --------------------- */
	/* -------------------------------------------- */
	/* a layout detector (doc/universal-import.md §6.2). Detect() returns a list of results, each
	   [code, name, confidence 0-1, evidence (list of strings), identity [subject, study, series]] */
	interface ImportDetector {
		public function Name();
		public function Detect($ctx);
	}


	/* -------------------------------------------- */
	/* ------- IIAnalyzeProfile ------------------- */
	/* -------------------------------------------- */
	/* run the detectors on a profile. Returns what the wizard shows in "Here's what we think you have" */
	function IIAnalyzeProfile($profile) {
		$ctx = IIBuildContext($profile);

		IIClassifyGroups($ctx);

		$layouts = array();
		foreach (array(new IIBidsDetector(), new IIDicomdirDetector(), new IISquirrelDetector(), new IIDicomHeaderDetector(), new IITreeDetector(), new IIFilenameDetector(), new IICustomDetector()) as $d)
			foreach ($d->Detect($ctx) as $r)
				$layouts[] = $r;
		usort($layouts, function($a, $b) { return ($b['confidence'] < $a['confidence']) ? -1 : (($b['confidence'] > $a['confidence']) ? 1 : 0); });

		$groups = array_values($ctx['groups']);
		usort($groups, function($a, $b) { return $b['count'] - $a['count']; });

		return array(
			'summary' => array(
				'source' => $profile['source'],
				'root' => $profile['root'],
				'numfiles' => $ctx['numfiles'],
				'totalsize' => $ctx['totalsize'],
				'numsampled' => count($profile['sniffs']),
				'mtimemin' => $ctx['mtimemin'] ? date('Y-m-d', $ctx['mtimemin']) : '',
				'mtimemax' => $ctx['mtimemax'] ? date('Y-m-d', $ctx['mtimemax']) : '',
				'truncated' => $profile['truncated']
			),
			'axes' => IIAxes($ctx, $layouts),
			'layouts' => $layouts,
			'groups' => $groups,
			'levels' => $ctx['levels'],
			'shapes' => $ctx['shapes'],
			'phiwarnings' => IIPhiWarnings($ctx),
			'hints' => IIHints($ctx),
			'notes' => IINotes($ctx, $profile)
		);
	}


	/* -------------------------------------------- */
	/* ------- IIBuildContext --------------------- */
	/* -------------------------------------------- */
	/* everything the detectors share: file groups, folder levels, path shapes, sampled headers */
	function IIBuildContext($profile) {
		$bundles = IIBundleExts();
		$ctx = array('files' => $profile['files'], 'source' => $profile['source'], 'numfiles' => count($profile['files']), 'totalsize' => 0, 'mtimemin' => 0, 'mtimemax' => 0, 'groups' => array(), 'depths' => array(), 'specials' => array(), 'sniffs' => array(), 'levels' => array(), 'shapes' => array(), 'datepaths' => 0, 'namepaths' => 0);

		foreach ($profile['sniffs'] as $s)
			$ctx['sniffs'][$s['path']] = $s;

		$levelvals = array();
		$shapecounts = array();
		foreach ($profile['files'] as $i => $f) {
			list($path, $size, $mtime) = $f;
			$ctx['totalsize'] += $size;
			if ($mtime > 0) {
				if (($ctx['mtimemin'] == 0) || ($mtime < $ctx['mtimemin'])) $ctx['mtimemin'] = $mtime;
				if ($mtime > $ctx['mtimemax']) $ctx['mtimemax'] = $mtime;
			}

			$parts = explode('/', $path);
			$depth = count($parts) - 1;
			$ctx['depths'][$depth] = ($ctx['depths'][$depth] ?? 0) + 1;

			$special = IISpecialName($parts[$depth]);
			if ($special != '') $ctx['specials'][$special][] = $path;

			$key = IIGroupKey($path, $bundles);
			if (!isset($ctx['groups'][$key]))
				$ctx['groups'][$key] = array('key' => $key, 'count' => 0, 'size' => 0, 'units' => array(), 'paths' => array());
			$g = &$ctx['groups'][$key];
			$g['count']++;
			$g['size'] += $size;
			$g['paths'][] = $path;
			if (in_array($key, $bundles, true)) {
				/* a bundle is the directory ending in .<ext>; count the directories */
				for ($j = 0; $j < $depth; $j++)
					if (substr(strtolower($parts[$j]), -strlen($key) - 1) == ".$key") { $g['units'][implode('/', array_slice($parts, 0, $j + 1))] = true; break; }
			}
			unset($g);

			/* folder levels and path shapes */
			$shape = array();
			$hasdate = false;
			$hasname = false;
			for ($j = 0; $j <= $depth; $j++) {
				$seg = $parts[$j];
				$segshape = IIShapeSegment(($j == $depth) ? IIStripExt($seg, $key) : $seg);
				if (strpos($segshape, '<date>') !== false) $hasdate = true;
				if (preg_match('/^[A-Za-z\'\-]+(\^|, ?)[A-Za-z\'\-]+/', $seg)) $hasname = true;
				if ($j < $depth) {
					$levelvals[$j][$seg] = ($levelvals[$j][$seg] ?? 0) + 1;
					$shape[] = $segshape;
				}
				else {
					$shape[] = $segshape . (($key != '' && $key[0] != '#') ? ".$key" : '');
				}
			}
			if ($hasdate) $ctx['datepaths']++;
			if ($hasname) $ctx['namepaths']++;
			$s = implode('/', $shape);
			$shapecounts[$s] = ($shapecounts[$s] ?? 0) + 1;
		}

		arsort($shapecounts);
		foreach (array_slice($shapecounts, 0, 12, true) as $s => $c)
			$ctx['shapes'][] = array('shape' => (string)$s, 'count' => $c);

		ksort($levelvals);
		foreach ($levelvals as $j => $vals) {
			$shapes = array();
			$numfiles = 0;
			foreach ($vals as $v => $c) {
				$sh = IIShapeSegment((string)$v);
				$shapes[$sh] = ($shapes[$sh] ?? 0) + 1;
				$numfiles += $c;
			}
			arsort($shapes);
			$examples = array_slice(array_map('strval', array_keys($vals)), 0, 3);
			$top = array();
			foreach (array_slice($shapes, 0, 3, true) as $sh => $c)
				$top[] = array('shape' => (string)$sh, 'count' => $c);
			$ctx['levels'][] = array('level' => $j + 1, 'distinct' => count($vals), 'numfiles' => $numfiles, 'shapes' => $top, 'examples' => $examples);
		}

		ksort($ctx['depths']);
		return $ctx;
	}


	/* -------------------------------------------- */
	/* ------- IIShapeSegment --------------------- */
	/* -------------------------------------------- */
	/* a path segment with its values generalized: UIDs, long hex strings, and dates become
	   placeholders, and digits become #. 'S012' -> 'S###', '2024-03-14_rest' -> '<date>_rest' */
	function IIShapeSegment($s) {
		if (preg_match('/^\d+(\.\d+){3,}$/', $s)) return '<uid>';
		if (preg_match('/^[0-9a-f]{16,}$/i', $s)) return '<hex>';
		$s = preg_replace('/(19|20)\d\d([-_.]?)(0[1-9]|1[0-2])\2(0[1-9]|[12]\d|3[01])/', '<date>', $s);
		$s = preg_replace('/(0[1-9]|1[0-2])([-_.])(0[1-9]|[12]\d|3[01])\2(19|20)\d\d/', '<date>', $s);
		$s = preg_replace('/\d/', '#', $s);
		return preg_replace_callback('/#{5,}/', function($m) { return '#{' . strlen($m[0]) . '}'; }, $s);
	}


	/* -------------------------------------------- */
	/* ------- IIStripExt ------------------------- */
	/* -------------------------------------------- */
	function IIStripExt($name, $key) {
		if (($key != '') && ($key[0] != '#') && (strtolower(substr($name, -strlen($key) - 1)) == ".$key"))
			return substr($name, 0, -strlen($key) - 1);
		return $name;
	}


	/* -------------------------------------------- */
	/* ------- IIGroupSniffs ---------------------- */
	/* -------------------------------------------- */
	/* the sampled headers for the files in a group */
	function IIGroupSniffs($ctx, $g) {
		$out = array();
		foreach ($g['paths'] as $p)
			if (isset($ctx['sniffs'][$p])) $out[] = $ctx['sniffs'][$p];
		return $out;
	}


	/* -------------------------------------------- */
	/* ------- IIClassifyGroups ------------------- */
	/* -------------------------------------------- */
	/* file type, modality, and role of each group */
	function IIClassifyGroups(&$ctx) {
		$bycode = IIRegistryByCode();

		/* file type */
		foreach ($ctx['groups'] as $key => &$g) {
			$g['label'] = IIGroupLabel($key);
			$g['examples'] = array_slice($g['paths'], 0, 2);
			$g['units'] = count($g['units']);
			$g['evidence'] = array();
			$g['type'] = '';
			$g['typeconf'] = 0;
			$g['role'] = 'unknown';
			$g['companionof'] = '';

			if ($key == '#ignore') {
				$g['type'] = 'IGNORE';
				$g['typename'] = 'System files';
				$g['typeconf'] = 1;
				$g['role'] = 'ignore';
				$g['evidence'][] = "Operating system files such as .DS_Store and Thumbs.db";
				continue;
			}

			$candidates = array();
			foreach (IIRegistry() as $t)
				if (in_array($key, $t['exts'], true)) $candidates[] = $t;

			$sn = IIGroupSniffs($ctx, $g);
			$counts = array();
			foreach ($sn as $s)
				if ($s['type'] != '') $counts[$s['type']] = ($counts[$s['type']] ?? 0) + 1;
			arsort($counts);

			if (count($counts) > 0) {
				$code = (string)key($counts);
				$k = reset($counts);
				$g['type'] = $code;
				$g['typeconf'] = round(0.5 + 0.5 * $k / count($sn), 2);
				$g['evidence'][] = "Contents match " . $bycode[$code]['name'] . " in $k of " . count($sn) . " sampled files";
			}
			elseif (count($candidates) > 0) {
				$t = $candidates[0];
				foreach ($candidates as $c)
					if (count($c['magic']) == 0) { $t = $c; break; }
				$g['type'] = $t['code'];
				if (count($t['magic']) == 0) {
					$g['typeconf'] = (count($candidates) == 1) ? 0.5 : 0.35;
					$g['evidence'][] = "Identified by the file extension only";
				}
				else {
					$g['typeconf'] = (count($sn) > 0) ? 0.2 : 0.4;
					$g['evidence'][] = (count($sn) > 0) ? "The extension suggests " . $t['name'] . ", but the sampled contents don't match" : "Identified by the file extension only";
				}
			}

			$g['typename'] = ($g['type'] != '') ? $bycode[$g['type']]['name'] : 'Unknown';
			if ($g['type'] != '') {
				$kind = $bycode[$g['type']]['kind'];
				$g['role'] = ($kind == 'archive') ? 'archive' : 'primary';
			}
			else {
				$g['evidence'][] = ($key == '') ? "Files with no extension, and the sampled contents aren't a known type" : "Not a known file type";
			}
		}
		unset($g);

		/* companions: e.g. .json and .bval next to .nii.gz files with the same name */
		foreach ($ctx['groups'] as $key => &$g) {
			if (in_array($g['role'], array('ignore', 'archive'), true)) continue;
			foreach ($ctx['groups'] as $pkey => $pg) {
				if (($pkey == $key) || ($pg['type'] == '') || ($pg['role'] != 'primary')) continue;
				if (!in_array($key, $bycode[$pg['type']]['companions'], true)) continue;
				$frac = IIBasenameOverlap($g['paths'], $key, $pg['paths'], $pkey);
				if ($frac >= 0.5) {
					$g['role'] = 'companion';
					$g['companionof'] = $pg['type'];
					$g['companionlabel'] = $pg['label'];
					$g['evidence'] = array(round($frac * 100) . "% have a " . $pg['label'] . " file with the same name, so they're kept with those files");
					break;
				}
			}
		}
		unset($g);

		/* dataset-level files */
		foreach (array('dataset_description.json', 'participants.tsv', 'participants.json') as $name) {
			if (!isset($ctx['specials'][$name])) continue;
			$key = IIGroupKey($ctx['specials'][$name][0], IIBundleExts());
			if (isset($ctx['groups'][$key]) && ($ctx['groups'][$key]['count'] <= 3) && ($ctx['groups'][$key]['role'] != 'companion')) {
				$ctx['groups'][$key]['role'] = 'metadata';
				$ctx['groups'][$key]['evidence'][] = "Dataset description files" . (($name == 'participants.tsv') ? "; participants.tsv can supply subject demographics" : "");
			}
		}

		/* modality */
		foreach ($ctx['groups'] as $key => &$g) {
			list($g['modality'], $g['modconf'], $ev) = IIGroupModality($ctx, $g);
			foreach ($ev as $e) $g['evidence'][] = $e;
			$g['include'] = in_array($g['role'], array('primary', 'companion', 'metadata', 'archive'), true);
			unset($g['paths']);
		}
		unset($g);
	}


	/* -------------------------------------------- */
	/* ------- IIGroupLabel ----------------------- */
	/* -------------------------------------------- */
	function IIGroupLabel($key) {
		if ($key == '') return '(no extension)';
		if ($key == '#ignore') return 'System files';
		if (in_array($key, IIBundleExts(), true)) return ".$key folders";
		return ".$key";
	}


	/* -------------------------------------------- */
	/* ------- IIBasenameOverlap ------------------ */
	/* -------------------------------------------- */
	/* fraction of $paths (extension $key) that have a file of extension $pkey with the same name in the same folder */
	function IIBasenameOverlap($paths, $key, $ppaths, $pkey) {
		if (count($paths) == 0) return 0;
		$bases = array();
		foreach ($ppaths as $p)
			$bases[strtolower(IIStripExt($p, $pkey))] = true;
		$n = 0;
		foreach ($paths as $p)
			if (isset($bases[strtolower(IIStripExt($p, $key))])) $n++;
		return $n / count($paths);
	}


	/* -------------------------------------------- */
	/* ------- IIGroupModality -------------------- */
	/* -------------------------------------------- */
	/* returns [modality code, confidence, evidence] for a group */
	function IIGroupModality($ctx, $g) {
		$bycode = IIRegistryByCode();
		if (in_array($g['role'], array('ignore', 'archive', 'metadata', 'unknown'), true)) {
			if ($g['type'] == 'SQUIRREL') return array('', 0, array("squirrel packages are imported with the squirrel import"));
			if ($g['role'] == 'archive') return array('', 0, array("Archives are unpacked after upload, and their contents are identified then"));
			return array('', 0, array());
		}
		if ($g['role'] == 'companion')
			return array($bycode[$g['companionof']]['modality'], 0, array());

		$type = $g['type'];
		$sn = IIGroupSniffs($ctx, $g);
		$default = $bycode[$type]['modality'];

		switch ($type) {
			case 'DICOM':
				$counts = array();
				foreach ($sn as $s)
					if (($s['header']['modality'] ?? '') != '') $counts[$s['header']['modality']] = ($counts[$s['header']['modality']] ?? 0) + 1;
				if (count($sn) == 0) return array('', 0, array());
				if (count($counts) == 0) return array('OT', 0.2, array("No DICOM Modality (0008,0060) in the sampled headers"));
				arsort($counts);
				$dcm = (string)key($counts);
				$mod = IIDicomModality($dcm);
				$ev = array("DICOM Modality is $dcm in " . reset($counts) . " of " . count($sn) . " sampled headers");
				if (count($counts) > 1) $ev[] = "Other modalities in the sample: " . implode(', ', array_slice(array_keys($counts), 1));
				if ($mod != $dcm) $ev[] = "NiDB has no $dcm modality; stored as $mod";
				return array($mod, (count($counts) == 1) ? 0.95 : 0.6, $ev);

			case 'EDF':
			case 'BDF':
				$votes = array();
				foreach ($sn as $s)
					if (isset($s['header']['labels'])) { $m = IIEdfModality($s['header']['labels']); $votes[$m] = ($votes[$m] ?? 0) + 1; }
				if (count($votes) == 0) return array('EEG', 0.4, array("EDF is used for EEG, sleep (PSG), and ECG recordings"));
				arsort($votes);
				$mod = (string)key($votes);
				$names = array('EEG' => 'EEG electrode', 'PSG' => 'sleep study (SpO2, airflow, respiratory effort)', 'ECG' => 'ECG');
				return array($mod, 0.75, array("Signal labels look like " . $names[$mod] . " channels in " . reset($votes) . " of " . count($sn) . " sampled files"));

			case 'NIFTI':
			case 'NIFTIGZ':
			case 'ANALYZE':
				/* BIDS datatype folders, then .json sidecars */
				$dt = array('anat' => 'MR', 'func' => 'MR', 'dwi' => 'MR', 'fmap' => 'MR', 'perf' => 'MR', 'pet' => 'PT', 'ct' => 'CT');
				$found = array();
				foreach ($ctx['files'] as $f) {
					if (IIGroupKey($f[0], array()) != $g['key']) continue;
					$parts = explode('/', $f[0]);
					for ($j = 0; $j < count($parts) - 1; $j++)
						if (isset($dt[$parts[$j]])) { $found[$dt[$parts[$j]]] = ($found[$dt[$parts[$j]]] ?? 0) + 1; break; }
				}
				if (count($found) > 0) {
					arsort($found);
					return array((string)key($found), 0.85, array("In BIDS " . ((key($found) == 'MR') ? "anat/func/dwi/fmap" : strtolower(key($found))) . " folders"));
				}
				foreach ($ctx['sniffs'] as $s)
					if (isset($s['header']['keys']['Modality']) && in_array(strtoupper($s['header']['keys']['Modality']), array('MR', 'PT', 'CT'), true))
						return array(strtoupper($s['header']['keys']['Modality']), 0.8, array("A .json sidecar says Modality " . $s['header']['keys']['Modality']));
				return array('MR', 0.45, array("NIfTI is usually MR, but can also be PET or a derived map"));

			case 'TABLE':
				foreach ($sn as $s)
					if (($s['header']['signature'] ?? '') != '')
						return array('TASK', 0.75, array("Looks like " . array('eprime' => 'E-Prime', 'presentation' => 'Presentation', 'psychopy' => 'PsychoPy')[$s['header']['signature']] . " output"));
				$events = 0;
				foreach ($sn as $s)
					if (preg_match('/_(events|beh|physio)\.tsv$/i', $s['path'])) $events++;
				if ($events > 0) return array('TASK', 0.6, array("BIDS events/behavior tables"));
				return array('', 0.2, array("Tables can be task output (TASK) or per-subject values (non-imaging)"));
		}

		if ($default != '')
			return array($default, ($bycode[$type]['kind'] == 'bundle') ? 0.7 : 0.6, array());
		return array('', 0.2, array());
	}


	/* -------------------------------------------- */
	/* ------- IIDicomModality -------------------- */
	/* -------------------------------------------- */
	/* NiDB modality for a DICOM Modality value */
	function IIDicomModality($dcm) {
		$map = array('MR' => 'MR', 'CT' => 'CT', 'PT' => 'PT', 'US' => 'US', 'XA' => 'XA', 'CR' => 'CR', 'DX' => 'XRAY', 'MG' => 'XRAY', 'RF' => 'XRAY', 'IO' => 'XRAY', 'PX' => 'XRAY', 'ECG' => 'ECG', 'EEG' => 'EEG', 'OT' => 'OT');
		return $map[strtoupper($dcm)] ?? 'OT';
	}


	/* -------------------------------------------- */
	/* ------- IIEdfModality ---------------------- */
	/* -------------------------------------------- */
	/* EEG, PSG, or ECG from EDF signal labels */
	function IIEdfModality($labels) {
		$psg = 0;
		$ecg = 0;
		foreach ($labels as $l) {
			$l = strtolower($l);
			if (preg_match('/spo2|sao2|pleth|airflow|nasal|thor|chest|abdo|abd\b|snore|cpap|position/', $l)) $psg++;
			if (preg_match('/ecg|ekg/', $l)) $ecg++;
		}
		if ($psg >= 2) return 'PSG';
		if ((count($labels) > 0) && ($ecg == count($labels))) return 'ECG';
		return 'EEG';
	}


	/* -------------------------------------------- */
	/* ------- IIDominantDepth -------------------- */
	/* -------------------------------------------- */
	/* [depth, fraction of files at that depth] */
	function IIDominantDepth($ctx) {
		if ($ctx['numfiles'] == 0) return array(0, 0);
		$d = $ctx['depths'];
		arsort($d);
		return array((int)key($d), reset($d) / $ctx['numfiles']);
	}


	/* -------------------------------------------- */
	/* ------- IIGroupFraction -------------------- */
	/* -------------------------------------------- */
	/* fraction of the non-ignored files in groups of type $code */
	function IIGroupFraction($ctx, $code) {
		$n = 0;
		$total = 0;
		foreach ($ctx['groups'] as $g) {
			if ($g['role'] == 'ignore') continue;
			$total += $g['count'];
			if ($g['type'] == $code) $n += $g['count'];
		}
		return ($total > 0) ? $n / $total : 0;
	}


	/* -------------------------------------------- */
	/* ------- IILayout --------------------------- */
	/* -------------------------------------------- */
	/* $roles: optional starting roles for the mapping step (scripts/importmap.js), e.g. ['d0' => 'subject'] */
	function IILayout($code, $name, $confidence, $evidence, $subject, $study, $series, $roles = null) {
		$l = array('code' => $code, 'name' => $name, 'confidence' => round(max(0, min(1, $confidence)), 2), 'evidence' => $evidence, 'identity' => array('subject' => $subject, 'study' => $study, 'series' => $series));
		if ($roles !== null) $l['roles'] = $roles;
		return $l;
	}


	/* -------------------------------------------- */
	/* ------- IIBidsDetector --------------------- */
	/* -------------------------------------------- */
	class IIBidsDetector implements ImportDetector {
		public function Name() { return "BIDS"; }
		public function Detect($ctx) {
			$hasdd = false;
			$version = '';
			foreach ($ctx['specials']['dataset_description.json'] ?? array() as $p) {
				if (strpos($p, '/') !== false) continue;
				$hasdd = true;
				$version = $ctx['sniffs'][$p]['header']['keys']['BIDSVersion'] ?? '';
			}
			$insub = 0;
			$subs = array();
			$sess = array();
			foreach ($ctx['files'] as $f) {
				$parts = explode('/', $f[0]);
				if ((count($parts) > 1) && preg_match('/^sub-[A-Za-z0-9]+$/', $parts[0])) {
					$insub++;
					$subs[$parts[0]] = true;
					if ((count($parts) > 2) && preg_match('/^ses-[A-Za-z0-9]+$/', $parts[1])) $sess[$parts[1]] = true;
				}
			}
			$frac = ($ctx['numfiles'] > 0) ? $insub / $ctx['numfiles'] : 0;
			$conf = ($hasdd ? 0.45 : 0) + 0.45 * $frac + (($version != '') ? 0.1 : 0);
			if ($conf < 0.3) return array();

			$ev = array();
			if ($hasdd) $ev[] = "dataset_description.json at the top level" . (($version != '') ? " (BIDS $version)" : "");
			$ev[] = round($frac * 100) . "% of files are in sub-* folders: " . count($subs) . " subjects" . ((count($sess) > 0) ? ", " . count($sess) . " session labels" : "");
			return array(IILayout('BIDS', 'BIDS', $conf, $ev, "BIDS subject label (sub-*)", (count($sess) > 0) ? "BIDS session label (ses-*)" : "One study per subject and modality", "BIDS file name entities (task, acq, run, ...)"));
		}
	}


	/* -------------------------------------------- */
	/* ------- IIDicomdirDetector ----------------- */
	/* -------------------------------------------- */
	class IIDicomdirDetector implements ImportDetector {
		public function Name() { return "DICOMDIR"; }
		public function Detect($ctx) {
			foreach ($ctx['specials']['dicomdir'] ?? array() as $p) {
				if (substr_count($p, '/') > 1) continue;
				$conf = (IIGroupFraction($ctx, 'DICOM') >= 0.5) ? 0.9 : 0.6;
				return array(IILayout('DICOMDIR', 'DICOMDIR (CD/DVD export)', $conf, array("DICOMDIR file at $p"), "DICOM PatientID (0010,0020)", "DICOM StudyInstanceUID (0020,000D)", "DICOM SeriesInstanceUID (0020,000E)"));
			}
			return array();
		}
	}


	/* -------------------------------------------- */
	/* ------- IISquirrelDetector ----------------- */
	/* -------------------------------------------- */
	class IISquirrelDetector implements ImportDetector {
		public function Name() { return "squirrel"; }
		public function Detect($ctx) {
			foreach ($ctx['specials']['squirrel.json'] ?? array() as $p)
				if (strpos($p, '/') === false)
					return array(IILayout('SQUIRREL', 'squirrel package', 0.95, array("squirrel.json at the top level"), "squirrel package metadata", "squirrel package metadata", "squirrel package metadata"));
			return array();
		}
	}


	/* -------------------------------------------- */
	/* ------- IIDicomHeaderDetector -------------- */
	/* -------------------------------------------- */
	/* DICOM files anywhere: identity comes from the headers, not the folders */
	class IIDicomHeaderDetector implements ImportDetector {
		public function Name() { return "DICOM headers"; }
		public function Detect($ctx) {
			$frac = IIGroupFraction($ctx, 'DICOM');
			if ($frac < 0.3) return array();

			$n = 0;
			$withid = 0;
			$keys = array('patient' => array(), 'study' => array(), 'series' => array());
			foreach ($ctx['sniffs'] as $s) {
				if ($s['type'] != 'DICOM') continue;
				$n++;
				foreach ($keys as $k => $v)
					if (($s['header']['keys'][$k] ?? '') != '') $keys[$k][$s['header']['keys'][$k]] = true;
				if (($s['header']['keys']['patient'] ?? '') != '') $withid++;
			}
			$ev = array(round($frac * 100) . "% of files are DICOM");
			if ($n > 0) {
				$ev[] = "PatientID is set in $withid of $n sampled headers";
				$ev[] = "The sample has " . count($keys['patient']) . " patient(s), " . count($keys['study']) . " study(s), and " . count($keys['series']) . " series";
			}
			$conf = 0.4 + 0.5 * $frac * (($n > 0) ? $withid / $n : 0.5);
			return array(IILayout('DICOMHEADERS', 'DICOM (identity from headers)', $conf, $ev, "DICOM PatientID (0010,0020)", "DICOM StudyInstanceUID (0020,000D)", "DICOM SeriesInstanceUID (0020,000E)"));
		}
	}


	/* -------------------------------------------- */
	/* ------- IITreeDetector --------------------- */
	/* -------------------------------------------- */
	/* subject/study/series folders: most files at the same depth */
	class IITreeDetector implements ImportDetector {
		public function Name() { return "Folder tree"; }
		public function Detect($ctx) {
			list($depth, $frac) = IIDominantDepth($ctx);
			if ($depth < 1) return array();

			/* the first level that varies is the subject: folders above it that are the same for every
			   file are containers. If no level varies (one subject), start at the top */
			$levels = array();
			$varies = false;
			foreach ($ctx['levels'] as $l) {
				if ($l['level'] > $depth) break;
				if ($l['distinct'] > 1) $varies = true;
				if ($varies) $levels[] = $l;
			}
			if (!$varies)
				foreach ($ctx['levels'] as $l)
					if ($l['level'] <= $depth) $levels[] = $l;
			if (count($levels) == 0) return array();

			$names = array();
			foreach (array_slice($levels, 0, 3) as $l)
				$names[] = "folder level " . $l['level'] . " (" . $l['distinct'] . (($l['distinct'] == 1) ? " name" : " names") . ", e.g. " . $l['shapes'][0]['shape'] . ")";

			$conf = 0.2 + 0.5 * $frac;
			if (IIGroupFraction($ctx, 'DICOM') >= 0.5) $conf *= 0.6;
			if (count($levels) == 1) $conf *= 0.8;
			if (!$varies) $conf *= 0.7;

			$ev = array(round($frac * 100) . "% of files are $depth folder(s) deep");
			foreach (array_slice($levels, 0, 3) as $l)
				$ev[] = "Level " . $l['level'] . ": " . (($l['distinct'] == 1) ? "1 name, " : $l['distinct'] . " different names, mostly ") . $l['shapes'][0]['shape'];

			/* folder level n is path part n-1, which the mapping step calls d(n-1) */
			$roles = array();
			foreach (array_slice($levels, 0, 3) as $i => $l)
				$roles['d' . ($l['level'] - 1)] = array('subject', 'studylabel', 'seriesdesc')[$i];

			return array(IILayout('TREE', 'Subject / study / series folders', $conf, $ev, $names[0], $names[1] ?? "One study per subject and modality", $names[2] ?? "File name", $roles));
		}
	}


	/* -------------------------------------------- */
	/* ------- IIFilenameDetector ----------------- */
	/* -------------------------------------------- */
	/* a flat folder where the file names carry the identity, e.g. S012_V2_rest.edf */
	class IIFilenameDetector implements ImportDetector {
		public function Name() { return "File names"; }
		public function Detect($ctx) {
			$flat = 0;
			$tokencounts = array();
			$first = array();
			$n = 0;
			foreach ($ctx['files'] as $f) {
				$parts = explode('/', $f[0]);
				if (count($parts) > 2) continue;
				$flat++;
				$name = IIStripExt(end($parts), IIGroupKey($f[0], IIBundleExts()));
				if (IIIsIgnoredName($name)) continue;
				$tokens = preg_split('/[_\-. ]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
				if (count($tokens) < 2) continue;
				$n++;
				$tokencounts[count($tokens)] = ($tokencounts[count($tokens)] ?? 0) + 1;
				$first[$tokens[0]] = true;
			}
			if (($ctx['numfiles'] == 0) || ($flat / $ctx['numfiles'] < 0.8) || ($n < 2)) return array();
			arsort($tokencounts);
			$numtokens = (int)key($tokencounts);
			$consistency = reset($tokencounts) / $n;
			if (($consistency < 0.6) || (count($first) < 2) || (count($first) >= $n)) return array();

			$ev = array("Files are in one folder", round($consistency * 100) . "% of file names have $numtokens parts separated by _ or -", count($first) . " different values in the first part");
			return array(IILayout('FILENAME', 'Identity in file names', 0.2 + 0.4 * $consistency, $ev, "File name part 1", ($numtokens >= 3) ? "File name part 2" : "One study per subject and modality", "Rest of the file name"));
		}
	}


	/* -------------------------------------------- */
	/* ------- IICustomDetector ------------------- */
	/* -------------------------------------------- */
	/* always offered: the user builds the mapping in the next step */
	class IICustomDetector implements ImportDetector {
		public function Name() { return "Custom"; }
		public function Detect($ctx) {
			return array(IILayout('CUSTOM', 'Custom (define in the next step)', 0.1, array("Choose which folder or file name parts are the subject, study, and series"), "To be chosen", "To be chosen", "To be chosen"));
		}
	}


	/* -------------------------------------------- */
	/* ------- IIAxes ----------------------------- */
	/* -------------------------------------------- */
	/* the three axes (doc §3) summarized over the groups that will be imported */
	function IIAxes($ctx, $layouts) {
		$types = array();
		$mods = array();
		$subtypes = array();
		$typeconf = 1;
		$modconf = 1;
		$typeev = array();
		$modev = array();
		foreach ($ctx['groups'] as $g) {
			if ($g['role'] == 'companion') { $subtypes[$g['label']] = true; continue; }
			if ($g['role'] != 'primary') continue;
			$types[$g['typename']] = ($types[$g['typename']] ?? 0) + $g['count'];
			if ($g['modality'] != '') $mods[$g['modality']] = ($mods[$g['modality']] ?? 0) + $g['count'];
			$typeconf = min($typeconf, $g['typeconf']);
			$modconf = min($modconf, $g['modconf']);
			if (count($typeev) < 2) $typeev[] = $g['label'] . ": " . ($g['evidence'][0] ?? '');
		}
		arsort($types);
		arsort($mods);
		foreach ($ctx['groups'] as $g)
			if (($g['role'] == 'primary') && ($g['modality'] != '') && (count($modev) < 2) && isset($g['evidence'][1])) $modev[] = $g['evidence'][count($g['evidence']) - 1];

		$top = $layouts[0] ?? IILayout('CUSTOM', 'Custom', 0.1, array(), '', '', '');
		return array(
			'modality' => array('value' => (count($mods) == 1) ? (string)key($mods) : ((count($mods) > 1) ? 'MIXED' : ''), 'values' => array_keys($mods), 'confidence' => (count($mods) > 0) ? $modconf : 0, 'evidence' => $modev),
			'filetype' => array('value' => (count($types) == 1) ? (string)key($types) : ((count($types) > 1) ? 'Mixed' : 'Unknown'), 'values' => array_keys($types), 'subtypes' => array_keys($subtypes), 'confidence' => (count($types) > 0) ? $typeconf : 0, 'evidence' => $typeev),
			'layout' => array('value' => $top['code'], 'name' => $top['name'], 'confidence' => $top['confidence'], 'evidence' => $top['evidence'], 'identity' => $top['identity'])
		);
	}


	/* -------------------------------------------- */
	/* ------- IIPhiWarnings ---------------------- */
	/* -------------------------------------------- */
	/* what the inspection saw that may be patient information (D6: a warning only, never blocking) */
	function IIPhiWarnings($ctx) {
		$w = array();
		$counts = array();
		foreach ($ctx['sniffs'] as $s) {
			$t = $s['type'];
			foreach ($s['phi'] as $field => $present) {
				$k = "$t|$field";
				if (!isset($counts[$k])) $counts[$k] = array(0, 0);
				$counts[$k][1]++;
				if ($present) $counts[$k][0]++;
			}
		}
		$names = array('DICOM|patientname' => 'DICOM PatientName (0010,0010) is populated', 'DICOM|patientbirthdate' => 'DICOM PatientBirthDate (0010,0030) is populated', 'DICOM|patientaddress' => 'DICOM PatientAddress (0010,1040) is populated', 'DICOM|otherpatientids' => 'DICOM OtherPatientIDs (0010,1000) is populated', 'EDF|patient' => 'The EDF patient field has more than an ID', 'BDF|patient' => 'The BDF patient field has more than an ID', 'PARREC|patientname' => 'The PAR header patient name is populated', '|patientname' => '.json sidecars include PatientName', '|patientbirthdate' => '.json sidecars include PatientBirthDate', 'JSON|patientname' => '.json sidecars include PatientName', 'JSON|patientbirthdate' => '.json sidecars include PatientBirthDate');
		foreach ($names as $k => $text)
			if (isset($counts[$k]) && ($counts[$k][0] > 0))
				$w[] = "$text in " . $counts[$k][0] . " of " . $counts[$k][1] . " sampled files";

		if ($ctx['namepaths'] > 0) $w[] = number_format($ctx['namepaths']) . " file paths have a folder or file name formatted like a person's name (Last^First or Last, First)";
		if ($ctx['datepaths'] > 0) $w[] = number_format($ctx['datepaths']) . " file paths contain dates, which may be scan dates or dates of birth";

		foreach ($ctx['specials']['participants.tsv'] ?? array() as $p) {
			$cols = array_map('strtolower', $ctx['sniffs'][$p]['header']['columns'] ?? array());
			$phicols = preg_grep('/name|birth|dob|mrn|address|phone|email/', $cols);
			if (count($phicols) > 0) $w[] = "participants.tsv has columns that may hold patient information: " . implode(', ', $phicols);
		}
		return $w;
	}


	/* -------------------------------------------- */
	/* ------- IIHints ---------------------------- */
	/* -------------------------------------------- */
	/* suggestions to take a different path, e.g. the data looks like non-imaging */
	function IIHints($ctx) {
		$hints = array();
		$primary = 0;
		$tables = 0;
		$dicom = 0;
		foreach ($ctx['groups'] as $g) {
			if ($g['role'] != 'primary') continue;
			$primary += $g['count'];
			if (in_array($g['type'], array('TABLE', 'SPREADSHEET'), true) && ($g['modality'] == '')) $tables += $g['count'];
			if ($g['type'] == 'DICOM') $dicom += $g['count'];
		}
		if (($primary > 0) && ($tables == $primary) && ($primary <= 50))
			$hints[] = array('kind' => 'nonimaging', 'text' => "This looks like a spreadsheet of values rather than data files. If each row is a subject's scores, measurements, or medications, use the non-imaging import instead");

		/* DICOM files carry their own subject, study, and series, so all-DICOM data belongs in the DICOM import */
		if (($primary > 0) && ($dicom == $primary))
			$hints[] = array('kind' => 'dicom', 'text' => "All of the data files are DICOM. DICOM files already contain the subject, study, and series information NiDB needs, so use the DICOM import instead. Continue here only if the headers are missing or wrong and the folder or file names should be used instead");

		/* squirrel packages have their own import: a .sqrl file, or an unpacked package (squirrel.json at the top) */
		$squirrel = isset($ctx['groups']['sqrl']);
		foreach ($ctx['specials']['squirrel.json'] ?? array() as $p)
			if (strpos($p, '/') === false) $squirrel = true;
		if ($squirrel)
			$hints[] = array('kind' => 'squirrel', 'text' => "This includes a squirrel package. squirrel packages can hold imaging and non-imaging data together, and have their own import");
		return $hints;
	}


	/* -------------------------------------------- */
	/* ------- IINotes ---------------------------- */
	/* -------------------------------------------- */
	function IINotes($ctx, $profile) {
		$notes = array();
		if ($profile['truncated'])
			$notes[] = "Only the first " . number_format(count($profile['files'])) . " files were listed";
		$nodcm = 0;
		foreach ($profile['sniffs'] as $s)
			if (($s['error'] ?? '') == 'nodcmdump') $nodcm++;
		if ($nodcm > 0)
			$notes[] = "DICOM headers could not be read on the server (dcmdump was not found), so DICOM details are missing";
		$notes[] = "NiDB read the headers of " . count($profile['sniffs']) . " sampled files. Every file is checked again after upload, and anything different is shown for review";
		return $notes;
	}


	/* ------------------------------------ matching to existing NiDB records ------------------------------------ */


	/* -------------------------------------------- */
	/* ------- IIMatchIdentities ------------------ */
	/* -------------------------------------------- */
	/* which subjects and studies from the mapping step already exist (doc/universal-import.md §4.1).
	   $subjectids: subject IDs from the mapping, matched like the DICOM import does: the subject UID or an
	   alternate ID. $studies: [subjectid, modality, date YYYY-MM-DD, label]; a study exists when the subject
	   is enrolled in the project and has a study of that modality with the same visit label (study_type),
	   or with no label, on the same date.
	   Returns, in the order requested, ['subjects' => [['status' => enrolled|other|new|ambiguous, 'uid' => ...]], 'studies' => [['status' => existing|new|ambiguous|unknown, 'studynum' => n]]].
	   A UID is returned only for subjects already enrolled in the project; for subjects found elsewhere in
	   NiDB, only that they exist */
	function IIMatchIdentities($projectid, $subjectids, $studies) {
		/* requested ID (lowercase, as the collation compares) -> subject_ids */
		$found = array();
		$uids = array();
		$ids = array();
		foreach ($subjectids as $id) $ids[strtolower(trim($id))] = true;
		foreach (array_chunk(array_keys($ids), 500) as $chunk) {
			$in = implode(',', array_fill(0, count($chunk), '?'));
			$sqlstring = "select a.subject_id, a.uid, b.altuid from subjects a left join subject_altuid b on a.subject_id = b.subject_id where a.isactive = 1 and (a.uid in ($in) or b.altuid in ($in))";
			$params = array_merge($chunk, $chunk);
			$result = IIBoundQuery($sqlstring, $params, __LINE__);
			while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))) {
				$sid = (int)$row['subject_id'];
				$uids[$sid] = $row['uid'];
				foreach (array($row['uid'], $row['altuid']) as $v) {
					$k = strtolower(trim((string)$v));
					if (($k != '') && isset($ids[$k])) $found[$k][$sid] = true;
				}
			}
		}

		/* enrollments in this project, and their studies */
		$enrolled = array();
		$sids = array();
		foreach ($found as $k => $set) if (count($set) == 1) $sids[] = (int)key($set);
		foreach (array_chunk(array_values(array_unique($sids)), 500) as $chunk) {
			$sqlstring = "select enrollment_id, subject_id from enrollment where project_id = ? and subject_id in (" . implode(',', array_fill(0, count($chunk), '?')) . ")";
			$result = IIBoundQuery($sqlstring, array_merge(array($projectid), $chunk), __LINE__);
			while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
				$enrolled[(int)$row['subject_id']] = (int)$row['enrollment_id'];
		}
		$existing = array();
		foreach (array_chunk(array_values($enrolled), 500) as $chunk) {
			$sqlstring = "select enrollment_id, study_num, study_modality, date(study_datetime) 'studydate', study_type from studies where enrollment_id in (" . implode(',', array_fill(0, count($chunk), '?')) . ")";
			$result = IIBoundQuery($sqlstring, $chunk, __LINE__);
			while ($result && ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)))
				$existing[(int)$row['enrollment_id']][] = array((int)$row['study_num'], strtolower((string)$row['study_modality']), (string)$row['studydate'], strtolower(trim((string)$row['study_type'])));
		}

		$out = array('subjects' => array(), 'studies' => array());
		foreach ($subjectids as $id) {
			$k = strtolower(trim($id));
			$set = $found[$k] ?? array();
			if (count($set) == 0) $out['subjects'][] = array('status' => 'new');
			elseif (count($set) > 1) $out['subjects'][] = array('status' => 'ambiguous', 'count' => count($set));
			else {
				$sid = (int)key($set);
				$out['subjects'][] = isset($enrolled[$sid]) ? array('status' => 'enrolled', 'uid' => $uids[$sid]) : array('status' => 'other');
			}
		}
		foreach ($studies as $st) {
			list($id, $modality, $date, $label) = $st;
			$k = strtolower(trim($id));
			$set = $found[$k] ?? array();
			if (count($set) != 1) { $out['studies'][] = array('status' => (count($set) == 0) ? 'new' : 'unknown'); continue; }
			$sid = (int)key($set);
			if (!isset($enrolled[$sid])) { $out['studies'][] = array('status' => 'new'); continue; }
			if (($label == '') && ($date == '')) { $out['studies'][] = array('status' => 'unknown'); continue; }
			$matches = array();
			foreach ($existing[$enrolled[$sid]] ?? array() as $e) {
				if ($e[1] != strtolower($modality)) continue;
				if (($label != '') ? ($e[3] == strtolower(trim($label))) : ($e[2] == $date)) $matches[] = $e[0];
			}
			if (count($matches) == 0) $out['studies'][] = array('status' => 'new');
			elseif (count($matches) == 1) $out['studies'][] = array('status' => 'existing', 'studynum' => $matches[0]);
			else $out['studies'][] = array('status' => 'ambiguous', 'studynums' => $matches);
		}
		return $out;
	}


	/* -------------------------------------------- */
	/* ------- IIBoundQuery ----------------------- */
	/* -------------------------------------------- */
	/* a prepared query with string-bound parameters (MySQL compares them by the column type) */
	function IIBoundQuery($sqlstring, $params, $line) {
		$stmt = mysqli_prepare($GLOBALS['linki'], $sqlstring);
		$params = array_map('strval', $params);
		mysqli_stmt_bind_param($stmt, str_repeat('s', count($params)), ...$params);
		$result = MySQLiBoundQuery($stmt, __FILE__, $line, $sqlstring, $params);
		mysqli_stmt_close($stmt);
		return $result;
	}


	/* -------------------------------------------- */
	/* ------- IIValidateMatchRequest ------------- */
	/* -------------------------------------------- */
	/* the subjects and studies sent by the mapping step. Returns [subjectids, studies], or null and sets $err */
	function IIValidateMatchRequest($req, &$err) {
		$err = '';
		if (!is_array($req) || !isset($req['subjects']) || !is_array($req['subjects'])) { $err = "The subject list was not understood"; return null; }
		if ((count($req['subjects']) > II_MAXMATCHSUBJECTS) || (count($req['studies'] ?? array()) > II_MAXMATCHSTUDIES)) { $err = "Too many subjects or studies to check at once"; return null; }
		$subjects = array();
		foreach ($req['subjects'] as $id)
			if (is_string($id) && (strlen($id) <= 255)) $subjects[] = $id;
		$studies = array();
		foreach ((isset($req['studies']) && is_array($req['studies'])) ? $req['studies'] : array() as $st) {
			if (!is_array($st) || (count($st) < 4)) continue;
			$date = (is_string($st[2]) && preg_match('/^\d{4}-\d\d-\d\d$/', $st[2])) ? $st[2] : '';
			$studies[] = array(substr((string)$st[0], 0, 255), substr((string)$st[1], 0, 25), $date, substr((string)$st[3], 0, 250));
		}
		return array($subjects, $studies);
	}
?>
