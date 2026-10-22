/* ------------------------------------------------------------------------------
   NiDB importmap.js
   Copyright (C) 2004 - 2026
   Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
   Olin Neuropsychiatry Research Center, Hartford Hospital
   ------------------------------------------------------------------------------
   GPLv3 License: see importwizard.php
   ------------------------------------------------------------------------------

   Universal import: maps the inspected files to subjects, studies, and series.
   See doc/universal-import.md §4 and §5 step 4.

   A mapping assigns roles (subject, study label, study date, series description,
   series number) to parts of the path: a whole folder level ("d0" is the top folder),
   a part of a folder name ("d0.t1"), the whole file name without its extension ("f"),
   or a part of the file name ("f.t2"). Names are split into parts at _ - . and spaces,
   except that dates and UIDs stay whole. A role taken from more than one part joins
   the values with _.

   Everything here works on the file list only (paths, sizes, modification times), so it
   runs in the browser for every file, not just the inspection sample.

   Usage:
     units = ImportMap.Prepare(files, result, groupstate, bundles)
     mapping = ImportMap.DefaultMapping(layout, units, result)
     tree = ImportMap.Apply(units, mapping)
     ImportMap.Template(unit, mapping), ImportMap.Recipe(...)
   ------------------------------------------------------------------------------ */

(function() {
	"use strict";

	var ROLES = ["subject", "studylabel", "studydate", "seriesdesc", "seriesnum"];
	var DATE_RE = /(19|20)\d\d([-_.]?)(0[1-9]|1[0-2])\2(0[1-9]|[12]\d|3[01])|(0[1-9]|1[0-2])([-_.])(0[1-9]|[12]\d|3[01])\6(19|20)\d\d/;
	/* the characters names can be split at. A mapping can split each folder level or the file name at fewer of them */
	var DELIMS = "_-. ";
	var tokenres = Object.create(null);
	/* top-level files that describe the whole dataset; they're kept with the import but aren't in a series */
	var DATASETNAMES = /^(dataset_description\.json|participants\.(tsv|json)|readme(\.[a-z]+)?|changes|license|squirrel\.json|dicomdir)$/i;

	/* natural order: run2 before run10. Faster than Intl.Collator, which matters for 100,000s of series */
	function NaturalCompare(a, b) {
		var i = 0, j = 0, la = a.length, lb = b.length;
		while ((i < la) && (j < lb)) {
			var ca = a.charCodeAt(i), cb = b.charCodeAt(j);
			if ((ca >= 48) && (ca <= 57) && (cb >= 48) && (cb <= 57)) {
				var si = i, sj = j;
				while ((i < la) && (a.charCodeAt(i) == 48)) i++;
				while ((j < lb) && (b.charCodeAt(j) == 48)) j++;
				var ni = i, nj = j;
				while ((i < la) && (a.charCodeAt(i) >= 48) && (a.charCodeAt(i) <= 57)) i++;
				while ((j < lb) && (b.charCodeAt(j) >= 48) && (b.charCodeAt(j) <= 57)) j++;
				if ((i - ni) != (j - nj)) return (i - ni) - (j - nj);
				for (var k = 0; k < i - ni; k++) {
					var d = a.charCodeAt(ni + k) - b.charCodeAt(nj + k);
					if (d != 0) return d;
				}
				if ((i - si) != (j - sj)) return (i - si) - (j - sj);
				continue;
			}
			if (ca != cb) {
				var la2 = (ca >= 65 && ca <= 90) ? ca + 32 : ca, lb2 = (cb >= 65 && cb <= 90) ? cb + 32 : cb;
				if (la2 != lb2) return la2 - lb2;
				return ca - cb;
			}
			i++;
			j++;
		}
		return (la - i) - (lb - j);
	}

	/* ------------------------------------ names ------------------------------------ */

	/* parts of a name split at the delimiters (default all of DELIMS): [{text, start, end}]. Dates (2024-03-14,
	   20240314, 03-14-2024) and UIDs stay whole when they are a whole part */
	function Tokenize(s, delims) {
		if (delims === undefined) delims = DELIMS;
		var re = tokenres[delims];
		if (!re) {
			var cls = delims.split("").map(function(c) { return (c == "-") ? "\\-" : ((c == ".") ? "\\." : c); }).join("");
			var end = cls ? "(?=[" + cls + "]|$)" : "$";
			re = tokenres[delims] = new RegExp("(?:19|20)\\d\\d([-_.]?)(?:0[1-9]|1[0-2])\\1(?:0[1-9]|[12]\\d|3[01])" + end +
				"|(?:0[1-9]|1[0-2])([-_.])(?:0[1-9]|[12]\\d|3[01])\\2(?:19|20)\\d\\d" + end +
				"|\\d+(?:\\.\\d+){3,}" + end +
				"|" + (cls ? "[^" + cls + "]+" : "[\\s\\S]+"), "g");
		}
		var out = [];
		var m;
		re.lastIndex = 0;
		while ((m = re.exec(s)) !== null)
			out.push({ text: m[0], start: m.index, end: m.index + m[0].length });
		return out;
	}

	/* the delimiters a mapping splits a folder level or the file name at */
	function SegDelims(mapping, seg) {
		var d = (mapping && mapping.delims) ? mapping.delims[seg] : undefined;
		return (d === undefined) ? DELIMS : d;
	}

	/* a date in a value, as YYYY-MM-DD, or '' */
	function ParseDate(s) {
		var m = DATE_RE.exec(s || "");
		if (!m) return "";
		if (m[1]) {
			var d = m[0].replace(/[-_.]/g, "");
			return d.substring(0, 4) + "-" + d.substring(4, 6) + "-" + d.substring(6, 8);
		}
		return m[8] + m[0].substring(8, 10) + "-" + m[5] + "-" + m[7];
	}

	/* a value with its characters generalized, same as IIShapeSegment(): S012 -> S###, dates -> <date> */
	function Shape(s) {
		if (/^\d+(\.\d+){3,}$/.test(s)) return "<uid>";
		if (/^[0-9a-f]{16,}$/i.test(s)) return "<hex>";
		s = s.replace(/(19|20)\d\d([-_.]?)(0[1-9]|1[0-2])\2(0[1-9]|[12]\d|3[01])/g, "<date>");
		s = s.replace(/(0[1-9]|1[0-2])([-_.])(0[1-9]|[12]\d|3[01])\2(19|20)\d\d/g, "<date>");
		s = s.replace(/\d/g, "#");
		return s.replace(/#{5,}/g, function(x) { return "#{" + x.length + "}"; });
	}

	/* letters as A or a, so a recipe can't hold a subject's name: S### -> A### */
	function LetterShape(s) {
		return s.replace(/<[a-z]+>|#\{\d+\}|[A-Za-z]/g, function(x) { return (x.length > 1) ? x : ((x == x.toUpperCase()) ? "A" : "a"); });
	}

	function FileDate(mtime) {
		if (!mtime) return "";
		var d = new Date(mtime * 1000);
		var p = function(n) { return (n < 10) ? "0" + n : String(n); };
		return d.getFullYear() + "-" + p(d.getMonth() + 1) + "-" + p(d.getDate());
	}

	/* ------------------------------------ units ------------------------------------ */

	/* the files that are mapped, from the profile's file list and the user's group choices.
	   A unit is one file, or one bundle folder (e.g. an EGI .mff) with everything inside it.
	   groupstate: {key: {include, modality}} from the inspection step */
	function Prepare(files, result, groupstate, bundles) {
		var groups = {};
		result.groups.forEach(function(g) { groups[g.key] = g; });

		var units = [];
		var bundleunits = {};
		var dataset = { count: 0, size: 0 };
		var archives = { count: 0, size: 0 };
		var excluded = { count: 0, size: 0 };

		files.forEach(function(f) {
			var path = f[0], size = f[1] || 0, mtime = f[2] || 0;
			var key = ImportInspect.GroupKey(path, bundles);
			var g = groups[key];
			var st = groupstate[key];
			if (!g || !st || !st.include) { excluded.count++; excluded.size += size; return; }
			if ((g.role == "metadata") || ((path.indexOf("/") < 0) && DATASETNAMES.test(path))) { dataset.count++; dataset.size += size; return; }
			if (g.role == "archive") { archives.count++; archives.size += size; return; }

			var parts = path.split("/");
			if (bundles.indexOf(key) >= 0) {
				/* the bundle folder is the unit */
				for (var j = 0; j < parts.length - 1; j++) {
					if (parts[j].toLowerCase().slice(-key.length - 1) != "." + key) continue;
					var bpath = parts.slice(0, j + 1).join("/");
					var u = bundleunits[bpath];
					if (!u) {
						u = bundleunits[bpath] = MakeUnit(bpath, key, g, st);
						u.bundle = true;
						units.push(u);
					}
					u.nfiles++;
					u.size += size;
					if (mtime && (!u.mtime || (mtime < u.mtime))) u.mtime = mtime;
					return;
				}
			}
			var unit = MakeUnit(path, key, g, st);
			unit.size = size;
			unit.mtime = mtime;
			unit.nfiles = 1;
			units.push(unit);
		});

		return { units: units, dataset: dataset, archives: archives, excluded: excluded };
	}

	function MakeUnit(path, key, g, st) {
		var parts = path.split("/");
		var name = parts.pop();
		var base = name;
		if ((key != "") && (key.charAt(0) != "#") && (name.toLowerCase().slice(-key.length - 1) == "." + key))
			base = name.substring(0, name.length - key.length - 1);
		return {
			path: path, folders: parts, dir: parts.join("/"), base: base, ext: (base != name) ? name.substring(base.length) : "",
			key: key, primary: (g.role == "primary") || (g.role == "unknown"), type: st.type || g.type,
			modality: st.modality || "", size: 0, mtime: 0, nfiles: 0, bundle: false, toks: {}
		};
	}

	/* the parts of a unit's folder i, or of its file name ('f') */
	function UnitTokens(u, seg, delims) {
		if (delims === undefined) delims = DELIMS;
		var ck = seg + "|" + delims;
		if (u.toks[ck] === undefined) {
			var s = (seg == "f") ? u.base : u.folders[parseInt(seg.substring(1), 10)];
			u.toks[ck] = (s === undefined) ? null : Tokenize(s, delims);
		}
		return u.toks[ck];
	}

	/* a role key ('d1', 'd1.t0', 'f', 'f.t2') as {seg, level, tok}; level -1 is the file name, tok -1 the whole name */
	function ParseKey(k) {
		var dot = k.indexOf(".t");
		var seg = (dot < 0) ? k : k.substring(0, dot);
		return { seg: seg, level: (seg == "f") ? -1 : parseInt(seg.substring(1), 10), tok: (dot < 0) ? -1 : parseInt(k.substring(dot + 2), 10) };
	}

	/* the value of a parsed role key for a unit, or null with the reason in KeyValue.reason */
	function KeyValue(u, pk) {
		var whole;
		if (pk.level < 0) whole = u.base;
		else {
			if (pk.level >= u.folders.length) { KeyValue.reason = "Fewer folders than the example"; return null; }
			whole = u.folders[pk.level];
		}
		if (pk.tok < 0) return whole;
		var toks = UnitTokens(u, pk.seg, pk.delims);
		if (!toks || (pk.tok >= toks.length)) { KeyValue.reason = (pk.level < 0) ? "The file name has fewer parts than the example" : "A folder name has fewer parts than the example"; return null; }
		return toks[pk.tok].text;
	}

	/* role keys in path order: folders top down, then the file name; parts left to right */
	function SortKeys(keys) {
		var rank = function(k) {
			var dot = k.indexOf(".t");
			var seg = (dot < 0) ? k : k.substring(0, dot);
			var level = (seg == "f") ? 100000 : parseInt(seg.substring(1), 10);
			return level * 1000 + ((dot < 0) ? 0 : parseInt(k.substring(dot + 2), 10) + 1);
		};
		return keys.slice().sort(function(a, b) { return rank(a) - rank(b); });
	}

	/* {role: [keys]} */
	function RoleKeys(mapping) {
		var out = {};
		ROLES.forEach(function(r) { out[r] = []; });
		Object.keys(mapping.roles).forEach(function(k) {
			var r = mapping.roles[k];
			if (out[r]) out[r].push(k);
		});
		ROLES.forEach(function(r) { out[r] = SortKeys(out[r]); });
		return out;
	}

	/* ------------------------------------ default mapping ------------------------------------ */

	/* the representative unit: the first unit with the most common path shape */
	function SampleUnit(units) {
		var counts = {}, first = {}, best = null;
		units.forEach(function(u) {
			if (!u.primary) return;
			var s = u.folders.map(Shape).join("/") + "/" + Shape(u.base) + u.ext;
			counts[s] = (counts[s] || 0) + 1;
			if (!first[s]) first[s] = u;
			if (!best || (counts[s] > counts[best])) best = s;
		});
		return best ? first[best] : (units[0] || null);
	}

	/* the different path shapes, most common first, each with an example unit */
	function ShapeExamples(units, max) {
		var counts = {}, first = {}, primary = {};
		units.forEach(function(u) {
			var s = u.folders.map(Shape).join("/") + (u.folders.length ? "/" : "") + Shape(u.base) + u.ext;
			counts[s] = (counts[s] || 0) + 1;
			if (!first[s]) first[s] = u;
			if (u.primary) primary[s] = true;
		});
		/* data files before companions such as .json sidecars */
		return Object.keys(counts).sort(function(a, b) { return ((primary[b] ? 1 : 0) - (primary[a] ? 1 : 0)) || (counts[b] - counts[a]); }).slice(0, max).map(function(s) {
			return { shape: s, count: counts[s], unit: first[s] };
		});
	}

	/* the starting mapping for a detected layout. 'header' (levels that come from the DICOM headers after
	   upload) is supported by Apply but not offered; the grouping is per folder for DICOM and bundles, otherwise per file name */
	function DefaultMapping(layout, prepared, result) {
		var units = prepared.units;
		var m = { roles: {}, delims: {}, subjectconst: "", header: { subject: false, study: false, series: false }, grouping: "basename", datesource: "mtime" };
		var dicom = units.some(function(u) { return u.primary && (u.type == "DICOM"); });
		if (dicom || units.some(function(u) { return u.nfiles > 1; })) m.grouping = "folder";

		var code = layout ? layout.code : "CUSTOM";
		var sample = SampleUnit(units);

		/* all-DICOM data goes to the DICOM import, so DICOM that reaches the mapping has headers that can't be
		   trusted. Its identity comes from the path like everything else; only the date can still come from them */
		if (layout && layout.roles) {
			Object.keys(layout.roles).forEach(function(k) { m.roles[k] = layout.roles[k]; });
			if (!Object.keys(m.roles).some(function(k) { return m.roles[k] == "seriesdesc"; }) && (m.grouping == "basename"))
				m.roles.f = "seriesdesc";
		}
		else if ((code == "BIDS") && sample) {
			/* sub-<label> and ses-<label> folders; the series is the file name */
			sample.folders.forEach(function(f, i) {
				if (/^sub-[A-Za-z0-9]+$/.test(f)) m.roles["d" + i + ".t1"] = "subject";
				if (/^ses-[A-Za-z0-9]+$/.test(f)) m.roles["d" + i + ".t1"] = "studylabel";
			});
			m.roles.f = "seriesdesc";
		}
		else if ((code == "FILENAME") && sample) {
			var toks = UnitTokens(sample, "f") || [];
			if (toks.length >= 2) {
				m.roles["f.t0"] = "subject";
				var start = 1;
				if (toks.length >= 3) {
					m.roles["f.t1"] = ParseDate(toks[1].text) ? "studydate" : "studylabel";
					start = 2;
				}
				for (var t = start; t < toks.length; t++) m.roles["f.t" + t] = "seriesdesc";
			}
		}
		if (Object.keys(m.roles).some(function(k) { return m.roles[k] == "studydate"; })) m.datesource = "role";
		if (dicom && (m.datesource == "mtime")) m.datesource = "header";
		return m;
	}

	/* ------------------------------------ applying a mapping ------------------------------------ */

	/* the subjects, studies, and series the mapping produces, plus the files that don't fit and anything to check */
	function Apply(prepared, mapping) {
		var units = prepared.units;
		var rk = RoleKeys(mapping);
		var prk = {};
		ROLES.forEach(function(r) { prk[r] = rk[r].map(function(k) { var pk = ParseKey(k); pk.delims = SegDelims(mapping, pk.seg); return pk; }); });
		var hdr = mapping.header;
		var misfits = {};          /* reason -> {count, examples} */
		var shapes = {};           /* role -> {shape -> count} */
		var badDates = { count: 0, examples: [] };
		var blankSubject = 0;
		var shapecache = Object.create(null);

		var Misfit = function(reason, u) {
			if (!misfits[reason]) misfits[reason] = { reason: reason, count: 0, examples: [] };
			misfits[reason].count++;
			if (misfits[reason].examples.length < 3) misfits[reason].examples.push(u.path);
		};

		/* pass 1: each unit's identity values, and its series within a subject/label/date */
		var proto = new Map();
		var protolist = [];
		for (var i = 0; i < units.length; i++) {
			var u = units[i];
			var vals = {};
			var reason = null;
			for (var r = 0; (r < ROLES.length) && !reason; r++) {
				var role = ROLES[r];
				if (rk[role].length == 0) continue;
				var pieces = [];
				for (var k = 0; k < prk[role].length; k++) {
					var kv = KeyValue(u, prk[role][k]);
					if (kv === null) { reason = KeyValue.reason; break; }
					pieces.push(kv);
				}
				vals[role] = (pieces.length == 1) ? pieces[0] : pieces.join("_");
			}
			if (reason) { Misfit(reason, u); continue; }

			var subject = hdr.subject ? "" : ((rk.subject.length > 0) ? vals.subject : mapping.subjectconst.trim());
			if (!hdr.subject && (subject == "")) { blankSubject++; Misfit((rk.subject.length > 0) ? "The subject part is empty" : "No subject ID in the path, and none typed in", u); continue; }

			var date = "";
			if (rk.studydate.length > 0) {
				date = ParseDate(vals.studydate);
				if (date == "") {
					badDates.count++;
					if (badDates.examples.length < 3) badDates.examples.push(vals.studydate);
					Misfit("The study date isn't a date", u);
					continue;
				}
			}
			for (r = 0; r < ROLES.length; r++) {
				var rl = ROLES[r];
				if (rk[rl].length == 0) continue;
				if (!shapes[rl]) shapes[rl] = {};
				var sh = shapecache[vals[rl]];
				if (sh === undefined) sh = shapecache[vals[rl]] = Shape(vals[rl]);
				shapes[rl][sh] = (shapes[rl][sh] || 0) + 1;
			}

			var label = (rk.studylabel.length > 0) ? vals.studylabel : "";
			var serieskey, desc, num = null;
			if (hdr.series) {
				serieskey = "#header";
				desc = "";
			}
			else if ((rk.seriesdesc.length > 0) || (rk.seriesnum.length > 0)) {
				desc = vals.seriesdesc || "";
				if (rk.seriesnum.length > 0) {
					num = /^\d+$/.test(vals.seriesnum) ? parseInt(vals.seriesnum, 10) : null;
					if (num === null) { Misfit("The series number isn't a number", u); continue; }
				}
				serieskey = num + "|" + desc;
			}
			else if (mapping.grouping == "folder") {
				serieskey = u.dir;
				desc = u.folders.length ? u.folders[u.folders.length - 1] : u.base;
			}
			else {
				serieskey = u.dir + "/" + u.base;
				desc = u.base;
			}

			var pk = subject + "\u0001" + label + "\u0001" + date + "\u0001" + serieskey;
			var p = proto.get(pk);
			if (!p) {
				p = { subject: subject, label: label, date: date, desc: desc, num: num, units: 0, files: 0, size: 0, mtime: 0, mods: null, cmods: null, first: u.path, firstprimary: u.primary, types: [], unitlist: [], ptype: "" };
				proto.set(pk, p);
				protolist.push(p);
			}
			p.units++;
			p.unitlist.push(u);
			if (u.primary && !p.ptype) p.ptype = u.type || "";
			p.files += u.nfiles;
			p.size += u.size;
			if (u.mtime && (!p.mtime || (u.mtime < p.mtime))) p.mtime = u.mtime;
			/* the series is shown by its first data file, not a companion */
			if (u.primary && !p.firstprimary) { p.first = u.path; p.firstprimary = true; }
			if (u.modality) {
				var mk = u.primary ? "mods" : "cmods";
				if (!p[mk]) p[mk] = {};
				p[mk][u.modality] = (p[mk][u.modality] || 0) + 1;
			}
			var ty = u.type || u.key;
			if (p.types.indexOf(ty) < 0) p.types.push(ty);
		}

		/* pass 2: a series' modality is its data files' modality (companions follow), then studies by subject + modality + label/date */
		var subjects = new Map();
		var subjectlist = [];
		var nomodality = 0;
		var studycount = 0, seriescount = 0, filecount = 0, unitcount = 0;
		protolist.forEach(function(p) {
			var mods = p.mods || p.cmods || {};
			var modality = Object.keys(mods).sort(function(a, b) { return mods[b] - mods[a]; })[0] || "";
			if (modality == "") nomodality++;

			var s = subjects.get(p.subject);
			if (!s) {
				s = { id: p.subject, studies: new Map(), studylist: [], files: 0, size: 0 };
				subjects.set(p.subject, s);
				subjectlist.push(s);
			}
			var sk = modality + "\u0001" + p.label + "\u0001" + p.date;
			var st = s.studies.get(sk);
			if (!st) {
				st = { modality: modality, label: p.label, date: p.date, datesource: (p.date != "") ? "path" : "", mtime: 0, series: [], files: 0, size: 0 };
				s.studies.set(sk, st);
				s.studylist.push(st);
				studycount++;
			}
			st.series.push({ desc: p.desc, num: p.num, files: p.files, units: p.units, size: p.size, first: p.first, mtime: p.mtime, types: p.types, filetype: p.ptype || p.types[0] || "", modality: modality, unitlist: p.unitlist });
			st.files += p.files;
			st.size += p.size;
			if (p.mtime && (!st.mtime || (p.mtime < st.mtime))) st.mtime = p.mtime;
			s.files += p.files;
			s.size += p.size;
			seriescount++;
			filecount += p.files;
			unitcount += p.units;
		});

		/* study dates from file times when nothing else gives one; series numbers in file name order unless a part gives them */
		var mtimestudies = 0, nodatestudies = 0, dupnums = 0;
		subjectlist.sort(function(a, b) { return NaturalCompare(a.id, b.id); });
		subjectlist.forEach(function(s) {
			s.studylist.sort(function(a, b) { return NaturalCompare(a.date + a.label + a.modality, b.date + b.label + b.modality); });
			s.studylist.forEach(function(st) {
				if (st.date == "") {
					if (mapping.datesource == "mtime") { st.date = FileDate(st.mtime); st.datesource = "mtime"; mtimestudies++; }
					else if (mapping.datesource == "header") st.datesource = "header";
					else { st.datesource = "none"; nodatestudies++; }
				}
				st.series.sort(function(a, b) { return ((a.num !== null) && (b.num !== null) && (a.num != b.num)) ? a.num - b.num : NaturalCompare(a.first, b.first); });
				var used = {};
				var next = 1;
				st.series.forEach(function(se) {
					if (se.num === null) {
						while (used[next]) next++;
						se.num = next;
						se.numsource = "order";
					}
					else se.numsource = "path";
					if (used[se.num]) dupnums++;
					used[se.num] = true;
				});
			});
			delete s.studies;
		});

		/* parts whose values don't look like the rest, e.g. one subject folder named 'S12' among 'S###' */
		var odd = [];
		var patterns = {};
		Object.keys(shapes).forEach(function(role) {
			var sh = shapes[role];
			var names = Object.keys(sh).sort(function(a, b) { return sh[b] - sh[a]; });
			/* subject patterns keep only the character classes, since subject IDs can be names */
			if (names.length <= 3) patterns[role] = (role == "subject") ? names.map(LetterShape) : names;
			var total = names.reduce(function(n, x) { return n + sh[x]; }, 0);
			if ((names.length > 1) && (sh[names[0]] / total >= 0.8)) {
				var others = total - sh[names[0]];
				odd.push({ role: role, shape: names[0], count: others, shapes: names.slice(1, 4) });
			}
		});

		var misfitlist = Object.keys(misfits).map(function(k) { return misfits[k]; }).sort(function(a, b) { return b.count - a.count; });
		var misfitcount = misfitlist.reduce(function(n, m) { return n + m.count; }, 0);
		return {
			subjects: subjectlist,
			counts: { subjects: subjectlist.length, studies: studycount, series: seriescount, files: filecount, units: unitcount, misfits: misfitcount },
			misfits: misfitlist,
			patterns: patterns,
			checks: { nomodality: nomodality, mtimestudies: mtimestudies, nodatestudies: nodatestudies, duplicatenumbers: dupnums, baddates: badDates, odd: odd, blanksubject: blankSubject }
		};
	}

	/* ------------------------------------ the manifest ------------------------------------ */

	/* a path the server accepts: relative, no empty, '.', or '..' parts, and no hidden parts (which also
	   keeps uploads away from the server's own .tus and .nidb entries). Same rule as IIValidRelPath() */
	function UploadablePath(path) {
		if ((path == "") || (path.length > 4000) || (path.indexOf("\\") >= 0)) return false;
		var parts = path.split("/");
		if (parts.length > 64) return false;
		return parts.every(function(x) { return (x != "") && (x.charAt(0) != ".") && (x.length <= 255) && !/[\x00-\x1f\x7f]/.test(x); });
	}

	/* what the server imports: subject -> study -> series -> paths, from a tree made by Apply(). Each path is
	   [path, size, bundle], where a bundle is a folder imported whole (e.g. .mff). 'dates' gives a date
	   (YYYY-MM-DD) for studies that have none, keyed by StudyKey(). Series with any path the server
	   wouldn't accept are left out and counted in 'skipped' */
	function Manifest(tree, dates) {
		var subjects = [];
		var skipped = { series: 0, files: 0, examples: [] };
		var counts = { subjects: 0, studies: 0, series: 0, files: 0, size: 0 };
		tree.subjects.forEach(function(s) {
			var studies = [];
			s.studylist.forEach(function(st) {
				var date = st.date, datesource = st.datesource;
				if ((date == "") && dates && dates[StudyKey(s.id, st)]) { date = dates[StudyKey(s.id, st)]; datesource = "user"; }
				var series = [];
				st.series.forEach(function(se) {
					var bad = se.unitlist.filter(function(u) { return !UploadablePath(u.path); });
					if (bad.length > 0) {
						skipped.series++;
						skipped.files += se.files;
						if (skipped.examples.length < 3) skipped.examples.push(bad[0].path);
						return;
					}
					series.push({
						num: se.num, numsource: se.numsource, desc: se.desc, modality: se.modality, filetype: se.filetype, size: se.size, files: se.files,
						paths: se.unitlist.map(function(u) { return [u.path, u.bundle ? u.nfiles : 1, u.size, u.bundle ? 1 : 0]; })
					});
					counts.series++;
					counts.files += se.files;
					counts.size += se.size;
				});
				if (series.length == 0) return;
				studies.push({ modality: st.modality, visit: st.label, date: date, datesource: datesource, series: series });
				counts.studies++;
			});
			if (studies.length == 0) return;
			subjects.push({ id: s.id, studies: studies });
			counts.subjects++;
		});
		return { manifest: { manifest_version: 1, subjects: subjects }, counts: counts, skipped: skipped };
	}

	/* a study's key, as used by the match results and Manifest() dates */
	function StudyKey(id, st) { return [id, st.modality, st.date, st.label].join("\u0001"); }

	/* ------------------------------------ describing a mapping ------------------------------------ */

	/* a path template for a unit, e.g. {subject}/{studylabel}/{studydate}_{seriesdesc}.vhdr. Parts with no role are * */
	function Template(u, mapping) {
		if (!u) return "";
		var segs = u.folders.map(function(f, i) { return TemplateSegment(f, "d" + i, mapping); });
		segs.push(TemplateSegment(u.base, "f", mapping) + u.ext);
		return segs.join("/");
	}

	function TemplateSegment(s, seg, mapping) {
		if (mapping.roles[seg]) return "{" + mapping.roles[seg] + "}";
		var toks = Tokenize(s, SegDelims(mapping, seg));
		if (!toks.some(function(t, i) { return !!mapping.roles[seg + ".t" + i]; })) return "*";
		var out = "", pos = 0;
		toks.forEach(function(t, i) {
			out += s.substring(pos, t.start);
			var role = mapping.roles[seg + ".t" + i];
			out += role ? "{" + role + "}" : "*";
			pos = t.end;
		});
		return out + s.substring(pos);
	}

	/* the mapping in the recipe format of doc/universal-import.md §6.4. Holds patterns, never sample values */
	function Recipe(mapping, axes, groups, sample, tree) {
		var rk = RoleKeys(mapping);
		var src = function(role) { return rk[role].map(function(k) { return "path:" + k; }); };
		return {
			recipe_version: 1,
			axes: axes,
			include: groups.filter(function(g) { return g.include; }).map(function(g) { return g.key; }),
			ignore: groups.filter(function(g) { return !g.include; }).map(function(g) { return g.key; }),
			grouping: { series: (rk.seriesdesc.length || rk.seriesnum.length) ? "series_parts" : (mapping.grouping == "folder" ? "same_dir" : "same_basename") },
			path_template: Template(sample, mapping),
			roles: mapping.roles,
			delimiters: mapping.delims || {},
			identity: {
				subject: mapping.header.subject ? { sources: ["header:PatientID"] } : (rk.subject.length ? { sources: src("subject"), match: ["uid", "altuid"] } : { sources: ["constant"] }),
				study: {
					datetime: { sources: mapping.header.study ? ["header:StudyDateTime"] : (rk.studydate.length ? src("studydate") : (mapping.datesource == "mtime" ? ["file:mtime"] : (mapping.datesource == "header" ? ["header:StudyDateTime"] : []))) },
					label: { sources: src("studylabel") },
					match: rk.studylabel.length ? ["label", "modality+date"] : ["modality+date"]
				},
				series: {
					number: { sources: mapping.header.series ? ["header:SeriesNumber"] : (rk.seriesnum.length ? src("seriesnum") : ["order:filename"]) },
					desc: { sources: mapping.header.series ? ["header:SeriesDescription"] : src("seriesdesc") }
				}
			},
			patterns: tree.patterns
		};
	}

	window.ImportMap = {
		ROLES: ROLES, Tokenize: Tokenize, ParseDate: ParseDate, Shape: Shape, Prepare: Prepare, UnitTokens: UnitTokens, DELIMS: DELIMS, SegDelims: SegDelims, Manifest: Manifest, StudyKey: StudyKey, UploadablePath: UploadablePath,
		SampleUnit: SampleUnit, ShapeExamples: ShapeExamples, DefaultMapping: DefaultMapping, Apply: Apply,
		Template: Template, Recipe: Recipe, RoleKeys: RoleKeys
	};
})();
