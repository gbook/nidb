<?
 // ------------------------------------------------------------------------------
 // NiDB mario.php
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

	define("LEGIT_REQUEST", true);

	session_start();
?>

<html>
	<head>
		<link rel="icon" type="image/png" href="images/squirrel.png">
		<title>NiDB - Squirrel Bros</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
?>

	<div>
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">Squirrel Bros.</h1>
			</div>
			<div class="right aligned column" style="padding-top:1.5em">
				<div class="ui label">Score <span id="marScore">0</span></div>
				<div class="ui label">Acorns <span id="marCoins">0</span></div>
				<div class="ui label">Lives <span id="marLives">3</span></div>
				<div class="ui label">Time <span id="marTime">400</span></div>
			</div>
		</div>

		<div style="text-align:center">
			<canvas id="marCanvas" width="768" height="672"
			        style="background:#5c94fc; border:2px solid #444; border-radius:6px; max-width:100%; image-rendering:pixelated; touch-action:none"></canvas>
			<div style="margin-top:8px; color:#666; font-size:0.9em">
				<b>A</b> / <b>&larr;</b> left &nbsp;&bull;&nbsp;
				<b>D</b> / <b>&rarr;</b> right &nbsp;&bull;&nbsp;
				<b>Space</b> / <b>W</b> / <b>&uarr;</b> jump (hold to jump higher) &nbsp;&bull;&nbsp;
				<b>Shift</b> run &nbsp;&bull;&nbsp;
				<b>Enter</b> restart
			</div>
			<div style="margin-top:10px">
				<div class="ui toggle checkbox">
					<input type="checkbox" id="marMusicToggle">
					<label>&#9834; Music</label>
				</div>
			</div>
		</div>
	</div>

	<script>
	(function() {

		const canvas = document.getElementById('marCanvas');
		const ctx = canvas.getContext('2d');

		/* 8-bit presentation: render at the NES playfield size and scale up with no smoothing */
		const SCALE = 3;
		const VW = canvas.width / SCALE;      /* 256 */
		const VH = canvas.height / SCALE;     /* 224 */
		const T = 16;                         /* tile size */
		ctx.imageSmoothingEnabled = false;

		/* ---------------- movement tuning ----------------
		   Frame-based (assumes ~60fps), like the other games on this site. The numbers below decide
		   what the level geometry can require, so they are grouped here:
		     max jump height  = JUMP_V^2 / (2*G_HOLD)  ~= 79px  (a shade under 5 tiles)
		     short hop height = JUMP_V^2 / (2*G_RISE)  ~= 33px  (about 2 tiles)
		     jump distance    ~= airtime * speed       ~= 97px walking, 143px running */
		const JUMP_V   = -6.4;   /* upward impulse */
		const G_HOLD   = 0.26;   /* gravity while rising and holding jump */
		const G_RISE   = 0.62;   /* gravity while rising after releasing jump (gives the short hop) */
		const G_FALL   = 0.50;   /* gravity while falling */
		const MAX_FALL = 8.0;
		const WALK_MAX = 2.3;
		const RUN_MAX  = 3.4;

		/* ---------------- palette ---------------- */
		const PAL = {
			D: '#3b2314',   /* dark brown outline */
			B: '#a4632a',   /* squirrel fur */
			b: '#7d4a1e',   /* squirrel fur shadow */
			L: '#e8c39a',   /* belly / inner ear */
			W: '#ffffff',
			K: '#000000',
			A: '#8b5a2b',   /* acorn body */
			T: '#5c3317',   /* acorn cap */
			Y: '#fbd000',   /* coin / block yellow */
			y: '#c88f00',   /* coin shadow */
			O: '#e39d25',   /* brick light */
			R: '#a4400c',   /* brick dark */
			G: '#00a800',   /* pipe dark */
			g: '#58d854',   /* pipe light */
			X: '#c84c0c',   /* ground */
			x: '#883000',   /* ground shadow */
			S: '#ffffff'
		};

		/* ---------------- sprites ----------------
		   Each sprite is an array of equal-length strings, one character per pixel, keyed to PAL
		   above. '.' is transparent. These are plain data - edit the grids to redraw a sprite. */

		/* Squirrel, facing right, 20x16. Wider than a tile so the tail has room to be properly
		   bushy: columns 0-7 are the tail, which hangs out BEHIND the collision box, and columns
		   9-19 are the squirrel itself. The head sits forward of the body so there is a visible
		   step at the back of the neck rather than one continuous blob. */
		const SQ_IDLE = [
			'.............DD.DD..',
			'....DDD.....DLD.DLD.',
			'...DBBBD....DBBBBBD.',
			'..DBBBBD...DBWKBBBD.',
			'.DBBBbBD...DBBBBBBLD',
			'DBBBBbBD...DBBBBBLKD',
			'DBBBBbBD....DBBBBBD.',
			'DBBBBbBD.DBBBBBBBD..',
			'DBBBBbBD.DBBLLLLBD..',
			'DBBBBbD..DBLLLLLBD..',
			'.DBBBBD..DBLLLLLBD..',
			'..DBBBD..DBBBBBBBD..',
			'...DBBD..DBBBBBBBD..',
			'....DD....DBD.DBD...',
			'.........DBBD.DBBD..',
			'.........DKKD.DKKD..'
		];

		/* Run and jump frames reuse everything above the knees, so the body cannot drift between
		   poses - only the three leg rows are redefined. */
		const SQ_RUN1 = SQ_IDLE.slice(0, 13).concat([
			'....DD....DBD.DBD...',
			'.........DBBD..DBBD.',
			'.........DKKD.......'
		]);
		const SQ_RUN2 = SQ_IDLE.slice(0, 13).concat([
			'....DD....DBD.DBD...',
			'........DBBD...DBBD.',
			'...............DKKD.'
		]);
		/* jump: legs tucked together */
		const SQ_JUMP = SQ_IDLE.slice(0, 13).concat([
			'....DD....DBBBBD....',
			'.........DBBD.DBBD..',
			'....................'
		]);

		/* Only columns SPR_BODY_LEFT..SPR_BODY_RIGHT of the squirrel sprites are the squirrel; the
		   rest is tail overhang. These line the body up with the 12px collision box, and keep it
		   lined up when the sprite is mirrored. */
		const SPR_BODY_LEFT = 9, SPR_BODY_RIGHT = 19;

		/* acorn enemy */
		const ACORN = [
			'................',
			'................',
			'...TTTTTTTT.....',
			'..TTTTTTTTTT....',
			'..TTTTTTTTTT....',
			'...TTTTTTTT.....',
			'...AAAAAAAA.....',
			'..AAWKAAWKAA....',
			'..AAAAAAAAAA....',
			'..AAAAAAAAAA....',
			'...AAAAAAAA.....',
			'....AAAAAA......',
			'.....AAAA.......',
			'...DD....DD.....',
			'..DKKD..DKKD....',
			'...DD....DD.....'
		];
		const ACORN_FLAT = [
			'................','................','................','................',
			'................','................','................','................',
			'................','................','...TTTTTTTT.....','..TTTTTTTTTT....',
			'..AAAAAAAAAA....','..AAAAAAAAAA....','...AAAAAAAA.....','................'
		];

		/* draws a squirrel sprite so that its body - not the whole bitmap - sits on (px,py) */
		function drawSquirrel(sp, px, py, flip) {
			const w = sp[0].length;
			const dx = flip ? (px - (w - 1 - SPR_BODY_RIGHT)) : (px - SPR_BODY_LEFT);
			drawSprite(sp, dx, py, flip);
		}

		function drawSprite(sp, px, py, flip) {
			const w = sp[0].length;
			for (let y = 0; y < sp.length; y++) {
				const rowstr = sp[y];
				for (let x = 0; x < w; x++) {
					const c = rowstr[x];
					if (c === '.' || c === ' ') continue;
					const col = PAL[c];
					if (!col) continue;
					ctx.fillStyle = col;
					ctx.fillRect(px + (flip ? (w - 1 - x) : x), py + y, 1, 1);
				}
			}
		}

		/* ---------------- level ----------------
		   Built by stamping features onto an empty grid rather than by hand-aligning long strings,
		   so positions are explicit and easy to change.
		     ' ' air   'X' ground   'B' brick   '?' acorn block   'u' spent block
		     'o' collectible acorn   'P' pipe   'F' flag pole   '=' flag base  */
		const LEVEL_W = 214, LEVEL_H = 14;
		const GROUND = 12;                    /* first ground row; rows 12-13 are ground */
		let level = [];

		function put(x, y, ch) {
			if ((y >= 0) && (y < LEVEL_H) && (x >= 0) && (x < LEVEL_W)) level[y][x] = ch;
		}
		function stamp(x, y, w, h, ch) {
			for (let j = y; j < y + h; j++)
				for (let i = x; i < x + w; i++) put(i, j, ch);
		}
		function rowOf(y, x1, x2, ch) {
			for (let i = x1; i <= x2; i++) put(i, y, ch);
		}

		/* pits the player must jump. Kept narrow enough to clear with a running jump. */
		const PITS = [[69, 4], [108, 4], [150, 3], [178, 4]];
		/* enemy spawn columns */
		const ENEMY_COLS = [22, 40, 41, 57, 80, 95, 96, 121, 133, 134, 160, 168, 190];

		function buildLevel() {
			level = [];
			for (let y = 0; y < LEVEL_H; y++) level.push(new Array(LEVEL_W).fill(' '));

			/* ground, then knock out the pits */
			stamp(0, GROUND, LEVEL_W, LEVEL_H - GROUND, 'X');
			PITS.forEach(function(p) { stamp(p[0], GROUND, p[1], LEVEL_H - GROUND, ' '); });

			/* opening: a lone acorn block to teach the head-bump */
			put(16, 8, '?');

			/* first brick run with acorn blocks mixed in */
			rowOf(8, 26, 30, 'B');
			put(28, 8, '?');
			rowOf(4, 27, 29, 'B');
			put(28, 4, '?');

			/* floating acorns on the approach to the first pit */
			rowOf(8, 64, 67, 'o');

			/* pipes */
			stamp(34, 10, 2, 4, 'P');
			stamp(50, 9, 2, 5, 'P');
			stamp(140, 10, 2, 4, 'P');

			/* mid-level platforms */
			rowOf(8, 84, 90, 'B');
			put(86, 8, '?');
			put(88, 8, '?');
			rowOf(8, 100, 104, 'B');
			rowOf(7, 100, 104, 'o');

			/* stepped bricks */
			rowOf(9, 116, 118, 'B');
			rowOf(7, 120, 122, 'B');
			rowOf(5, 124, 126, 'B');
			rowOf(4, 124, 126, 'o');

			/* a long brick ceiling with acorns beneath */
			rowOf(6, 145, 149, 'B');
			put(147, 6, '?');
			rowOf(8, 156, 158, 'B');
			rowOf(7, 156, 158, 'o');

			/* acorns strung over the last two pits */
			rowOf(8, 173, 176, 'o');
			rowOf(8, 182, 185, 'o');

			/* closing staircase */
			for (let s = 0; s < 6; s++)
				stamp(194 + s, GROUND - 1 - s, 1, s + 1, 'X');

			/* Flag pole and base. The pole must reach all the way down to the row the player walks
			   on (GROUND-1), otherwise they run straight past it without ever touching it. */
			for (let y = 4; y <= GROUND - 1; y++) put(206, y, 'F');
			put(205, GROUND - 1, '=');
			put(207, GROUND - 1, '=');
		}

		function tileAt(tx, ty) {
			if (ty < 0) return ' ';
			if (ty >= LEVEL_H) return ' ';
			if (tx < 0) return 'X';                 /* invisible wall at the far left */
			if (tx >= LEVEL_W) return ' ';
			return level[ty][tx];
		}
		function isSolid(ch) {
			return (ch === 'X') || (ch === 'B') || (ch === '?') || (ch === 'u') || (ch === 'P');
		}

		/* ---------------- state ---------------- */
		let player, enemies, cam, score, coins, lives, timeLeft, timeTick, frame;
		let gameOver, won, dying, deathTimer, levelDone, doneTimer, bumps;

		function resetPlayer() {
			player = { x: 3 * T, y: (GROUND - 1) * T, w: 12, h: 16, vx: 0, vy: 0,
			           grounded: false, face: 1, hitWall: false, jumpHeld: false };
		}

		function startLevel() {
			buildLevel();
			resetPlayer();
			enemies = ENEMY_COLS.map(function(cx) {
				return { x: cx * T + 2, y: (GROUND - 1) * T, w: 12, h: 16,
				         vx: -0.55, vy: 0, grounded: false, alive: true, flat: 0 };
			});
			cam = 0;
			timeLeft = 400;
			timeTick = 0;
			frame = 0;
			dying = false; deathTimer = 0;
			levelDone = false; doneTimer = 0;
			bumps = [];
			won = false;
			gameOver = false;
		}

		function startGame() {
			score = 0; coins = 0; lives = 3;
			startLevel();
		}

		function die() {
			if (dying) return;
			dying = true;
			deathTimer = 0;
			player.vy = -5.5;
		}

		/* ---------------- collision ---------------- */
		function collideX(e) {
			e.hitWall = false;
			e.x += e.vx;
			const top = Math.floor(e.y / T);
			const bot = Math.floor((e.y + e.h - 1) / T);
			if (e.vx > 0) {
				const tx = Math.floor((e.x + e.w - 1) / T);
				for (let ty = top; ty <= bot; ty++) {
					if (isSolid(tileAt(tx, ty))) { e.x = tx * T - e.w; e.vx = 0; e.hitWall = true; break; }
				}
			}
			else if (e.vx < 0) {
				const tx = Math.floor(e.x / T);
				for (let ty = top; ty <= bot; ty++) {
					if (isSolid(tileAt(tx, ty))) { e.x = (tx + 1) * T; e.vx = 0; e.hitWall = true; break; }
				}
			}
		}

		function collideY(e, isPlayer) {
			e.y += e.vy;
			const left = Math.floor(e.x / T);
			const right = Math.floor((e.x + e.w - 1) / T);
			e.grounded = false;
			if (e.vy > 0) {
				/* Probe the pixel row immediately BELOW the last one the entity occupies, not the
				   last occupied row itself. Resting exactly on a surface puts the bottom edge on
				   the tile boundary, and probing "e.y + e.h - 1" reports air there - so gravity
				   sinks the entity a fraction of a pixel each frame and only snaps back once it
				   crosses into the tile. That never settles: the entity oscillates by 1px and
				   'grounded' flips every frame, which also flickers the sprite between its
				   standing and jumping poses. */
				const ty = Math.floor((e.y + e.h) / T);
				for (let tx = left; tx <= right; tx++) {
					if (isSolid(tileAt(tx, ty))) { e.y = ty * T - e.h; e.vy = 0; e.grounded = true; break; }
				}
			}
			else if (e.vy < 0) {
				const ty = Math.floor(e.y / T);
				for (let tx = left; tx <= right; tx++) {
					if (isSolid(tileAt(tx, ty))) {
						e.y = (ty + 1) * T; e.vy = 0;
						if (isPlayer) bumpBlock(tx, ty);
						break;
					}
				}
			}
		}

		function bumpBlock(tx, ty) {
			const ch = tileAt(tx, ty);
			if (ch === '?') {
				level[ty][tx] = 'u';
				coins++; score += 200;
				bumps.push({ x: tx, y: ty, t: 0 });
			}
			else if (ch === 'B') {
				bumps.push({ x: tx, y: ty, t: 0 });
			}
		}

		/* ---------------- update ---------------- */
		const keys = {};

		function update() {
			frame++;

			if (gameOver) return;

			/* level complete: let the squirrel walk a moment, then stop */
			if (levelDone) {
				doneTimer++;
				if (doneTimer > 150) { gameOver = true; won = true; }
				player.vx = 1.2;
				collideX(player);
				player.vy += 0.38;
				if (player.vy > 7) player.vy = 7;
				collideY(player, true);
				return;
			}

			/* death animation: fall off the screen, then respawn or end */
			if (dying) {
				deathTimer++;
				player.vy += 0.35;
				player.y += player.vy;
				if (deathTimer > 110) {
					lives--;
					if (lives <= 0) { gameOver = true; won = false; }
					else startLevel();
				}
				return;
			}

			/* countdown */
			timeTick++;
			if (timeTick >= 24) { timeTick = 0; timeLeft--; if (timeLeft <= 0) { timeLeft = 0; die(); return; } }

			/* ---- horizontal ---- */
			const running = (keys['shift'] === true);
			const maxrun = running ? RUN_MAX : WALK_MAX;
			const accel = 0.22, friction = 0.18;
			let dir = 0;
			if (keys['a'] || keys['arrowleft']) dir -= 1;
			if (keys['d'] || keys['arrowright']) dir += 1;

			if (dir !== 0) {
				player.vx += accel * dir;
				player.face = dir;
				if (player.vx > maxrun) player.vx = maxrun;
				if (player.vx < -maxrun) player.vx = -maxrun;
			}
			else {
				if (player.vx > friction) player.vx -= friction;
				else if (player.vx < -friction) player.vx += friction;
				else player.vx = 0;
			}
			collideX(player);

			/* ---- vertical ---- */
			const jumpKey = (keys[' '] || keys['w'] || keys['arrowup']);
			if (jumpKey && player.grounded && !player.jumpHeld) {
				player.vy = JUMP_V;
				player.jumpHeld = true;
			}
			if (!jumpKey) player.jumpHeld = false;

			/* holding jump while rising gives a floatier, higher jump (classic feel) */
			const gravity = (jumpKey && (player.vy < 0)) ? G_HOLD : ((player.vy < 0) ? G_RISE : G_FALL);
			player.vy += gravity;
			if (player.vy > MAX_FALL) player.vy = MAX_FALL;
			collideY(player, true);

			/* fell in a pit */
			if (player.y > LEVEL_H * T + 32) { die(); return; }

			/* ---- collectible acorns ---- */
			const cl = Math.floor(player.x / T), cr = Math.floor((player.x + player.w - 1) / T);
			const ct = Math.floor(player.y / T), cb = Math.floor((player.y + player.h - 1) / T);
			for (let ty = ct; ty <= cb; ty++) {
				for (let tx = cl; tx <= cr; tx++) {
					if (tileAt(tx, ty) === 'o') { level[ty][tx] = ' '; coins++; score += 100; }
					else if (tileAt(tx, ty) === 'F') { levelDone = true; score += 1000 + timeLeft * 10; }
				}
			}

			/* ---- enemies ---- */
			enemies.forEach(function(en) {
				if (!en.alive) { if (en.flat > 0) en.flat--; return; }

				/* only run enemies that are near the camera, like the original */
				if ((en.x < cam - 32) || (en.x > cam + VW + 96)) return;

				collideX(en);
				if (en.hitWall) en.vx = -en.vx;

				en.vy += 0.40;
				if (en.vy > 7) en.vy = 7;
				collideY(en, false);

				/* turn around at a ledge so they do not walk into pits */
				if (en.grounded) {
					const aheadX = (en.vx > 0) ? Math.floor((en.x + en.w + 1) / T) : Math.floor((en.x - 1) / T);
					const belowY = Math.floor((en.y + en.h + 1) / T);
					if (!isSolid(tileAt(aheadX, belowY))) en.vx = -en.vx;
				}

				if (en.y > LEVEL_H * T + 32) { en.alive = false; return; }

				/* ---- player vs enemy ---- */
				const hit = (player.x < en.x + en.w) && (player.x + player.w > en.x) &&
				            (player.y < en.y + en.h) && (player.y + player.h > en.y);
				if (hit) {
					const falling = (player.vy > 0);
					const fromAbove = ((player.y + player.h) - en.y) < 12;
					if (falling && fromAbove) {
						en.alive = false;
						en.flat = 30;
						score += 100;
						player.vy = keys[' '] || keys['w'] || keys['arrowup'] ? -6.2 : -4.2;
					}
					else {
						die();
					}
				}
			});

			/* ---- block bump animation ---- */
			bumps = bumps.filter(function(b) { b.t++; return b.t < 12; });

			/* ---- camera ---- */
			const target = player.x - VW / 3;
			if (target > cam) cam = target;                       /* never scrolls back, like SMB */
			if (cam < 0) cam = 0;
			const maxcam = LEVEL_W * T - VW;
			if (cam > maxcam) cam = maxcam;

			/* keep the player from walking off the left edge of the screen */
			if (player.x < cam) { player.x = cam; player.vx = 0; }
		}

		/* ---------------- draw ---------------- */
		function drawBrick(px, py) {
			ctx.fillStyle = PAL.R; ctx.fillRect(px, py, T, T);
			ctx.fillStyle = PAL.O;
			/* two courses of offset bricks */
			ctx.fillRect(px + 1, py + 1, 6, 6);
			ctx.fillRect(px + 9, py + 1, 6, 6);
			ctx.fillRect(px + 1, py + 9, 14, 6);
		}
		function drawGround(px, py) {
			ctx.fillStyle = PAL.X; ctx.fillRect(px, py, T, T);
			ctx.fillStyle = PAL.x;
			ctx.fillRect(px, py, T, 2);
			ctx.fillRect(px + 6, py + 4, 4, 4);
			ctx.fillRect(px, py + 10, 5, 4);
			ctx.fillRect(px + 11, py + 10, 5, 4);
		}
		function drawQBlock(px, py, lit) {
			ctx.fillStyle = lit ? PAL.Y : PAL.y; ctx.fillRect(px, py, T, T);
			ctx.fillStyle = PAL.D;
			ctx.fillRect(px, py, T, 1); ctx.fillRect(px, py + T - 1, T, 1);
			ctx.fillRect(px, py, 1, T); ctx.fillRect(px + T - 1, py, 1, T);
			/* a chunky question mark */
			ctx.fillRect(px + 5, py + 4, 6, 2);
			ctx.fillRect(px + 9, py + 6, 2, 2);
			ctx.fillRect(px + 7, py + 8, 3, 2);
			ctx.fillRect(px + 7, py + 11, 2, 2);
		}
		function drawUsed(px, py) {
			ctx.fillStyle = PAL.T; ctx.fillRect(px, py, T, T);
			ctx.fillStyle = PAL.D;
			ctx.fillRect(px, py, T, 1); ctx.fillRect(px, py + T - 1, T, 1);
			ctx.fillRect(px, py, 1, T); ctx.fillRect(px + T - 1, py, 1, T);
		}
		function drawPipe(px, py, tx, ty) {
			const isTop = !isSolid(tileAt(tx, ty - 1));
			ctx.fillStyle = PAL.G; ctx.fillRect(px, py, T, T);
			ctx.fillStyle = PAL.g; ctx.fillRect(px + 2, py, 5, T);
			if (isTop) { ctx.fillStyle = PAL.D; ctx.fillRect(px, py, T, 1); }
		}
		function drawAcornPickup(px, py, bob) {
			ctx.fillStyle = PAL.T; ctx.fillRect(px + 4, py + 2 + bob, 8, 4);
			ctx.fillStyle = PAL.A; ctx.fillRect(px + 5, py + 6 + bob, 6, 7);
			ctx.fillStyle = PAL.Y; ctx.fillRect(px + 6, py + 7 + bob, 2, 3);
		}

		function draw() {
			/* sky */
			ctx.setTransform(SCALE, 0, 0, SCALE, 0, 0);
			ctx.fillStyle = '#5c94fc';
			ctx.fillRect(0, 0, VW, VH);

			/* parallax hills and clouds */
			const bx = -(cam * 0.4);
			ctx.fillStyle = '#00a800';
			for (let i = 0; i < 14; i++) {
				const hx = bx + i * 180;
				if ((hx > -80) && (hx < VW + 80)) {
					ctx.beginPath();
					ctx.moveTo(hx, GROUND * T);
					ctx.lineTo(hx + 26, GROUND * T - 26);
					ctx.lineTo(hx + 52, GROUND * T);
					ctx.closePath();
					ctx.fill();
				}
			}
			ctx.fillStyle = '#ffffff';
			for (let i = 0; i < 20; i++) {
				const cx2 = -(cam * 0.25) + i * 140 + 20;
				const cy2 = 24 + ((i % 3) * 18);
				if ((cx2 > -50) && (cx2 < VW + 50)) {
					ctx.fillRect(cx2, cy2, 26, 8);
					ctx.fillRect(cx2 + 5, cy2 - 5, 16, 8);
				}
			}

			/* shift into world space, snapped to whole pixels so nothing shimmers */
			const camx = Math.round(cam);
			ctx.translate(-camx, 0);

			/* tiles in view */
			const tx0 = Math.max(0, Math.floor(camx / T) - 1);
			const tx1 = Math.min(LEVEL_W - 1, Math.floor((camx + VW) / T) + 1);
			const bob = [0, 0, 1, 1, 2, 2, 1, 1][Math.floor(frame / 6) % 8];

			for (let ty = 0; ty < LEVEL_H; ty++) {
				for (let tx = tx0; tx <= tx1; tx++) {
					const ch = level[ty][tx];
					if (ch === ' ') continue;
					let px = tx * T, py = ty * T;

					/* nudge a bumped block upward */
					for (let i = 0; i < bumps.length; i++) {
						if ((bumps[i].x === tx) && (bumps[i].y === ty)) {
							py -= (bumps[i].t < 6) ? bumps[i].t : (12 - bumps[i].t);
							break;
						}
					}

					if (ch === 'X') drawGround(px, py);
					else if (ch === 'B') drawBrick(px, py);
					else if (ch === '?') drawQBlock(px, py, (Math.floor(frame / 10) % 4) !== 3);
					else if (ch === 'u') drawUsed(px, py);
					else if (ch === 'P') drawPipe(px, py, tx, ty);
					else if (ch === 'o') drawAcornPickup(px, py, bob);
					else if (ch === 'F') {
						ctx.fillStyle = '#e8e8e8'; ctx.fillRect(px + 7, py, 2, T);
						if (ty === 4) {
							ctx.fillStyle = '#00a800';
							ctx.fillRect(px + 1, py + 1, 6, 6);
						}
					}
					else if (ch === '=') { ctx.fillStyle = PAL.G; ctx.fillRect(px, py, T, T); }
				}
			}

			/* enemies */
			enemies.forEach(function(en) {
				if ((en.x < camx - 32) || (en.x > camx + VW + 32)) return;
				if (en.alive) drawSprite(ACORN, Math.round(en.x) - 2, Math.round(en.y), (en.vx > 0));
				else if (en.flat > 0) drawSprite(ACORN_FLAT, Math.round(en.x) - 2, Math.round(en.y), false);
			});

			/* player */
			let sp = SQ_IDLE;
			if (dying) sp = SQ_JUMP;
			else if (!player.grounded) sp = SQ_JUMP;
			else if (Math.abs(player.vx) > 0.2) sp = ((Math.floor(frame / 5) % 2) === 0) ? SQ_RUN1 : SQ_RUN2;
			drawSquirrel(sp, Math.round(player.x), Math.round(player.y), (player.face < 0));

			/* back to screen space for overlays */
			ctx.setTransform(SCALE, 0, 0, SCALE, 0, 0);

			if (gameOver) {
				ctx.fillStyle = 'rgba(0,0,0,0.65)';
				ctx.fillRect(0, VH / 2 - 30, VW, 60);
				ctx.textAlign = 'center';
				ctx.fillStyle = won ? '#fbd000' : '#ffffff';
				ctx.font = 'bold 16px monospace';
				ctx.fillText(won ? 'LEVEL CLEAR!' : 'GAME OVER', VW / 2, VH / 2 - 8);
				ctx.fillStyle = '#cccccc';
				ctx.font = '10px monospace';
				ctx.fillText('Score ' + score + '  -  press Enter to play again', VW / 2, VH / 2 + 14);
			}

			/* HUD */
			document.getElementById('marScore').textContent = score;
			document.getElementById('marCoins').textContent = coins;
			document.getElementById('marLives').textContent = (lives > 0) ? lives : 0;
			document.getElementById('marTime').textContent = timeLeft;
		}

		function loop() {
			update();
			draw();
			requestAnimationFrame(loop);
		}

		/* ---- background music ---------------------------------------------------------
		   An ORIGINAL upbeat chiptune, synthesized with the Web Audio API. It is deliberately
		   not the Super Mario Bros. theme (Koji Kondo, 1985), which is copyrighted - the melody
		   below is our own, in the general cheerful-overworld style, so the page stays both
		   self-contained and clear of anyone else's composition. Same engine as tetris.php. */
		let audioCtx = null, musicGain = null, musicOn = false, musicStarted = false;
		let melIndex = 0, melNextTime = 0, bassIndex = 0, bassNextTime = 0, schedulerTimer = null;
		const BEAT = 0.16;   /* seconds per quarter note - lively */
		const NOTE = {
			'rest': 0,
			'C4': 261.63, 'D4': 293.66, 'E4': 329.63, 'F4': 349.23, 'G4': 392.00, 'A4': 440.00, 'B4': 493.88,
			'C5': 523.25, 'D5': 587.33, 'E5': 659.25, 'F5': 698.46, 'G5': 783.99, 'A5': 880.00, 'B5': 987.77,
			'C6': 1046.50
		};
		/* [note, beats] - an original loop: two bright phrases, then a contrasting one, resolving to C */
		const MELODY = [
			['C5',0.5],['D5',0.5],['E5',0.5],['G5',0.5],['E5',0.5],['C5',0.5],['rest',1],
			['D5',0.5],['E5',0.5],['F5',0.5],['A5',0.5],['F5',0.5],['D5',0.5],['rest',1],
			['E5',0.5],['G5',0.5],['C6',1],['B5',0.5],['G5',0.5],['E5',1],
			['D5',1],['G4',0.5],['B4',0.5],['D5',1],['rest',1],
			['A4',0.5],['C5',0.5],['E5',0.5],['A5',0.5],['G5',0.5],['E5',0.5],['C5',0.5],['A4',0.5],
			['F4',0.5],['A4',0.5],['D5',0.5],['F5',0.5],['E5',1],['rest',1],
			['G4',0.5],['B4',0.5],['D5',0.5],['G5',0.5],['F5',0.5],['D5',0.5],['B4',0.5],['G4',0.5],
			['C5',2],['rest',2]
		];
		/* a simple root-note bass, one voice below the melody, following the chord changes */
		const BASS = [
			['C3',2],['C3',2], ['D3',2],['D3',2],
			['C3',2],['E3',2], ['G3',2],['G3',2],
			['A3',2],['A3',2], ['F3',2],['F3',2],
			['G3',2],['G3',2], ['C3',2],['C3',2]
		];
		const BASSNOTE = { 'C3': 130.81, 'D3': 146.83, 'E3': 164.81, 'F3': 174.61, 'G3': 196.00, 'A3': 220.00 };

		function playTone(freq, time, dur, wave, peak, dest) {
			const osc = audioCtx.createOscillator();
			const g = audioCtx.createGain();
			osc.type = wave;
			osc.frequency.value = freq;
			/* short attack + decay so notes stay distinct instead of a continuous drone */
			g.gain.setValueAtTime(0.0001, time);
			g.gain.exponentialRampToValueAtTime(peak, time + 0.01);
			g.gain.exponentialRampToValueAtTime(0.0001, time + Math.max(0.05, dur * 0.9));
			osc.connect(g);
			g.connect(dest);
			osc.start(time);
			osc.stop(time + dur);
		}

		/* lookahead scheduler: queue melody + bass slightly ahead of the audio clock and loop */
		function scheduler() {
			if (!audioCtx) return;
			const horizon = audioCtx.currentTime + 0.2;
			while (melNextTime < horizon) {
				const m = MELODY[melIndex];
				const dur = m[1] * BEAT;
				if (m[0] !== 'rest') playTone(NOTE[m[0]], melNextTime, dur, 'square', 0.5, musicGain);
				melNextTime += dur;
				melIndex = (melIndex + 1) % MELODY.length;
			}
			while (bassNextTime < horizon) {
				const b = BASS[bassIndex];
				const dur = b[1] * BEAT;
				if (b[0] !== 'rest') playTone(BASSNOTE[b[0]], bassNextTime, dur, 'triangle', 0.5, musicGain);
				bassNextTime += dur;
				bassIndex = (bassIndex + 1) % BASS.length;
			}
		}

		function startMusic() {
			if (musicStarted) return;
			const AC = window.AudioContext || window.webkitAudioContext;
			if (!AC) return;
			audioCtx = new AC();
			if (audioCtx.state === 'suspended') audioCtx.resume();
			musicGain = audioCtx.createGain();
			musicGain.gain.value = musicOn ? 0.15 : 0;   /* soft background */
			musicGain.connect(audioCtx.destination);
			melIndex = 0; bassIndex = 0;
			melNextTime = bassNextTime = audioCtx.currentTime + 0.15;
			schedulerTimer = setInterval(scheduler, 25);
			musicStarted = true;
		}

		const musicToggle = document.getElementById('marMusicToggle');
		musicToggle.addEventListener('change', function() {
			musicOn = this.checked;
			if (musicOn && !musicStarted) {
				startMusic();   /* the toggle is a user gesture, so audio may start here */
			} else if (audioCtx) {
				if (audioCtx.state === 'suspended') audioCtx.resume();
				musicGain.gain.value = musicOn ? 0.15 : 0;
			}
			this.blur();   /* so Space/Enter don't re-toggle the focused control */
		});
		if (window.jQuery) jQuery(musicToggle).parent().checkbox();

		/* Browser autoplay policy blocks audio until the user interacts with the page, so we
		   can't play on load even though the toggle defaults to On. Kick playback off on the
		   first pointer gesture (keydown is already covered below). */
		function bootstrapAudioOnce() {
			if (musicOn && !musicStarted) startMusic();
			window.removeEventListener('pointerdown', bootstrapAudioOnce);
		}
		window.addEventListener('pointerdown', bootstrapAudioOnce);

		/* ---- input ---- */
		window.addEventListener('keydown', function(e) {
			const k = e.key.toLowerCase();
			/* browsers only allow audio to start after a user gesture */
			if (musicOn) startMusic();
			if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
			/* keep the page from scrolling on the game keys */
			if ([' ', 'arrowup', 'arrowdown', 'arrowleft', 'arrowright'].includes(k))
				e.preventDefault();

			if (k === 'enter') { if (gameOver) startGame(); return; }
			if (k === 'shift') { keys['shift'] = true; return; }
			keys[k] = true;
		});
		window.addEventListener('keyup', function(e) {
			const k = e.key.toLowerCase();
			if (k === 'shift') { keys['shift'] = false; return; }
			keys[k] = false;
		});

		startGame();
		loop();
	})();
	</script>

<? include("footer.php") ?>
