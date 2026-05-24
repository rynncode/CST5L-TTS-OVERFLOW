<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overflow</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Space+Grotesk:wght@300&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { width: 100%; height: 100%; background: #07070f; overflow: hidden; }

    #c { position: fixed; inset: 0; width: 100%; height: 100%; display: block; }

    #logo {
      position: fixed; inset: 0;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      pointer-events: none; z-index: 5;
      opacity: 0;
    }
    .wm {
      font-family: 'Bebas Neue', sans-serif;
      font-size: clamp(2.8rem, 9vw, 5.5rem);
      letter-spacing: .12em; color: #eeeef8; line-height: 1;
      text-shadow: 0 0 40px rgba(162,134,255,.7), 0 0 90px rgba(124,92,252,.4);
    }
    .wm span { color: #a286ff; }
    .sub {
      font-family: 'Space Grotesk', sans-serif;
      font-size: clamp(.65rem, 1.8vw, .9rem);
      letter-spacing: .32em; color: rgba(162,134,255,.55);
      margin-top: .55rem; text-transform: uppercase;
    }

    /* fade-to-black curtain that triggers the redirect */
    #curtain {
      position: fixed; inset: 0;
      background: #07070f;
      z-index: 20;
      opacity: 0;
      pointer-events: none;
    }

    @media (prefers-reduced-motion: reduce) {
      #c, #logo { display: none !important; }
    }
  </style>
</head>
<body>
<canvas id="c"></canvas>
<div id="logo">
  <div class="wm">OVER<span>FLOW</span></div>
  <div class="sub">Let the journey flow smoothly</div>
</div>
<div id="curtain"></div>

<script>
// Redirects after 4s regardless of animation state (To avoid issues)
const SAFETY_TIMEOUT = setTimeout(function() {
  window.location.replace('dashboard.php');
}, 4000);

// Bail immediately for reduced-motion users
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  clearTimeout(SAFETY_TIMEOUT);
  window.location.replace('dashboard.php');
}

const canvas  = document.getElementById('c');
const ctx     = canvas.getContext('2d');
const logo    = document.getElementById('logo');
const curtain = document.getElementById('curtain');

let W, H, cx, cy;
function resize() {
  W = canvas.width  = window.innerWidth;
  H = canvas.height = window.innerHeight;
  cx = W / 2; cy = H / 2;
}
resize();
window.addEventListener('resize', resize);

// ── Colours ──────────────────────────────────────────────────
const PAL = [
  [162,134,255], // lavender
  [124, 92,252], // violet
  [196,170,255], // pale purple
  [255,255,255], // white
  [ 79,195,247], // ice blue
];

// ── Stars ────────────────────────────────────────────────────
const N_FIELD   = 1600;
const N_SPIRAL  = 500;
const N         = N_FIELD + N_SPIRAL;
// flat arrays for speed
const sx   = new Float32Array(N); // base x (from center)
const sy   = new Float32Array(N); // base y
const ssz  = new Float32Array(N); // base size
const sci  = new Uint8Array(N);   // color index

function init() {
  for (let i = 0; i < N_FIELD; i++) {
    const a = Math.random() * Math.PI * 2;
    const r = (0.1 + Math.random() * 0.9) * Math.max(W, H) * 0.75;
    sx[i]  = Math.cos(a) * r;
    sy[i]  = Math.sin(a) * r;
    ssz[i] = Math.random() * 1.4 + 0.3;
    sci[i] = Math.random() < 0.6 ? 3 : Math.floor(Math.random() * PAL.length);
  }
  const ARMS = 3;
  for (let i = 0; i < N_SPIRAL; i++) {
    const idx  = N_FIELD + i;
    const arm  = i % ARMS;
    const t    = i / N_SPIRAL;
    const ang  = (arm / ARMS) * Math.PI * 2 + t * Math.PI * 3.8
               + (Math.random() - .5) * 0.35;
    const r    = t * Math.max(W, H) * 0.52 + (Math.random() - .5) * 55;
    sx[idx]  = Math.cos(ang) * r;
    sy[idx]  = Math.sin(ang) * r * 0.52;
    ssz[idx] = Math.random() * 1.7 + 0.4;
    sci[idx] = Math.floor(Math.random() * PAL.length);
  }
}
init();

// ── Timeline (ms) ────────────────────────────────────────────
// 0       –  400  drift + gentle zoom-in
// 400     –  900  converge: stars rush toward center
// 900     – 1200  collapse: everything implodes
// 1200    – 1450  white-purple flash
// 1450    – 1750  logo fades in
// 1750    – 2100  curtain fades to black → redirect
const T = {
  DRIFT_END:    400,
  CONV_END:     900,
  COLL_END:    1200,
  FLASH_END:   1450,
  LOGO_END:    1750,
  DONE:        2100,
};

let t0 = null;
let redirectFired = false;

function easeOut3(t) { return 1 - Math.pow(1-t, 3); }
function easeIn2(t)  { return t * t; }
function clamp(v,a,b){ return Math.max(a, Math.min(b, v)); }
function norm(v,a,b) { return clamp((v-a)/(b-a), 0, 1); }

// ── Nebula glow ──────────────────────────────────────────────
function nebula(p) {
  const r  = Math.max(W,H) * clamp(0.55 - p * 0.45, 0.05, 0.6);
  const a  = p < 0.75 ? 0.07 + p * 0.07 : 0.13 * (1 - norm(p, 0.75, 1.0));
  if (a < 0.005) return;
  const g = ctx.createRadialGradient(cx,cy,0, cx,cy,r);
  g.addColorStop(0,   `rgba(124,92,252,${(a*.9).toFixed(3)})`);
  g.addColorStop(0.5, `rgba(162,134,255,${(a*.4).toFixed(3)})`);
  g.addColorStop(1,   'rgba(7,7,15,0)');
  ctx.fillStyle = g;
  ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.fill();
}

function coreGlow(p) {
  if (p < 0.45) return;
  const t = norm(p, 0.45, 0.85);
  const r = 20 + t * 200;
  const a = t * 0.55;
  const g = ctx.createRadialGradient(cx,cy,0, cx,cy,r);
  g.addColorStop(0,   `rgba(255,255,255,${Math.min(1,a*2.2).toFixed(2)})`);
  g.addColorStop(0.25,`rgba(196,170,255,${a.toFixed(2)})`);
  g.addColorStop(0.7, `rgba(124,92,252,${(a*.45).toFixed(2)})`);
  g.addColorStop(1,   'rgba(7,7,15,0)');
  ctx.fillStyle = g;
  ctx.beginPath(); ctx.arc(cx,cy,r,0,Math.PI*2); ctx.fill();
}

// ── Frame ────────────────────────────────────────────────────
function frame(ts) {
  if (!t0) t0 = ts;
  const ms = ts - t0;
  const p  = clamp(ms / T.DONE, 0, 1); // global 0→1

  // clear
  ctx.fillStyle = '#07070f';
  ctx.fillRect(0,0,W,H);

  nebula(p);

  // ── Draw stars ──────────────────────────────────────────────
  const rot = p * Math.PI * 1.4; // galaxy rotation

  for (let i = 0; i < N; i++) {
    const bx = sx[i], by = sy[i];
    const base = ssz[i];
    const [r,g,b] = PAL[sci[i]];

    let dx, dy, sz, alpha;

    if (ms < T.DRIFT_END) {
      // gentle slow rotation
      const t = norm(ms, 0, T.DRIFT_END);
      const cosR = Math.cos(rot * .25), sinR = Math.sin(rot * .25);
      dx    = cosR*bx - sinR*by;
      dy    = sinR*bx + cosR*by;
      sz    = base;
      alpha = 0.35 + t * 0.25;

    } else if (ms < T.CONV_END) {
      // stars rush toward center with zoom
      const t  = norm(ms, T.DRIFT_END, T.CONV_END);
      const e  = easeOut3(t);
      const cosR = Math.cos(rot), sinR = Math.sin(rot);
      const rx = cosR*bx - sinR*by;
      const ry = sinR*bx + cosR*by;
      const zoom = 1 + e * 7;
      dx    = rx / zoom;
      dy    = ry / zoom;
      sz    = base * (0.7 + e * 2.2);
      alpha = 0.55 + e * 0.45;

    } else if (ms < T.COLL_END) {
      // implode to exact center
      const t  = norm(ms, T.CONV_END, T.COLL_END);
      const e  = easeIn2(t);
      dx    = bx * (1 - e) * 0.04;
      dy    = by * (1 - e) * 0.04;
      sz    = base * (0.5 - e * 0.45);
      alpha = 1 - e * 0.7;

    } else {
      continue; // stars gone after collapse
    }

    if (sz < 0.05 || alpha <= 0) continue;
    alpha = clamp(alpha, 0, 1);

    ctx.beginPath();
    ctx.arc(cx + dx, cy + dy, sz, 0, Math.PI*2);
    ctx.fillStyle = `rgba(${r},${g},${b},${alpha.toFixed(2)})`;
    ctx.fill();

    // soft halo on larger stars
    if (sz > 1.1 && alpha > 0.4) {
      ctx.beginPath();
      ctx.arc(cx + dx, cy + dy, sz * 2.8, 0, Math.PI*2);
      ctx.fillStyle = `rgba(${r},${g},${b},${(alpha * 0.1).toFixed(2)})`;
      ctx.fill();
    }
  }

  coreGlow(p);

  // ── Flash ────────────────────────────────────────────────────
  if (ms >= T.COLL_END && ms < T.FLASH_END) {
    const t    = norm(ms, T.COLL_END, T.FLASH_END);
    const peak = t < 0.35 ? t/0.35 : 1-(t-0.35)/0.65;
    ctx.fillStyle = `rgba(220,210,255,${(peak * 0.9).toFixed(2)})`;
    ctx.fillRect(0,0,W,H);
  }

  // ── Logo fade-in ─────────────────────────────────────────────
  if (ms >= T.FLASH_END) {
    const t = norm(ms, T.FLASH_END, T.LOGO_END);
    logo.style.opacity = easeOut3(t).toFixed(3);
    const sc = 0.88 + easeOut3(t) * 0.12;
    logo.style.transform = `scale(${sc.toFixed(3)})`;
  }

  // ── Curtain fade → redirect ───────────────────────────────────
  if (ms >= T.LOGO_END) {
    const t = norm(ms, T.LOGO_END, T.DONE);
    curtain.style.opacity = easeIn2(t).toFixed(3);

    if (t >= 1 && !redirectFired) {
      redirectFired = true;
      clearTimeout(SAFETY_TIMEOUT);
      window.location.replace('dashboard.php');
      return; // stop rAF
    }
  }

  requestAnimationFrame(frame);
}

requestAnimationFrame(frame);
</script>
</body>
</html>
