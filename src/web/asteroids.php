<?
 // ------------------------------------------------------------------------------
 // NiDB asteroids.php
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
		<title>NiDB - Asteroids</title>
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
				<h1 class="ui header">Asteroids</h1>
			</div>
			<div class="right aligned column" style="padding-top:1.5em">
				<div class="ui label">Score <span id="astScore">0</span></div>
				<div class="ui label">Lives <span id="astLives">3</span></div>
			</div>
		</div>

		<div style="text-align:center">
			<canvas id="astCanvas" width="900" height="600"
			        style="background:#000; border:2px solid #444; border-radius:6px; max-width:100%; touch-action:none"></canvas>
			<div style="margin-top:8px; color:#666; font-size:0.9em">
				<b>A</b> / <b>&larr;</b> rotate counterclockwise &nbsp;&bull;&nbsp;
				<b>S</b> / <b>&rarr;</b> rotate clockwise &nbsp;&bull;&nbsp;
				<b>W</b> / <b>&uarr;</b> thrust &nbsp;&bull;&nbsp;
				<b>Space</b> fire &nbsp;&bull;&nbsp;
				<b>Enter</b> restart
			</div>
		</div>
	</div>

	<script>
	(function() {
		const canvas = document.getElementById('astCanvas');
		const ctx = canvas.getContext('2d');
		const W = canvas.width, H = canvas.height;

		const scoreEl = document.getElementById('astScore');
		const livesEl = document.getElementById('astLives');

		/* ---- tunables ---- */
		const SHIP_R          = 12;      /* ship "radius" for collisions/drawing */
		const SHIP_THRUST     = 0.12;    /* acceleration per frame while thrusting */
		const SHIP_TURN       = 0.06;    /* radians per frame */
		const FRICTION        = 0.995;   /* velocity decay per frame */
		const BULLET_SPEED    = 7;
		const BULLET_LIFE     = 60;      /* frames */
		const FIRE_COOLDOWN   = 10;      /* frames between shots */
		const START_ASTEROIDS = 5;
		const INVULN_FRAMES   = 120;     /* respawn grace period */

		const AST_SIZES = {
			3: { r: 44, score: 20, speed: 1.0 },   /* large */
			2: { r: 26, score: 50, speed: 1.6 },   /* medium */
			1: { r: 14, score: 100, speed: 2.4 }   /* small */
		};

		let ship, bullets, asteroids, score, lives, gameOver, won, fireTimer;
		const keys = {};

		function rand(min, max) { return Math.random() * (max - min) + min; }
		function wrap(o) {
			if (o.x < 0) o.x += W; else if (o.x > W) o.x -= W;
			if (o.y < 0) o.y += H; else if (o.y > H) o.y -= H;
		}

		function newShip() {
			return { x: W/2, y: H/2, vx: 0, vy: 0, angle: -Math.PI/2, invuln: INVULN_FRAMES };
		}

		function spawnAsteroid(size, x, y) {
			const cfg = AST_SIZES[size];
			const ang = rand(0, Math.PI * 2);
			const spd = rand(cfg.speed * 0.5, cfg.speed);
			/* irregular polygon offsets for a chunky asteroid look */
			const verts = Math.floor(rand(8, 12));
			const offs = [];
			for (let i = 0; i < verts; i++) offs.push(rand(0.75, 1.15));
			return {
				x: x, y: y, size: size, r: cfg.r,
				vx: Math.cos(ang) * spd, vy: Math.sin(ang) * spd,
				rot: rand(-0.03, 0.03), a: 0, offs: offs
			};
		}

		function startGame() {
			ship = newShip();
			bullets = [];
			asteroids = [];
			score = 0;
			lives = 3;
			gameOver = false;
			won = false;
			fireTimer = 0;
			/* spawn large asteroids away from the ship's center */
			for (let i = 0; i < START_ASTEROIDS; i++) {
				let x, y;
				do { x = rand(0, W); y = rand(0, H); }
				while (Math.hypot(x - W/2, y - H/2) < 150);
				asteroids.push(spawnAsteroid(3, x, y));
			}
			updateHUD();
		}

		function updateHUD() {
			scoreEl.textContent = score;
			livesEl.textContent = lives;
		}

		function fire() {
			if (fireTimer > 0) return;
			bullets.push({
				x: ship.x + Math.cos(ship.angle) * SHIP_R,
				y: ship.y + Math.sin(ship.angle) * SHIP_R,
				vx: Math.cos(ship.angle) * BULLET_SPEED + ship.vx,
				vy: Math.sin(ship.angle) * BULLET_SPEED + ship.vy,
				life: BULLET_LIFE
			});
			fireTimer = FIRE_COOLDOWN;
		}

		function splitAsteroid(idx) {
			const a = asteroids[idx];
			score += AST_SIZES[a.size].score;
			asteroids.splice(idx, 1);
			if (a.size > 1) {
				asteroids.push(spawnAsteroid(a.size - 1, a.x, a.y));
				asteroids.push(spawnAsteroid(a.size - 1, a.x, a.y));
			}
			updateHUD();
			if (asteroids.length === 0) { won = true; gameOver = true; }
		}

		function killShip() {
			lives--;
			updateHUD();
			if (lives <= 0) { gameOver = true; won = false; }
			else ship = newShip();
		}

		function update() {
			if (gameOver) return;

			/* rotation: A / left = counterclockwise, S / right = clockwise */
			if (keys['a'] || keys['arrowleft'])  ship.angle -= SHIP_TURN;
			if (keys['s'] || keys['arrowright'])  ship.angle += SHIP_TURN;

			/* thrust: W / up */
			const thrusting = keys['w'] || keys['arrowup'];
			if (thrusting) {
				ship.vx += Math.cos(ship.angle) * SHIP_THRUST;
				ship.vy += Math.sin(ship.angle) * SHIP_THRUST;
			}
			ship.vx *= FRICTION;
			ship.vy *= FRICTION;
			ship.x += ship.vx;
			ship.y += ship.vy;
			wrap(ship);
			if (ship.invuln > 0) ship.invuln--;
			if (fireTimer > 0) fireTimer--;

			/* bullets */
			for (let i = bullets.length - 1; i >= 0; i--) {
				const b = bullets[i];
				b.x += b.vx; b.y += b.vy; wrap(b); b.life--;
				if (b.life <= 0) { bullets.splice(i, 1); continue; }
				for (let j = asteroids.length - 1; j >= 0; j--) {
					const a = asteroids[j];
					if (Math.hypot(b.x - a.x, b.y - a.y) < a.r) {
						bullets.splice(i, 1);
						splitAsteroid(j);
						break;
					}
				}
			}

			/* asteroids move + ship collision */
			for (let i = 0; i < asteroids.length; i++) {
				const a = asteroids[i];
				a.x += a.vx; a.y += a.vy; a.a += a.rot; wrap(a);
				if (ship.invuln <= 0 && Math.hypot(ship.x - a.x, ship.y - a.y) < a.r + SHIP_R) {
					killShip();
					break;
				}
			}
		}

		function drawShip() {
			if (ship.invuln > 0 && Math.floor(ship.invuln / 6) % 2 === 0) return; /* blink */
			ctx.save();
			ctx.translate(ship.x, ship.y);
			ctx.rotate(ship.angle);
			ctx.strokeStyle = '#fff';
			ctx.lineWidth = 2;
			ctx.beginPath();
			ctx.moveTo(SHIP_R, 0);
			ctx.lineTo(-SHIP_R, -SHIP_R * 0.7);
			ctx.lineTo(-SHIP_R * 0.5, 0);
			ctx.lineTo(-SHIP_R, SHIP_R * 0.7);
			ctx.closePath();
			ctx.stroke();
			/* thrust flame */
			if ((keys['w'] || keys['arrowup']) && !gameOver) {
				ctx.strokeStyle = '#f80';
				ctx.beginPath();
				ctx.moveTo(-SHIP_R * 0.5, 0);
				ctx.lineTo(-SHIP_R * 1.4, 0);
				ctx.stroke();
			}
			ctx.restore();
		}

		function drawAsteroid(a) {
			ctx.save();
			ctx.translate(a.x, a.y);
			ctx.rotate(a.a);
			ctx.strokeStyle = '#aaa';
			ctx.lineWidth = 2;
			ctx.beginPath();
			const n = a.offs.length;
			for (let i = 0; i < n; i++) {
				const ang = (i / n) * Math.PI * 2;
				const rr = a.r * a.offs[i];
				const x = Math.cos(ang) * rr, y = Math.sin(ang) * rr;
				if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
			}
			ctx.closePath();
			ctx.stroke();
			ctx.restore();
		}

		function draw() {
			ctx.clearRect(0, 0, W, H);

			ctx.fillStyle = '#fff';
			for (const b of bullets) { ctx.fillRect(b.x - 2, b.y - 2, 4, 4); }

			for (const a of asteroids) drawAsteroid(a);

			if (!gameOver) drawShip();

			if (gameOver) {
				ctx.fillStyle = won ? '#4caf50' : '#e53935';
				ctx.font = 'bold 48px arial';
				ctx.textAlign = 'center';
				ctx.fillText(won ? 'YOU CLEARED THE FIELD!' : 'GAME OVER', W/2, H/2 - 10);
				ctx.fillStyle = '#ccc';
				ctx.font = '20px arial';
				ctx.fillText('Score: ' + score + '  —  press Enter to play again', W/2, H/2 + 30);
			}
		}

		function loop() {
			update();
			draw();
			requestAnimationFrame(loop);
		}

		/* ---- input ---- */
		window.addEventListener('keydown', function(e) {
			const k = e.key.toLowerCase();
			/* keep the page from scrolling on the game keys */
			if ([' ', 'arrowup', 'arrowdown', 'arrowleft', 'arrowright'].includes(k))
				e.preventDefault();

			if (k === ' ') { fire(); }
			else if (k === 'enter') { if (gameOver) startGame(); }
			else { keys[k] = true; }
		});
		window.addEventListener('keyup', function(e) {
			keys[e.key.toLowerCase()] = false;
		});

		startGame();
		loop();
	})();
	</script>

<? include("footer.php") ?>
