/* ------------------------------------------------------------------------------
   NiDB importinspect.js
   Copyright (C) 2004 - 2026
   Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
   Olin Neuropsychiatry Research Center, Hartford Hospital
   ------------------------------------------------------------------------------
   GPLv3 License: see importwizard.php
   ------------------------------------------------------------------------------

   Universal import: builds the inspection profile for files on the user's computer.
   See doc/universal-import.md §5 step 2 and §6.2.

   Nothing is uploaded. The profile holds every file's relative path, size, and modification
   time, plus header details read with File.slice() from a sample of the files. Patient
   information is never put in the profile: readers report only whether a patient field is
   populated, and identifiers (PatientID, study and series UIDs) are salted hashes, used only to
   count distinct values. The salt is made here and never sent.

   The grouping, sampling, and header readers mirror importinspect_functions.php (the server
   builds the same profile for NFS paths). Keep the two in step.

   Usage: ImportInspect.BuildProfile(files, source, config, onprogress) -> Promise(profile)
     files       a FileList or array of File
     source      'localfiles' or 'localfolder'
     config      IIBrowserConfig() from importinspect_functions.php
     onprogress  function(stage, done, total), optional
   ------------------------------------------------------------------------------ */

(function() {
	"use strict";

	var IGNORENAMES = [".ds_store", "thumbs.db", "desktop.ini", ".localized"];
	var COMPOUNDEXTS = ["nii.gz", "tar.gz", "vcf.gz"];
	var SPECIALNAMES = ["dataset_description.json", "participants.tsv", "participants.json", "squirrel.json", "dicomdir"];
	var DICOMBYTES = 524288; /* DICOM headers are parsed from the first 512 KB */
	var CONCURRENCY = 6;

	/* ------------------------------------ grouping and sampling ------------------------------------ */

	function IsIgnoredName(name) {
		name = name.toLowerCase();
		if (IGNORENAMES.indexOf(name) >= 0) return true;
		return ((name.substring(0, 2) == "._") || (name.substring(0, 2) == "~$"));
	}

	function EndsWith(s, suffix) {
		return (s.length >= suffix.length) && (s.substring(s.length - suffix.length) == suffix);
	}

	/* same rules as IIGroupKey() */
	function GroupKey(relpath, bundles) {
		var parts = relpath.split("/");
		var n = parts.length;
		for (var i = 0; i < n - 1; i++) {
			var seg = parts[i].toLowerCase();
			for (var b = 0; b < bundles.length; b++)
				if ((seg.length > bundles[b].length + 1) && EndsWith(seg, "." + bundles[b])) return bundles[b];
		}

		var name = parts[n - 1].toLowerCase();
		if (IsIgnoredName(name)) return "#ignore";

		for (var c = 0; c < COMPOUNDEXTS.length; c++)
			if ((name.length > COMPOUNDEXTS[c].length + 1) && EndsWith(name, "." + COMPOUNDEXTS[c])) return COMPOUNDEXTS[c];

		var dot = name.lastIndexOf(".");
		if (dot <= 0) return "";
		var ext = name.substring(dot + 1);
		return /^[a-z][a-z0-9]{0,7}$/.test(ext) ? ext : "";
	}

	/* same rules as IISelectSamples() */
	function SelectSamples(paths, config) {
		var groups = {}, order = [], samples = [], seen = {};
		var add = function(i) { if (!seen[i]) { seen[i] = true; samples.push(i); } };

		for (var i = 0; i < paths.length; i++) {
			var parts = paths[i].split("/");
			var name = parts[parts.length - 1].toLowerCase();
			if ((SPECIALNAMES.indexOf(name) >= 0) && (name != "dicomdir") && (parts.length <= 3)) add(i);

			var key = GroupKey(paths[i], config.bundles);
			if ((key == "#ignore") || (config.bundles.indexOf(key) >= 0)) continue;
			if (!groups.hasOwnProperty(key)) { groups[key] = []; order.push(key); }
			groups[key].push(i);
		}

		for (var g = 0; g < order.length; g++) {
			var idx = groups[order[g]];
			var n = idx.length, m = Math.min(n, config.maxpergroup);
			for (var j = 0; j < m; j++) {
				if (samples.length >= config.maxsamples) return samples;
				add(idx[(m == 1) ? 0 : Math.floor(j * (n - 1) / (m - 1))]);
			}
		}
		return samples;
	}

	/* ------------------------------------ type detection ------------------------------------ */

	function HexAt(bytes, offset, hex) {
		if (offset + hex.length / 2 > bytes.length) return false;
		for (var i = 0; i < hex.length / 2; i++)
			if (bytes[offset + i] != parseInt(hex.substr(i * 2, 2), 16)) return false;
		return true;
	}

	function MatchMagic(bytes, magic) {
		for (var a = 0; a < magic.length; a++) {
			var ok = (magic[a].length > 0);
			for (var m = 0; m < magic[a].length; m++)
				if (!HexAt(bytes, magic[a][m][0], magic[a][m][1])) { ok = false; break; }
			if (ok) return true;
		}
		return false;
	}

	/* same rules as IIDetectType(): types for this extension first, then any type */
	function DetectType(bytes, key, types) {
		var t;
		for (t = 0; t < types.length; t++)
			if ((types[t].exts.indexOf(key) >= 0) && (types[t].magic.length > 0) && MatchMagic(bytes, types[t].magic)) return types[t];
		for (t = 0; t < types.length; t++)
			if ((types[t].magic.length > 0) && MatchMagic(bytes, types[t].magic)) return types[t];
		return null;
	}

	/* ------------------------------------ helpers ------------------------------------ */

	function Ascii(bytes, start, len) {
		var s = "";
		var end = Math.min(bytes.length, start + len);
		for (var i = start; i < end; i++) s += String.fromCharCode(bytes[i]);
		return s;
	}

	function Text(bytes) {
		try { return new TextDecoder("utf-8", { fatal: false }).decode(bytes); }
		catch (e) { return Ascii(bytes, 0, bytes.length); }
	}

	/* same as IIIdShape(): 'S012' -> 'A###' */
	function IdShape(s) {
		return String(s || "").substring(0, 64).replace(/[A-Z]/g, "A").replace(/[a-z]/g, "a").replace(/[0-9]/g, "#");
	}

	/* salted FNV-1a (64-bit, as two 32-bit halves). Only used to count distinct identifiers, so it needn't
	   be cryptographic, and it works where crypto.subtle doesn't (pages served over plain http) */
	function KeyHash(salt, value) {
		value = String(value || "").trim();
		if (value == "") return "";
		var s = salt + value;
		var h1 = 0x811c9dc5, h2 = 0x01000193 ^ 0x5bd1e995;
		for (var i = 0; i < s.length; i++) {
			var c = s.charCodeAt(i);
			h1 = Math.imul(h1 ^ c, 0x01000193) >>> 0;
			h2 = Math.imul(h2 ^ c, 0x5bd1e995) >>> 0;
		}
		return ("00000000" + h1.toString(16)).slice(-8) + ("00000000" + h2.toString(16)).slice(-8);
	}

	function MakeSalt() {
		var a = new Uint32Array(4);
		if (window.crypto && window.crypto.getRandomValues) window.crypto.getRandomValues(a);
		else for (var i = 0; i < 4; i++) a[i] = Math.floor(Math.random() * 4294967296);
		return Array.prototype.map.call(a, function(x) { return x.toString(16); }).join("");
	}

	function ReadBytes(file, length) {
		return file.slice(0, length).arrayBuffer().then(function(buf) { return new Uint8Array(buf); });
	}

	/* dicomParser comes from scripts/cs3d.bundle.js (the Cornerstone3D bundle used by dicom.php), which is
	   only loaded if a sampled file turns out to be DICOM */
	var dicomParserPromise = null;
	function EnsureDicomParser() {
		if (window.dicomParser) return Promise.resolve(window.dicomParser);
		if (!dicomParserPromise) {
			dicomParserPromise = new Promise(function(resolve) {
				var s = document.createElement("script");
				s.type = "module";
				s.src = "scripts/cs3d.bundle.js";
				s.onload = function() { resolve(window.dicomParser || null); };
				s.onerror = function() { resolve(null); };
				document.head.appendChild(s);
			});
		}
		return dicomParserPromise;
	}

	/* ------------------------------------ header readers ------------------------------------ */
	/* each fills sniff.header and sniff.phi, matching the IIRead*() functions */

	function ReadDicom(file, salt, sniff) {
		return EnsureDicomParser().then(function(parser) {
			if (!parser) { sniff.error = "nodicomparser"; return; }
			return ReadBytes(file, DICOMBYTES).then(function(bytes) {
				var ds;
				try {
					ds = parser.parseDicom(bytes, { untilTag: "x7fe00010" });
				}
				catch (e) {
					/* a header bigger than DICOMBYTES ends mid-element; dicomParser still returns what it read */
					ds = (e && e.dataSet) ? e.dataSet : null;
				}
				if (!ds) { sniff.error = "baddicom"; return; }

				var v = function(tag) { try { return (ds.string(tag) || "").trim(); } catch (e) { return ""; } };
				sniff.header = {
					modality: v("x00080060").substring(0, 16),
					sopclass: v("x00080016").substring(0, 64),
					manufacturer: v("x00080070").substring(0, 64),
					model: v("x00081090").substring(0, 64),
					seriesdesc: v("x0008103e").substring(0, 64),
					hasstudydate: (v("x00080020") != ""),
					hasseriesnumber: (v("x00200011") != ""),
					patientidshape: IdShape(v("x00100020")),
					keys: { patient: KeyHash(salt, v("x00100020")), study: KeyHash(salt, v("x0020000d")), series: KeyHash(salt, v("x0020000e")) }
				};
				sniff.phi = {
					patientname: (v("x00100010").replace(/\^/g, "").trim() != ""),
					patientbirthdate: (v("x00100030") != ""),
					patientaddress: (v("x00101040") != ""),
					otherpatientids: (v("x00101000") != "")
				};
			});
		});
	}

	function Signed16(v) { return (v >= 32768) ? v - 65536 : v; }

	function ReadNifti(bytes, sniff) {
		if (bytes.length < 348) { sniff.error = "short"; return; }
		var dv = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
		var dims = [], datatype, version, i;
		var le = dv.getInt32(0, true), be = dv.getInt32(0, false);
		if ((le == 348) || (be == 348)) {
			var l = (le == 348);
			for (i = 0; i < 8; i++) dims.push(dv.getInt16(40 + 2 * i, l));
			datatype = dv.getInt16(70, l);
			var magic = Ascii(bytes, 344, 3);
			version = ((magic == "n+1") || (magic == "ni1")) ? 1 : 0;
		}
		else if (((le == 540) || (be == 540)) && (bytes.length >= 540)) {
			var l2 = (le == 540);
			for (i = 0; i < 8; i++) {
				var lo = dv.getUint32(16 + 8 * i + (l2 ? 0 : 4), l2), hi = dv.getUint32(16 + 8 * i + (l2 ? 4 : 0), l2);
				dims.push(hi * 4294967296 + lo);
			}
			datatype = dv.getInt16(12, l2);
			version = 2;
		}
		else {
			sniff.error = "notnifti";
			return;
		}
		var ndim = Math.max(0, Math.min(7, dims[0]));
		sniff.header = { version: version, ndim: ndim, dims: dims.slice(1, 1 + ndim), datatype: datatype };
	}

	/* the start of a .nii.gz, decompressed with the browser's DecompressionStream */
	function ReadNiftiGz(file, headbytes, sniff) {
		if (typeof DecompressionStream === "undefined") { sniff.error = "nogzip"; return Promise.resolve(); }
		var reader = file.slice(0, headbytes).stream().pipeThrough(new DecompressionStream("gzip")).getReader();
		var chunks = [], total = 0;
		var pump = function() {
			return reader.read().then(function(r) {
				if (r.done || !r.value) return;
				chunks.push(r.value);
				total += r.value.length;
				if (total < 544) return pump();
			});
		};
		return pump().catch(function() { /* the slice ends mid-stream; whatever was decompressed is used */ }).then(function() {
			try { reader.cancel(); } catch (e) {}
			var bytes = new Uint8Array(total), pos = 0;
			chunks.forEach(function(c) { bytes.set(c, pos); pos += c.length; });
			ReadNifti(bytes, sniff);
		});
	}

	/* same as IIEdfPatientIdentifies() */
	function EdfPatientIdentifies(patient, variant) {
		if (patient == "") return false;
		if (variant.substring(0, 4) != "EDF+") return true;
		var sub = patient.split(/\s+/);
		for (var i = 2; i < sub.length; i++)
			if ((sub[i] != "X") && (sub[i] != "")) return true;
		return false;
	}

	function ReadEdf(bytes, sniff) {
		if (bytes.length < 256) { sniff.error = "short"; return; }
		var patient = Ascii(bytes, 8, 80).trim();
		var reserved = Ascii(bytes, 192, 44).trim();
		var ns = Math.max(0, Math.min(512, parseInt(Ascii(bytes, 252, 4).trim(), 10) || 0));
		var labels = [];
		for (var i = 0; (i < ns) && (i < 64) && (256 + 16 * (i + 1) <= bytes.length); i++)
			labels.push(Ascii(bytes, 256 + 16 * i, 16).trim());
		var variant = (bytes[0] == 0xff) ? "BDF" : ((reserved.substring(0, 4) == "EDF+") ? reserved.substring(0, 5) : "EDF");
		sniff.header = { variant: variant, numsignals: ns, labels: labels, hasstartdate: (Ascii(bytes, 168, 8).trim() != "") };
		sniff.phi = { patient: EdfPatientIdentifies(patient, variant) };
	}

	function ReadBrainVision(text, sniff) {
		var m = /^NumberOfChannels\s*=\s*(\d+)/mi.exec(text);
		sniff.header = { numchannels: m ? parseInt(m[1], 10) : 0, datafile: /^DataFile\s*=\s*\S/mi.test(text), markerfile: /^MarkerFile\s*=\s*\S/mi.test(text) };
	}

	function ReadPar(text, sniff) {
		var get = function(re) { var m = re.exec(text); return m ? m[1].trim() : ""; };
		var name = get(/^\.\s*Patient name\s*:\s*(.*)$/mi);
		var protocol = get(/^\.\s*Protocol name\s*:\s*(.*)$/mi);
		var date = get(/^\.\s*Examination date\/time\s*:\s*(.*)$/mi);
		sniff.header = { protocol: protocol.substring(0, 64), hasexamdate: (date != "") };
		sniff.phi = { patientname: (name != "") };
	}

	function ReadJson(text, size, maxbytes, sniff) {
		if (size > maxbytes) { sniff.error = "toolarge"; return; }
		var j;
		try { j = JSON.parse(text); } catch (e) { sniff.error = "badjson"; return; }
		if (!j || (typeof j !== "object") || Array.isArray(j)) { sniff.error = "badjson"; return; }
		var keys = {};
		["Modality", "Manufacturer", "ManufacturersModelName", "TaskName", "BIDSVersion", "DatasetType", "Name", "SeriesDescription", "ProtocolName"].forEach(function(k) {
			if ((j[k] !== undefined) && (j[k] !== null) && (typeof j[k] !== "object")) keys[k] = String(j[k]).substring(0, 64);
		});
		sniff.header = { keys: keys };
		sniff.phi = { patientname: !!j.PatientName, patientbirthdate: !!j.PatientBirthDate };
	}

	function ReadTable(text, sniff) {
		var first = text.split(/\r\n|\r|\n/)[0] || "";
		var signature = "";
		if (first.substring(0, 16) == "*** Header Start") signature = "eprime";
		else if (first.substring(0, 10) == "Scenario -") signature = "presentation";

		var tabs = first.split("\t").length - 1, commas = first.split(",").length - 1;
		var delim = (tabs >= commas) ? "\t" : ",";
		var columns = [];
		if (first.indexOf(delim) >= 0)
			columns = first.split(delim).slice(0, 40).map(function(c) { return c.replace(/^[\s"']+|[\s"']+$/g, "").substring(0, 40); });
		var lc = columns.map(function(c) { return c.toLowerCase(); });
		if ((lc.indexOf("psychopyversion") >= 0) || (lc.indexOf("framerate") >= 0)) signature = "psychopy";

		sniff.header = { delimiter: (delim == "\t") ? "tab" : "comma", columns: columns, signature: signature };
	}

	/* ------------------------------------ sampling a file ------------------------------------ */

	/* same output as IIReadSample() */
	function ReadSample(file, relpath, config, salt) {
		var sniff = { path: relpath, type: "", binary: false, header: {}, phi: {} };
		var key = GroupKey(relpath, config.bundles);

		return ReadBytes(file, config.headbytes).then(function(bytes) {
			var t = DetectType(bytes, key, config.types);
			sniff.type = t ? t.code : "";
			sniff.binary = (bytes.subarray(0, 4096).indexOf(0) >= 0);

			var reader = t ? t.reader : "";
			if (!t) {
				/* no magic match: use the reader of a magic-less type for this extension (json, table) */
				for (var i = 0; i < config.types.length; i++)
					if ((config.types[i].exts.indexOf(key) >= 0) && (config.types[i].magic.length == 0) && (config.types[i].reader != "")) { reader = config.types[i].reader; break; }
			}

			switch (reader) {
				case "dicom": return ReadDicom(file, salt, sniff);
				case "nifti": ReadNifti(bytes, sniff); break;
				case "niftigz": return ReadNiftiGz(file, config.headbytes, sniff);
				case "edf": ReadEdf(bytes, sniff); break;
				case "brainvision": ReadBrainVision(Text(bytes), sniff); break;
				case "par": ReadPar(Text(bytes), sniff); break;
				case "json": ReadJson(Text(bytes), file.size, config.maxjsonbytes, sniff); break;
				case "table": if (!sniff.binary) ReadTable(Text(bytes), sniff); break;
			}
		}).catch(function() {
			sniff.error = "unreadable";
		}).then(function() {
			return sniff;
		});
	}

	/* ------------------------------------ the profile ------------------------------------ */

	function BuildProfile(files, source, config, onprogress) {
		var progress = onprogress || function() {};
		var list = Array.prototype.slice.call(files);

		/* relative paths. A chosen folder's own name is the first part of every path; it becomes the root */
		var entries = list.map(function(f) { return { file: f, path: (source == "localfolder") ? (f.webkitRelativePath || f.name) : f.name }; });
		var root = "";
		if ((source == "localfolder") && (entries.length > 0)) {
			var top = entries[0].path.split("/")[0];
			var common = entries.every(function(e) { return (e.path.indexOf(top + "/") == 0); });
			if (common) {
				root = top;
				entries.forEach(function(e) { e.path = e.path.substring(top.length + 1); });
			}
		}
		entries.sort(function(a, b) { return (a.path < b.path) ? -1 : ((a.path > b.path) ? 1 : 0); });

		var paths = entries.map(function(e) { return e.path; });
		var samples = SelectSamples(paths, config);
		var salt = MakeSalt();
		var sniffs = [];
		var next = 0, done = 0;
		progress("reading", 0, samples.length);

		/* read the samples a few at a time */
		var worker = function() {
			if (next >= samples.length) return Promise.resolve();
			var e = entries[samples[next++]];
			return ReadSample(e.file, e.path, config, salt).then(function(sniff) {
				sniffs.push(sniff);
				progress("reading", ++done, samples.length);
				return worker();
			});
		};
		var workers = [];
		for (var w = 0; w < Math.min(CONCURRENCY, samples.length); w++) workers.push(worker());

		return Promise.all(workers).then(function() {
			return {
				version: config.version,
				source: source,
				root: root,
				files: entries.map(function(e) { return [e.path, e.file.size, Math.floor(e.file.lastModified / 1000)]; }),
				sniffs: sniffs,
				truncated: false
			};
		});
	}

	/* the profile as a gzipped Blob when the browser can compress, otherwise plain JSON */
	function PackProfile(profile) {
		var json = new Blob([JSON.stringify(profile)], { type: "application/json" });
		if (typeof CompressionStream === "undefined") return Promise.resolve({ blob: json, encoding: "json" });
		return new Response(json.stream().pipeThrough(new CompressionStream("gzip"))).blob().then(function(b) {
			return { blob: b, encoding: "gzip" };
		});
	}

	window.ImportInspect = { BuildProfile: BuildProfile, PackProfile: PackProfile, GroupKey: GroupKey, SelectSamples: SelectSamples };
})();
