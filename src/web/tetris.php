<?
 // ------------------------------------------------------------------------------
 // NiDB tetris.php
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
		<title>NiDB - Tetris</title>
	</head>

<body>
	<div id="wrapper">
<?
	require "functions.php";
	require "includes_php.php";
	require "includes_html.php";
	require "menu.php";
	require "nidbapi.php";
?>

	<div>
		<div class="ui two column grid">
			<div class="column">
				<h1 class="ui header">Tetris</h1>
			</div>
			<div class="right aligned column" style="padding-top:1.5em">
				<div class="ui label">Score <span id="tetScore">0</span></div>
				<div class="ui label">Lines <span id="tetLines">0</span></div>
				<div class="ui label">Level <span id="tetLevel">1</span></div>
			</div>
		</div>

		<div style="text-align:center">
			<canvas id="tetCanvas" width="300" height="600"
			        style="background:#000; border:2px solid #444; border-radius:6px; max-width:100%; touch-action:none"></canvas>
			<div style="margin-top:8px; color:#666; font-size:0.9em">
				<b>A</b> / <b>&larr;</b> move left &nbsp;&bull;&nbsp;
				<b>D</b> / <b>&rarr;</b> move right &nbsp;&bull;&nbsp;
				<b>S</b> / <b>&darr;</b> soft drop &nbsp;&bull;&nbsp;
				<b>W</b> / <b>&uarr;</b> rotate &nbsp;&bull;&nbsp;
				<b>Space</b> hard drop &nbsp;&bull;&nbsp;
				<b>Enter</b> restart
			</div>
			<div style="margin-top:8px">
				<div class="ui toggle checkbox">
					<input type="checkbox" id="tetMusicToggle" checked>
					<label>&#9834; Music</label>
				</div>
			</div>
		</div>
	</div>

	<script>
	(function() {
		const canvas = document.getElementById('tetCanvas');
		const ctx = canvas.getContext('2d');

		const scoreEl = document.getElementById('tetScore');
		const linesEl = document.getElementById('tetLines');
		const levelEl = document.getElementById('tetLevel');

		/* ---- board geometry ---- */
		const COLS = 10, ROWS = 20, CELL = 30;   /* 300 x 600 canvas */

		/* ---- tetromino definitions (spawn orientation) + colors ---- */
		const SHAPES = {
			I: { color: '#00bcd4', cells: [[0,1],[1,1],[2,1],[3,1]] },
			O: { color: '#ffd600', cells: [[1,0],[2,0],[1,1],[2,1]] },
			T: { color: '#ab47bc', cells: [[1,0],[0,1],[1,1],[2,1]] },
			S: { color: '#66bb6a', cells: [[1,0],[2,0],[0,1],[1,1]] },
			Z: { color: '#ef5350', cells: [[0,0],[1,0],[1,1],[2,1]] },
			J: { color: '#42a5f5', cells: [[0,0],[0,1],[1,1],[2,1]] },
			L: { color: '#ffa726', cells: [[2,0],[0,1],[1,1],[2,1]] }
		};
		const TYPES = Object.keys(SHAPES);

		/* points awarded for clearing 1..4 lines at level 1 (scaled by level) */
		const LINE_SCORES = [0, 100, 300, 500, 800];

		let board, current, score, lines, level, gameOver;
		let dropInterval, dropTimer, bag;
		const keys = {};

		function emptyBoard() {
			const b = [];
			for (let r = 0; r < ROWS; r++) b.push(new Array(COLS).fill(null));
			return b;
		}

		/* 7-bag randomizer: each piece appears once per bag for fair distribution */
		function nextType() {
			if (!bag || bag.length === 0) {
				bag = TYPES.slice();
				for (let i = bag.length - 1; i > 0; i--) {
					const j = Math.floor(Math.random() * (i + 1));
					[bag[i], bag[j]] = [bag[j], bag[i]];
				}
			}
			return bag.pop();
		}

		function spawnPiece() {
			const type = nextType();
			const def = SHAPES[type];
			const piece = {
				type: type,
				color: def.color,
				cells: def.cells.map(c => c.slice()),
				x: 3, y: 0
			};
			/* if the new piece immediately collides, it's game over */
			if (collides(piece, piece.cells, piece.x, piece.y)) {
				gameOver = true;
			}
			return piece;
		}

		function collides(piece, cells, ox, oy) {
			for (const [cx, cy] of cells) {
				const x = ox + cx, y = oy + cy;
				if (x < 0 || x >= COLS || y >= ROWS) return true;
				if (y >= 0 && board[y][x]) return true;
			}
			return false;
		}

		/* rotate clockwise within the piece's bounding box (O never rotates) */
		function rotatedCells(piece) {
			if (piece.type === 'O') return piece.cells.map(c => c.slice());
			/* I uses a 4x4 box, the rest a 3x3 box */
			const size = (piece.type === 'I') ? 3 : 2;
			return piece.cells.map(([cx, cy]) => [size - cy, cx]);
		}

		function rotate() {
			const rc = rotatedCells(current);
			/* simple wall-kick: try in place, then nudge left/right */
			for (const dx of [0, -1, 1, -2, 2]) {
				if (!collides(current, rc, current.x + dx, current.y)) {
					current.cells = rc;
					current.x += dx;
					return;
				}
			}
		}

		function move(dx) {
			if (!collides(current, current.cells, current.x + dx, current.y))
				current.x += dx;
		}

		/* move down one row; returns false if it couldn't (piece locked) */
		function softDrop() {
			if (!collides(current, current.cells, current.x, current.y + 1)) {
				current.y += 1;
				return true;
			}
			lockPiece();
			return false;
		}

		function hardDrop() {
			while (!collides(current, current.cells, current.x, current.y + 1))
				current.y += 1;
			lockPiece();
		}

		function lockPiece() {
			for (const [cx, cy] of current.cells) {
				const x = current.x + cx, y = current.y + cy;
				if (y >= 0) board[y][x] = current.color;
			}
			clearLines();
			current = spawnPiece();
		}

		function clearLines() {
			let cleared = 0;
			for (let r = ROWS - 1; r >= 0; r--) {
				if (board[r].every(c => c !== null)) {
					board.splice(r, 1);
					board.unshift(new Array(COLS).fill(null));
					cleared++;
					r++; /* re-check the same row index after the shift */
				}
			}
			if (cleared > 0) {
				lines += cleared;
				score += LINE_SCORES[cleared] * level;
				level = Math.floor(lines / 10) + 1;
				dropInterval = Math.max(80, 800 - (level - 1) * 70);
				updateHUD();
			}
		}

		function updateHUD() {
			scoreEl.textContent = score;
			linesEl.textContent = lines;
			levelEl.textContent = level;
		}

		function startGame() {
			board = emptyBoard();
			score = 0; lines = 0; level = 1;
			dropInterval = 800; dropTimer = 0;
			gameOver = false;
			bag = null;
			current = spawnPiece();
			updateHUD();
		}

		/* ---- drawing ---- */
		function drawCell(x, y, color) {
			ctx.fillStyle = color;
			ctx.fillRect(x * CELL, y * CELL, CELL, CELL);
			ctx.strokeStyle = 'rgba(0,0,0,0.35)';
			ctx.lineWidth = 2;
			ctx.strokeRect(x * CELL + 1, y * CELL + 1, CELL - 2, CELL - 2);
		}

		function draw() {
			ctx.clearRect(0, 0, canvas.width, canvas.height);

			/* faint grid */
			ctx.strokeStyle = 'rgba(255,255,255,0.05)';
			ctx.lineWidth = 1;
			for (let c = 1; c < COLS; c++) {
				ctx.beginPath(); ctx.moveTo(c * CELL, 0); ctx.lineTo(c * CELL, canvas.height); ctx.stroke();
			}
			for (let r = 1; r < ROWS; r++) {
				ctx.beginPath(); ctx.moveTo(0, r * CELL); ctx.lineTo(canvas.width, r * CELL); ctx.stroke();
			}

			/* settled blocks */
			for (let r = 0; r < ROWS; r++)
				for (let c = 0; c < COLS; c++)
					if (board[r][c]) drawCell(c, r, board[r][c]);

			if (!gameOver && current) {
				/* active piece */
				for (const [cx, cy] of current.cells) {
					if (current.y + cy >= 0) drawCell(current.x + cx, current.y + cy, current.color);
				}
			}

			if (gameOver) {
				ctx.fillStyle = 'rgba(0,0,0,0.7)';
				ctx.fillRect(0, canvas.height/2 - 60, canvas.width, 120);
				ctx.fillStyle = '#e53935';
				ctx.font = 'bold 34px arial';
				ctx.textAlign = 'center';
				ctx.fillText('GAME OVER', canvas.width/2, canvas.height/2 - 10);
				ctx.fillStyle = '#ccc';
				ctx.font = '16px arial';
				ctx.fillText('Score: ' + score + ' — press Enter', canvas.width/2, canvas.height/2 + 25);
			}
		}

		function loop(ts) {
			if (!loop.last) loop.last = ts;
			const dt = ts - loop.last;
			loop.last = ts;

			if (!gameOver) {
				dropTimer += dt;
				if (dropTimer >= dropInterval) {
					dropTimer = 0;
					softDrop();
				}
			}
			draw();
			requestAnimationFrame(loop);
		}

		/* ---- background music ---------------------------------------------------------
		   "Korobeiniki" is a 19th-century Russian folk song; the melody is public domain.
		   We synthesize it with the Web Audio API rather than embed any copyrighted
		   recording/arrangement, which also keeps the page fully self-contained. */
		let audioCtx = null, musicGain = null, musicOn = true, musicStarted = false;
		let noteIndex = 0, nextNoteTime = 0, schedulerTimer = null;
		const BEAT = 0.32;   /* seconds per quarter note */
		const NOTE = {
			'rest': 0,
			'G#4': 415.30, 'A4': 440.00, 'B4': 493.88,
			'C5': 523.25, 'D5': 587.33, 'E5': 659.25, 'F5': 698.46, 'G5': 783.99,
			'G#5': 830.61, 'A5': 880.00
		};
		/* [note, beats] — A-theme (two phrases) then the bridge, looped */
		const MELODY = [
			['E5',1],['B4',0.5],['C5',0.5],['D5',1],['C5',0.5],['B4',0.5],
			['A4',1],['A4',0.5],['C5',0.5],['E5',1],['D5',0.5],['C5',0.5],
			['B4',1.5],['C5',0.5],['D5',1],['E5',1],
			['C5',1],['A4',1],['A4',1],['rest',1],
			['rest',0.5],['D5',1],['F5',0.5],['A5',1],['G5',0.5],['F5',0.5],
			['E5',1.5],['C5',0.5],['E5',1],['D5',0.5],['C5',0.5],
			['B4',1],['B4',0.5],['C5',0.5],['D5',1],['E5',1],
			['C5',1],['A4',1],['A4',1],['rest',1],
			['E5',1],['C5',1],['D5',1],['B4',1],['C5',1],['A4',1],['G#4',1],['rest',1],
			['E5',1],['C5',1],['D5',1],['B4',1],['C5',0.5],['E5',0.5],['A5',1],['A5',1],
			['G#5',2],['rest',1]
		];

		function playNote(freq, time, dur) {
			const osc = audioCtx.createOscillator();
			const g = audioCtx.createGain();
			osc.type = 'triangle';
			osc.frequency.value = freq;
			/* short attack + decay so notes stay distinct instead of a continuous drone */
			g.gain.setValueAtTime(0.0001, time);
			g.gain.exponentialRampToValueAtTime(0.6, time + 0.01);
			g.gain.exponentialRampToValueAtTime(0.0001, time + Math.max(0.05, dur * 0.9));
			osc.connect(g);
			g.connect(musicGain);
			osc.start(time);
			osc.stop(time + dur);
		}

		/* lookahead scheduler: queue notes slightly ahead of the audio clock and loop */
		function scheduler() {
			if (!audioCtx) return;
			while (nextNoteTime < audioCtx.currentTime + 0.2) {
				const [n, beats] = MELODY[noteIndex];
				const dur = beats * BEAT;
				if (n !== 'rest') playNote(NOTE[n], nextNoteTime, dur);
				nextNoteTime += dur;
				noteIndex = (noteIndex + 1) % MELODY.length;
			}
		}

		function startMusic() {
			if (musicStarted) return;
			const AC = window.AudioContext || window.webkitAudioContext;
			if (!AC) return;
			audioCtx = new AC();
			if (audioCtx.state === 'suspended') audioCtx.resume();
			musicGain = audioCtx.createGain();
			musicGain.gain.value = musicOn ? 0.2 : 0;   /* soft background; triangle is quieter than square */
			musicGain.connect(audioCtx.destination);
			noteIndex = 0;
			nextNoteTime = audioCtx.currentTime + 0.15;
			schedulerTimer = setInterval(scheduler, 25);
			musicStarted = true;
		}

		const musicToggle = document.getElementById('tetMusicToggle');
		musicToggle.addEventListener('change', function() {
			musicOn = this.checked;
			if (musicOn && !musicStarted) {
				startMusic();   /* the toggle is a user gesture, so audio may start here */
			} else if (audioCtx) {
				if (audioCtx.state === 'suspended') audioCtx.resume();
				musicGain.gain.value = musicOn ? 0.2 : 0;
			}
			this.blur();   /* so Space/Enter don't re-toggle the focused control */
		});
		if (window.jQuery) jQuery(musicToggle).parent().checkbox();

		/* Browser autoplay policy blocks audio until the user interacts with the page,
		   so we can't play on load even though the toggle defaults to On. Kick playback
		   off on the first pointer gesture (keydown is already covered below). */
		function bootstrapAudioOnce() {
			if (musicOn && !musicStarted) startMusic();
			window.removeEventListener('pointerdown', bootstrapAudioOnce);
		}
		window.addEventListener('pointerdown', bootstrapAudioOnce);

		/* ---- input (fires once per keypress; no auto-repeat needed for a first cut) ---- */
		window.addEventListener('keydown', function(e) {
			const k = e.key.toLowerCase();
			/* browsers only allow audio to start after a user gesture */
			startMusic();
			if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
			if ([' ', 'arrowup', 'arrowdown', 'arrowleft', 'arrowright'].includes(k))
				e.preventDefault();

			if (k === 'enter') { if (gameOver) startGame(); return; }
			if (gameOver) return;

			if (k === 'a' || k === 'arrowleft')       move(-1);
			else if (k === 'd' || k === 'arrowright') move(1);
			else if (k === 's' || k === 'arrowdown')  { softDrop(); dropTimer = 0; }
			else if (k === 'w' || k === 'arrowup')    rotate();
			else if (k === ' ')                       hardDrop();
			keys[k] = true;
		});
		window.addEventListener('keyup', function(e) {
			keys[e.key.toLowerCase()] = false;
		});

		startGame();
		requestAnimationFrame(loop);
	})();
	</script>

<? include("footer.php") ?>
