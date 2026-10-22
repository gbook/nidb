/* ------------------------------------------------------------------------------
   NiDB sqrlreader.js
   Copyright (C) 2004 - 2026
   Gregory A Book <gregory.book@hhchealth.org> <gbook@gbook.org>
   Olin Neuropsychiatry Research Center, Hartford Hospital
   ------------------------------------------------------------------------------
   GPLv3 License: see importwizard.php
   ------------------------------------------------------------------------------

   Reads a squirrel package (.sqrl = 7z, or .zip) in the browser, to show what's in it before
   it is uploaded. See doc/universal-import.md §5 Step 0.

   Only small parts of the file are read, with File.slice(), so multi-GB packages are fine:
     7z   the 32-byte start header, the index at the end of the archive, and the one compressed
          block holding squirrel.json. squirrel writes 7z non-solid with LZMA2, so squirrel.json
          is compressed on its own
     zip  the central directory at the end, and squirrel.json's compressed data

   Supported: 7z with Copy, LZMA, or LZMA2 (one coder per block, not encrypted), and zip with
   store or deflate (the browser's DecompressionStream). Anything else (solid blocks over the
   size limit, BCJ filters, AES, other zip methods) is reported as unsupported; the package can
   still be imported, it just can't be previewed.

   Usage:
     SqrlReader.ReadPackage(file) -> Promise({ format, entries, json })   rejects with an Error
     SqrlReader.Summarize(json)   -> counts of what the package holds
   ------------------------------------------------------------------------------ */

(function() {
	"use strict";

	var MAXJSONBYTES = 268435456; /* largest squirrel.json (or 7z block holding it) that is decoded */

	function Fail(msg) { throw new Error(msg); }

	function ReadSlice(file, start, end) {
		return file.slice(start, end).arrayBuffer().then(function(b) { return new Uint8Array(b); });
	}

	/* ------------------------------------ CRC32 ------------------------------------ */

	var CRCTABLE = null;
	function Crc32(bytes) {
		if (!CRCTABLE) {
			CRCTABLE = new Uint32Array(256);
			for (var n = 0; n < 256; n++) {
				var c = n;
				for (var k = 0; k < 8; k++) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
				CRCTABLE[n] = c >>> 0;
			}
		}
		var crc = 0xFFFFFFFF;
		for (var i = 0; i < bytes.length; i++) crc = CRCTABLE[(crc ^ bytes[i]) & 0xFF] ^ (crc >>> 8);
		return (crc ^ 0xFFFFFFFF) >>> 0;
	}

	function U16(b, p) { return b[p] | (b[p + 1] << 8); }
	function U32(b, p) { return (b[p] | (b[p + 1] << 8) | (b[p + 2] << 16) | (b[p + 3] << 24)) >>> 0; }
	function U64(b, p) { return U32(b, p) + U32(b, p + 4) * 4294967296; }

	/* ------------------------------------ LZMA decoder ------------------------------------ */
	/* Follows LzmaSpec.cpp from the LZMA SDK (public domain). The output buffer is the dictionary:
	   the whole block is decoded into memory, so matches copy straight from earlier output */

	function Lzma(out) {
		this.out = out;
		this.pos = 0;           /* output position */
		this.dictStart = 0;     /* position of the last dictionary reset (LZMA2) */
		this.isMatch = new Uint16Array(192);
		this.isRep = new Uint16Array(12);
		this.isRepG0 = new Uint16Array(12);
		this.isRepG1 = new Uint16Array(12);
		this.isRepG2 = new Uint16Array(12);
		this.isRep0Long = new Uint16Array(192);
		this.posSlot = new Uint16Array(256);
		this.posDecoders = new Uint16Array(115);
		this.align = new Uint16Array(16);
		this.matchLen = new Uint16Array(514);
		this.repLen = new Uint16Array(514);
		this.literal = null;
	}

	Lzma.prototype.SetProps = function(lc, lp, pb) {
		if ((lc > 8) || (lp > 4) || (pb > 4)) Fail("Bad LZMA properties");
		this.lc = lc; this.lp = lp; this.pb = pb;
		this.literal = new Uint16Array(0x300 << (lc + lp));
	};

	/* properties byte: lc + 9 * (lp + 5 * pb) */
	Lzma.prototype.SetPropsByte = function(d) {
		if (d >= 225) Fail("Bad LZMA properties");
		this.SetProps(d % 9, Math.floor(d / 9) % 5, Math.floor(d / 45));
	};

	Lzma.prototype.Reset = function() {
		var arrays = [this.isMatch, this.isRep, this.isRepG0, this.isRepG1, this.isRepG2, this.isRep0Long, this.posSlot, this.posDecoders, this.align, this.matchLen, this.repLen, this.literal];
		for (var i = 0; i < arrays.length; i++) arrays[i].fill(1024);
		this.state = 0;
		this.rep0 = this.rep1 = this.rep2 = this.rep3 = 0;
	};

	/* range decoder. range and code are kept as non-negative numbers below 2^32 */
	Lzma.prototype.InitRange = function(buf, pos) {
		if (buf[pos] !== 0) Fail("Corrupt LZMA data");
		this.buf = buf;
		this.code = U32([buf[pos + 4], buf[pos + 3], buf[pos + 2], buf[pos + 1]], 0);
		this.range = 0xFFFFFFFF;
		this.ip = pos + 5;
	};

	Lzma.prototype.Bit = function(probs, i) {
		var p = probs[i];
		var bound = (this.range >>> 11) * p;
		var bit;
		if (this.code < bound) {
			this.range = bound;
			probs[i] = p + ((2048 - p) >>> 5);
			bit = 0;
		}
		else {
			this.range -= bound;
			this.code -= bound;
			probs[i] = p - (p >>> 5);
			bit = 1;
		}
		if (this.range < 16777216) {
			this.range *= 256;
			this.code = this.code * 256 + (this.buf[this.ip++] | 0);
		}
		return bit;
	};

	Lzma.prototype.DirectBits = function(n) {
		var res = 0;
		do {
			this.range = Math.floor(this.range / 2);
			var bit = 0;
			if (this.code >= this.range) { this.code -= this.range; bit = 1; }
			res = res * 2 + bit;
			if (this.range < 16777216) {
				this.range *= 256;
				this.code = this.code * 256 + (this.buf[this.ip++] | 0);
			}
		} while (--n);
		return res;
	};

	Lzma.prototype.Tree = function(probs, off, numBits) {
		var m = 1;
		for (var i = 0; i < numBits; i++) m = (m << 1) + this.Bit(probs, off + m);
		return m - (1 << numBits);
	};

	Lzma.prototype.Reverse = function(probs, off, numBits) {
		var m = 1, sym = 0;
		for (var i = 0; i < numBits; i++) {
			var bit = this.Bit(probs, off + m);
			m = (m << 1) + bit;
			sym |= bit << i;
		}
		return sym;
	};

	/* length decoder layout: [0] choice, [1] choice2, [2..129] low (8 per posState), [130..257] mid, [258..513] high */
	Lzma.prototype.Len = function(probs, posState) {
		if (this.Bit(probs, 0) === 0) return this.Tree(probs, 2 + posState * 8, 3);
		if (this.Bit(probs, 1) === 0) return 8 + this.Tree(probs, 130 + posState * 8, 3);
		return 16 + this.Tree(probs, 258, 8);
	};

	Lzma.prototype.Distance = function(len) {
		var lenState = (len < 3) ? len : 3;
		var posSlot = this.Tree(this.posSlot, lenState << 6, 6);
		if (posSlot < 4) return posSlot;
		var numDirectBits = (posSlot >>> 1) - 1;
		var dist = (2 | (posSlot & 1)) * Math.pow(2, numDirectBits);
		if (posSlot < 14) return dist + this.Reverse(this.posDecoders, dist - posSlot, numDirectBits);
		return dist + this.DirectBits(numDirectBits - 4) * 16 + this.Reverse(this.align, 0, 4);
	};

	/* decode until the output reaches outLimit, or an end marker */
	Lzma.prototype.DecodeTo = function(outLimit) {
		var out = this.out;
		var pbMask = (1 << this.pb) - 1, lpMask = (1 << this.lp) - 1, lc = this.lc;
		while (this.pos < outLimit) {
			var rel = this.pos - this.dictStart;
			var posState = rel & pbMask;
			var state = this.state;

			/* literal */
			if (this.Bit(this.isMatch, (state << 4) + posState) === 0) {
				var prev = (rel > 0) ? out[this.pos - 1] : 0;
				var base = 0x300 * (((rel & lpMask) << lc) + (prev >>> (8 - lc)));
				var sym = 1;
				if (state >= 7) {
					var matchByte = out[this.pos - this.rep0 - 1];
					do {
						var matchBit = (matchByte >>> 7) & 1;
						matchByte = (matchByte << 1) & 0xFF;
						var bit = this.Bit(this.literal, base + ((1 + matchBit) << 8) + sym);
						sym = (sym << 1) | bit;
						if (matchBit !== bit) break;
					} while (sym < 0x100);
				}
				while (sym < 0x100) sym = (sym << 1) | this.Bit(this.literal, base + sym);
				out[this.pos++] = sym - 0x100;
				this.state = (state < 4) ? 0 : ((state < 10) ? state - 3 : state - 6);
				continue;
			}

			var len;
			if (this.Bit(this.isRep, state) !== 0) {
				if (rel === 0) Fail("Corrupt LZMA data");
				if (this.Bit(this.isRepG0, state) === 0) {
					/* short rep: one byte at rep0 */
					if (this.Bit(this.isRep0Long, (state << 4) + posState) === 0) {
						this.state = (state < 7) ? 9 : 11;
						out[this.pos] = out[this.pos - this.rep0 - 1];
						this.pos++;
						continue;
					}
				}
				else {
					var dist;
					if (this.Bit(this.isRepG1, state) === 0) {
						dist = this.rep1;
					}
					else {
						if (this.Bit(this.isRepG2, state) === 0) {
							dist = this.rep2;
						}
						else {
							dist = this.rep3;
							this.rep3 = this.rep2;
						}
						this.rep2 = this.rep1;
					}
					this.rep1 = this.rep0;
					this.rep0 = dist;
				}
				len = this.Len(this.repLen, posState);
				this.state = (state < 7) ? 8 : 11;
			}
			else {
				this.rep3 = this.rep2;
				this.rep2 = this.rep1;
				this.rep1 = this.rep0;
				len = this.Len(this.matchLen, posState);
				this.state = (state < 7) ? 7 : 10;
				this.rep0 = this.Distance(len);
				if (this.rep0 === 0xFFFFFFFF) return; /* end marker */
				if (this.rep0 >= rel) Fail("Corrupt LZMA data");
			}

			len += 2;
			var src = this.pos - this.rep0 - 1;
			var end = Math.min(this.pos + len, outLimit);
			while (this.pos < end) out[this.pos++] = out[src++];
		}
	};

	/* 7z LZMA coder: 5 property bytes (lc/lp/pb, dictionary size) */
	function DecodeLzma(input, props, outSize) {
		if (props.length < 5) Fail("Bad LZMA properties");
		var lz = new Lzma(new Uint8Array(outSize));
		lz.SetPropsByte(props[0]);
		lz.Reset();
		lz.InitRange(input, 0);
		lz.DecodeTo(outSize);
		if (lz.pos !== outSize) Fail("LZMA data ended early");
		return lz.out;
	}

	/* 7z LZMA2 coder: a sequence of chunks, each LZMA-compressed or stored */
	function DecodeLzma2(input, outSize) {
		var lz = new Lzma(new Uint8Array(outSize));
		var ip = 0, haveProps = false;
		while (true) {
			if (ip >= input.length) Fail("LZMA2 data ended early");
			var c = input[ip++];
			if (c === 0) break;

			if ((c === 1) || (c === 2)) {
				/* stored chunk; 1 also resets the dictionary */
				var ssize = ((input[ip] << 8) | input[ip + 1]) + 1;
				ip += 2;
				if (lz.pos + ssize > outSize) Fail("Corrupt LZMA2 data");
				if (c === 1) lz.dictStart = lz.pos;
				lz.out.set(input.subarray(ip, ip + ssize), lz.pos);
				lz.pos += ssize;
				ip += ssize;
				continue;
			}
			if (c < 0x80) Fail("Corrupt LZMA2 data");

			var usize = (c & 0x1F) * 65536 + (input[ip] << 8) + input[ip + 1] + 1;
			var psize = ((input[ip + 2] << 8) | input[ip + 3]) + 1;
			ip += 4;
			var reset = (c >>> 5) & 3; /* 0 none, 1 state, 2 state + new props, 3 state + new props + dictionary */
			if (reset === 3) lz.dictStart = lz.pos;
			if (reset >= 2) { lz.SetPropsByte(input[ip++]); haveProps = true; }
			if (!haveProps) Fail("Corrupt LZMA2 data");
			if (reset >= 1) lz.Reset();
			if (lz.pos + usize > outSize) Fail("Corrupt LZMA2 data");
			lz.InitRange(input, ip);
			lz.DecodeTo(lz.pos + usize);
			ip += psize;
		}
		if (lz.pos !== outSize) Fail("LZMA2 data ended early");
		return lz.out;
	}

	/* ------------------------------------ 7z ------------------------------------ */

	var SIG7Z = [0x37, 0x7A, 0xBC, 0xAF, 0x27, 0x1C];

	/* reader for 7z header structures */
	function R7(bytes) { this.b = bytes; this.p = 0; }
	R7.prototype.Byte = function() { if (this.p >= this.b.length) Fail("Corrupt 7z header"); return this.b[this.p++]; };
	R7.prototype.Number = function() {
		var first = this.Byte(), mask = 0x80, value = 0;
		for (var i = 0; i < 8; i++) {
			if ((first & mask) === 0) return value + (first & (mask - 1)) * Math.pow(2, 8 * i);
			value += this.Byte() * Math.pow(2, 8 * i);
			mask >>>= 1;
		}
		return value;
	};
	R7.prototype.UInt32 = function() { var v = U32(this.b, this.p); this.p += 4; return v; };
	R7.prototype.Bits = function(n) {
		var bits = [], b = 0;
		for (var i = 0; i < n; i++) {
			if ((i & 7) === 0) b = this.Byte();
			bits.push((b >>> (7 - (i & 7))) & 1);
		}
		return bits;
	};
	R7.prototype.Digests = function(n) {
		var defined = (this.Byte() !== 0) ? null : this.Bits(n);
		var crcs = [];
		for (var i = 0; i < n; i++) crcs.push((!defined || defined[i]) ? this.UInt32() : null);
		return crcs;
	};
	R7.prototype.Expect = function(id) { if (this.Number() !== id) Fail("Unsupported 7z header"); };

	function ParseFolder(r) {
		var numCoders = r.Number();
		var folder = { coders: [], bindPairs: [], packedStreams: [], unpackSizes: [], crc: null };
		var totalIn = 0, totalOut = 0;
		for (var i = 0; i < numCoders; i++) {
			var flag = r.Byte();
			if (flag & 0x80) Fail("Unsupported 7z coder");
			var id = "";
			for (var j = 0; j < (flag & 0x0F); j++) id += ("0" + r.Byte().toString(16)).slice(-2);
			var numIn = 1, numOut = 1;
			if (flag & 0x10) { numIn = r.Number(); numOut = r.Number(); }
			var props = new Uint8Array(0);
			if (flag & 0x20) { var n = r.Number(); props = r.b.subarray(r.p, r.p + n); r.p += n; }
			folder.coders.push({ id: id, numIn: numIn, numOut: numOut, props: props });
			totalIn += numIn;
			totalOut += numOut;
		}
		for (var k = 0; k < totalOut - 1; k++) folder.bindPairs.push({ inIndex: r.Number(), outIndex: r.Number() });
		var numPacked = totalIn - (totalOut - 1);
		if (numPacked === 1) {
			for (var s = 0; s < totalIn; s++)
				if (!folder.bindPairs.some(function(bp) { return bp.inIndex === s; })) { folder.packedStreams.push(s); break; }
		}
		else {
			for (var q = 0; q < numPacked; q++) folder.packedStreams.push(r.Number());
		}
		folder.totalOut = totalOut;
		return folder;
	}

	/* the folder's final output: the out stream no bind pair consumes */
	function FolderUnpackSize(folder) {
		for (var i = folder.unpackSizes.length - 1; i >= 0; i--)
			if (!folder.bindPairs.some(function(bp) { return bp.outIndex === i; })) return folder.unpackSizes[i];
		return 0;
	}

	function ParseStreamsInfo(r) {
		var si = { packPos: 0, packSizes: [], folders: [], numUnpackStreams: [], subSizes: [] };
		var id;
		while ((id = r.Number()) !== 0) {
			if (id === 0x06) {
				/* PackInfo */
				si.packPos = r.Number();
				var numPack = r.Number();
				var pid;
				while ((pid = r.Number()) !== 0) {
					if (pid === 0x09) { for (var i = 0; i < numPack; i++) si.packSizes.push(r.Number()); }
					else if (pid === 0x0A) r.Digests(numPack);
					else Fail("Unsupported 7z header");
				}
			}
			else if (id === 0x07) {
				/* UnpackInfo */
				r.Expect(0x0B);
				var numFolders = r.Number();
				if (r.Byte() !== 0) Fail("Unsupported 7z header");
				for (var f = 0; f < numFolders; f++) si.folders.push(ParseFolder(r));
				r.Expect(0x0C);
				si.folders.forEach(function(folder) { for (var o = 0; o < folder.totalOut; o++) folder.unpackSizes.push(r.Number()); });
				var uid;
				while ((uid = r.Number()) !== 0) {
					if (uid === 0x0A) { var crcs = r.Digests(numFolders); si.folders.forEach(function(folder, n) { folder.crc = crcs[n]; }); }
					else Fail("Unsupported 7z header");
				}
			}
			else if (id === 0x08) {
				/* SubStreamsInfo: how each folder splits into files */
				si.numUnpackStreams = si.folders.map(function() { return 1; });
				var sid = r.Number();
				if (sid === 0x0D) {
					for (var g = 0; g < si.folders.length; g++) si.numUnpackStreams[g] = r.Number();
					sid = r.Number();
				}
				var hasSizes = (sid === 0x09);
				si.folders.forEach(function(folder, n) {
					var count = si.numUnpackStreams[n], sum = 0, sizes = [];
					if (count === 0) { si.subSizes.push(sizes); return; }
					for (var j = 0; j < count - 1; j++) {
						if (!hasSizes) Fail("Corrupt 7z header");
						var sz = r.Number();
						sizes.push(sz);
						sum += sz;
					}
					sizes.push(FolderUnpackSize(folder) - sum);
					si.subSizes.push(sizes);
				});
				if (hasSizes) sid = r.Number();
				if (sid === 0x0A) {
					var numDigests = 0;
					si.folders.forEach(function(folder, n) { if ((si.numUnpackStreams[n] !== 1) || (folder.crc === null)) numDigests += si.numUnpackStreams[n]; });
					r.Digests(numDigests);
					sid = r.Number();
				}
				if (sid !== 0) Fail("Unsupported 7z header");
			}
			else {
				Fail("Unsupported 7z header");
			}
		}
		if (si.numUnpackStreams.length === 0) {
			/* no SubStreamsInfo: one file per folder */
			si.numUnpackStreams = si.folders.map(function() { return 1; });
			si.subSizes = si.folders.map(function(folder) { return [FolderUnpackSize(folder)]; });
		}
		return si;
	}

	function ParseFilesInfo(r) {
		var numFiles = r.Number();
		var names = [], emptyStream = null, emptyFile = null;
		var type;
		while ((type = r.Number()) !== 0) {
			var size = r.Number();
			var end = r.p + size;
			if (type === 0x0E) {
				emptyStream = r.Bits(numFiles);
			}
			else if (type === 0x0F) {
				var numEmpty = emptyStream ? emptyStream.reduce(function(a, b) { return a + b; }, 0) : 0;
				emptyFile = r.Bits(numEmpty);
			}
			else if (type === 0x11) {
				if (r.Byte() !== 0) Fail("Unsupported 7z header");
				var s = "";
				while (r.p + 1 < end) {
					var ch = U16(r.b, r.p);
					r.p += 2;
					if (ch === 0) { names.push(s); s = ""; }
					else s += String.fromCharCode(ch);
				}
			}
			r.p = end;
		}
		var files = [], e = 0;
		for (var i = 0; i < numFiles; i++) {
			var empty = emptyStream ? (emptyStream[i] === 1) : false;
			var isDir = empty && !(emptyFile && emptyFile[e]);
			if (empty) e++;
			files.push({ path: (names[i] || "").replace(/\\/g, "/"), hasStream: !empty, isDir: isDir });
		}
		return files;
	}

	function ParseHeader(r) {
		var arch = { streams: null, files: [] };
		var id = r.Number();
		if (id === 0x02) {
			/* archive properties */
			var t;
			while ((t = r.Number()) !== 0) { var n = r.Number(); r.p += n; }
			id = r.Number();
		}
		if (id === 0x03) { ParseStreamsInfo(r); id = r.Number(); }
		if (id === 0x04) { arch.streams = ParseStreamsInfo(r); id = r.Number(); }
		if (id === 0x05) { arch.files = ParseFilesInfo(r); id = r.Number(); }
		if (id !== 0) Fail("Unsupported 7z header");
		return arch;
	}

	/* decode one folder (block). Only single-coder folders: Copy, LZMA, LZMA2 */
	function DecodeFolder(file, si, f) {
		var folder = si.folders[f];
		var unpackSize = FolderUnpackSize(folder);
		if (folder.coders.length !== 1) return Promise.reject(new Error("This package uses 7z filters the preview doesn't support"));
		var coder = folder.coders[0];
		if (coder.id === "06f10701") return Promise.reject(new Error("The package is encrypted"));
		if ((coder.id !== "00") && (coder.id !== "030101") && (coder.id !== "21")) return Promise.reject(new Error("This package uses a 7z compression method the preview doesn't support"));
		if (unpackSize > MAXJSONBYTES) return Promise.reject(new Error("squirrel.json is stored in a block too large to preview"));

		/* the folder's packed stream: after the packed streams of the folders before it */
		var first = 0;
		for (var i = 0; i < f; i++) first += si.folders[i].packedStreams.length;
		var offset = 32 + si.packPos;
		for (var j = 0; j < first; j++) offset += si.packSizes[j];

		return ReadSlice(file, offset, offset + si.packSizes[first]).then(function(packed) {
			var out;
			if (coder.id === "00") out = packed;
			else if (coder.id === "030101") out = DecodeLzma(packed, coder.props, unpackSize);
			else out = DecodeLzma2(packed, unpackSize);
			if ((folder.crc !== null) && (Crc32(out) !== folder.crc)) Fail("The package is damaged (CRC mismatch)");
			return out;
		});
	}

	function Open7z(file) {
		return ReadSlice(file, 0, 32).then(function(sh) {
			for (var i = 0; i < 6; i++) if (sh[i] !== SIG7Z[i]) Fail("Not a 7z file");
			var nextOff = U64(sh, 12), nextSize = U64(sh, 20), nextCrc = U32(sh, 28);
			if (nextSize === 0) return { format: "7z", entries: [] };
			if (32 + nextOff + nextSize > file.size) Fail("The package is incomplete (was it fully copied?)");
			return ReadSlice(file, 32 + nextOff, 32 + nextOff + nextSize).then(function(hb) {
				if (Crc32(hb) !== nextCrc) Fail("The package is damaged (CRC mismatch)");
				/* the index is usually compressed (0x17): decode it, then parse the real header (0x01) */
				var step = function(bytes) {
					var r = new R7(bytes);
					var id = r.Number();
					if (id === 0x17) {
						var si = ParseStreamsInfo(r);
						return DecodeFolder(file, si, 0).then(step);
					}
					if (id !== 0x01) Fail("Unsupported 7z header");
					return Promise.resolve(ParseHeader(r));
				};
				return step(hb);
			}).then(function(arch) {
				/* attach each file to its folder and its offset within the folder */
				var si = arch.streams, entries = [], folder = 0, sub = 0, offset = 0;
				arch.files.forEach(function(f) {
					if (!f.hasStream) { entries.push({ path: f.path, size: 0, isDir: f.isDir, folder: -1 }); return; }
					while (si && (folder < si.folders.length) && (sub >= si.numUnpackStreams[folder])) { folder++; sub = 0; offset = 0; }
					if (!si || (folder >= si.folders.length)) Fail("Corrupt 7z header");
					var size = si.subSizes[folder][sub];
					entries.push({ path: f.path, size: size, isDir: false, folder: folder, offset: offset });
					offset += size;
					sub++;
				});
				return { format: "7z", entries: entries, streams: si };
			});
		});
	}

	function Read7zEntry(file, arch, entry) {
		if (entry.folder < 0) return Promise.resolve(new Uint8Array(0));
		return DecodeFolder(file, arch.streams, entry.folder).then(function(out) {
			return out.subarray(entry.offset, entry.offset + entry.size);
		});
	}

	/* ------------------------------------ zip ------------------------------------ */

	function OpenZip(file) {
		var tailLen = Math.min(file.size, 65557);
		return ReadSlice(file, file.size - tailLen, file.size).then(function(tail) {
			var eocd = -1;
			for (var i = tail.length - 22; i >= 0; i--)
				if (U32(tail, i) === 0x06054b50) { eocd = i; break; }
			if (eocd < 0) Fail("Not a zip file, or the zip is incomplete");
			var count = U16(tail, eocd + 10), cdSize = U32(tail, eocd + 12), cdOffset = U32(tail, eocd + 16);
			var zip64 = (count === 0xFFFF) || (cdSize === 0xFFFFFFFF) || (cdOffset === 0xFFFFFFFF);
			var locate = Promise.resolve();
			if (zip64) {
				/* Zip64: the locator just before the end record points to the Zip64 end record */
				if ((eocd < 20) || (U32(tail, eocd - 20) !== 0x07064b50)) Fail("Unsupported zip file");
				var z64 = U64(tail, eocd - 12);
				locate = ReadSlice(file, z64, z64 + 56).then(function(z) {
					if (U32(z, 0) !== 0x06064b50) Fail("Unsupported zip file");
					count = U64(z, 32); cdSize = U64(z, 40); cdOffset = U64(z, 48);
				});
			}
			return locate.then(function() { return ReadSlice(file, cdOffset, cdOffset + cdSize); });
		}).then(function(cd) {
			var entries = [], p = 0;
			var decoder = new TextDecoder("utf-8");
			while ((p + 46 <= cd.length) && (U32(cd, p) === 0x02014b50)) {
				var e = { flags: U16(cd, p + 8), method: U16(cd, p + 10), crc: U32(cd, p + 16), csize: U32(cd, p + 20), size: U32(cd, p + 24), lho: U32(cd, p + 42) };
				var nlen = U16(cd, p + 28), xlen = U16(cd, p + 30), clen = U16(cd, p + 32);
				e.path = decoder.decode(cd.subarray(p + 46, p + 46 + nlen)).replace(/\\/g, "/");
				e.isDir = EndsWith(e.path, "/");
				/* Zip64 extra field: the 64-bit values for whichever fields are 0xFFFFFFFF, in this order */
				var x = p + 46 + nlen, xend = x + xlen;
				while (x + 4 <= xend) {
					var xid = U16(cd, x), xsize = U16(cd, x + 2), q = x + 4;
					if (xid === 0x0001) {
						if (e.size === 0xFFFFFFFF) { e.size = U64(cd, q); q += 8; }
						if (e.csize === 0xFFFFFFFF) { e.csize = U64(cd, q); q += 8; }
						if (e.lho === 0xFFFFFFFF) { e.lho = U64(cd, q); q += 8; }
					}
					x += 4 + xsize;
				}
				entries.push(e);
				p += 46 + nlen + xlen + clen;
			}
			return { format: "zip", entries: entries };
		});
	}

	function EndsWith(s, suffix) { return (s.length >= suffix.length) && (s.substring(s.length - suffix.length) === suffix); }

	function Inflate(bytes) {
		if (typeof DecompressionStream === "undefined") return Promise.reject(new Error("This browser can't decompress zip files"));
		var stream = new Blob([bytes]).stream().pipeThrough(new DecompressionStream("deflate-raw"));
		return new Response(stream).arrayBuffer().then(function(b) { return new Uint8Array(b); });
	}

	function ReadZipEntry(file, entry) {
		if (entry.flags & 1) return Promise.reject(new Error("The package is encrypted"));
		if ((entry.method !== 0) && (entry.method !== 8)) return Promise.reject(new Error("This package uses a zip compression method the preview doesn't support"));
		if (entry.size > MAXJSONBYTES) return Promise.reject(new Error("squirrel.json is too large to preview"));
		return ReadSlice(file, entry.lho, entry.lho + 30).then(function(lh) {
			if (U32(lh, 0) !== 0x04034b50) Fail("The package is damaged");
			var start = entry.lho + 30 + U16(lh, 26) + U16(lh, 28);
			return ReadSlice(file, start, start + entry.csize);
		}).then(function(data) {
			return (entry.method === 0) ? data : Inflate(data);
		}).then(function(out) {
			if (Crc32(out) !== entry.crc) Fail("The package is damaged (CRC mismatch)");
			return out;
		});
	}

	/* ------------------------------------ packages ------------------------------------ */

	function Open(file) {
		return ReadSlice(file, 0, 6).then(function(b) {
			if ((b.length === 6) && SIG7Z.every(function(v, i) { return b[i] === v; })) return Open7z(file);
			if ((b[0] === 0x50) && (b[1] === 0x4B)) return OpenZip(file);
			Fail("This isn't a squirrel package: it is neither a .sqrl (7z) nor a .zip file");
		});
	}

	function ReadEntry(file, arch, entry) {
		return (arch.format === "7z") ? Read7zEntry(file, arch, entry) : ReadZipEntry(file, entry);
	}

	function ReadPackage(file) {
		return Open(file).then(function(arch) {
			var entry = null;
			arch.entries.forEach(function(e) { if (e.path === "squirrel.json") entry = e; });
			if (!entry) Fail("This archive has no squirrel.json at the top level, so it isn't a squirrel package");
			return ReadEntry(file, arch, entry).then(function(bytes) {
				var json;
				try { json = JSON.parse(new TextDecoder("utf-8").decode(bytes)); }
				catch (e) { Fail("The package's squirrel.json couldn't be read"); }
				return { format: arch.format, entries: arch.entries, json: json };
			});
		});
	}

	/* ------------------------------------ summary ------------------------------------ */

	function Arr(v) { return Array.isArray(v) ? v : []; }

	/* what a squirrel.json holds: package details and counts (see squirrel::ToJSON) */
	function Summarize(json) {
		var pkg = (json && json["package"]) || {};
		var data = (json && json.data) || {};
		var subjects = Arr(data.subjects).length ? Arr(data.subjects) : Arr(json && json.subjects);
		var s = {
			name: pkg.PackageName || "", description: pkg.Description || "", datetime: pkg.Datetime || "", dataformat: pkg.DataFormat || "",
			squirrelversion: pkg.SquirrelVersion || "", license: pkg.License || "",
			subjects: subjects.length, studies: 0, series: 0, modalities: {}, observations: 0, interventions: 0, analyses: 0,
			groupanalyses: Arr(data["group-analysis"]).length, pipelines: Arr(json && json.pipelines).length,
			experiments: Arr(json && json.experiments).length, datadictionaries: Arr(json && json["data-dictionaries"]).length,
			files: Number((json && json.TotalFileCount) || 0), size: Number((json && json.TotalSize) || 0), subjectids: []
		};
		subjects.forEach(function(subj) {
			if (subj.SubjectID) s.subjectids.push(String(subj.SubjectID));
			s.observations += Arr(subj.observations).length;
			s.interventions += Arr(subj.interventions).length;
			Arr(subj.studies).forEach(function(st) {
				s.studies++;
				var mod = String(st.Modality || "unknown").toUpperCase();
				var nseries = Arr(st.series).length;
				s.modalities[mod] = s.modalities[mod] || { studies: 0, series: 0 };
				s.modalities[mod].studies++;
				s.modalities[mod].series += nseries;
				s.series += nseries;
				s.analyses += Arr(st.analyses).length;
			});
		});
		return s;
	}

	window.SqrlReader = { ReadPackage: ReadPackage, Summarize: Summarize, Open: Open, ReadEntry: ReadEntry };
})();
