<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// Destroy session server-side immediately
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overflow</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html,body{width:100%;height:100%;overflow:hidden;background:#000}
    #c{position:fixed;inset:0;width:100%;height:100%;display:block;}
    #veil{position:fixed;inset:0;z-index:10;background:#000;opacity:0;pointer-events:none;}
    @media(prefers-reduced-motion:reduce){
      #c{display:none!important;}
    }
  </style>
</head>
<body>
<canvas id="c"></canvas>
<div id="veil"></div>
<script>
// ── Safety net: always redirect after 5s no matter what ──────
const SAFETY = setTimeout(() => {
  window.location.replace('login.php?logout=1');
}, 5000);

// ── Reduced motion: instant redirect ─────────────────────────
if (window.matchMedia('(prefers-reduced-motion:reduce)').matches) {
  clearTimeout(SAFETY);
  window.location.replace('login.php?logout=1');
}

const canvas = document.getElementById('c');
const ctx    = canvas.getContext('2d');
const veil   = document.getElementById('veil');

let W, H, cx, cy;
function resize() {
  W = canvas.width  = window.innerWidth;
  H = canvas.height = window.innerHeight;
  cx = W/2; cy = H/2;
}
resize();
window.addEventListener('resize', resize);

// ── Noise helpers ─────────────────────────────────────────────
function hash2(x, y) {
  let h = Math.sin(x * 127.1 + y * 311.7) * 43758.5453;
  return h - Math.floor(h);
}
function noise2(x, y) {
  const ix = Math.floor(x), iy = Math.floor(y);
  const fx = x-ix, fy = y-iy;
  const ux = fx*fx*(3-2*fx), uy = fy*fy*(3-2*fy);
  return (hash2(ix,iy)*(1-ux) + hash2(ix+1,iy)*ux) * (1-uy)
       + (hash2(ix,iy+1)*(1-ux) + hash2(ix+1,iy+1)*ux) * uy;
}

// ── Stars ─────────────────────────────────────────────────────
const NSTARS = 280;
const starX = new Float32Array(NSTARS);
const starY = new Float32Array(NSTARS);
const starB = new Float32Array(NSTARS); // brightness
for (let i = 0; i < NSTARS; i++) {
  starX[i] = Math.random();
  starY[i] = Math.random();
  starB[i] = Math.random();
}

// ── Dashboard particles (color palette of the app) ────────────
const NP = 1800;
const px   = new Float32Array(NP);
const py   = new Float32Array(NP);
const pr   = new Float32Array(NP); // radius from center
const pa   = new Float32Array(NP); // angle
const psz  = new Float32Array(NP); // size
const pspd = new Float32Array(NP); // speed factor
const pc   = new Array(NP);        // color string

const PAL = [
  '#a286ff','#7c5cfc','#c4aaff',
  '#4fc3f7','#2dd4a0','#f4a33a',
  '#eeeef8','rgba(162,134,255,.4)',
  '#0c0c1a','#12121f',
];

for (let i = 0; i < NP; i++) {
  // Start scattered across the screen
  const angle = Math.random() * Math.PI * 2;
  const dist  = (0.08 + Math.random() * 0.92) * Math.max(W,H) * 0.62;
  px[i]   = cx + Math.cos(angle) * dist;
  py[i]   = cy + Math.sin(angle) * dist;
  pr[i]   = dist;
  pa[i]   = angle;
  psz[i]  = Math.random() * 3.5 + 0.8;
  pspd[i] = 0.5 + Math.random() * 0.5;
  pc[i]   = PAL[Math.floor(Math.random() * PAL.length)];
}

// ── Timeline (ms) ─────────────────────────────────────────────
const T = {
  DISK_IN:    700,   // 0    → 700   BH disk appears
  SUCK_END:  2000,   // 700  → 2000  particles spiral in
  PEAK:      2500,   // 2000 → 2500  accretion at max brightness
  COLLAPSE:  3000,   // 2500 → 3000  BH swallows everything
  FLASH_END: 3350,   // 3000 → 3350  white flash
  DONE:      3800,   // 3350 → 3800  fade to black → redirect
};

let t0   = null;
let done = false;

function norm(v,a,b){ return Math.max(0,Math.min(1,(v-a)/(b-a))); }
function easeIn2(t) { return t*t; }
function easeIn3(t) { return t*t*t; }
function easeOut3(t){ return 1-Math.pow(1-t,3); }

// ── Draw starfield ────────────────────────────────────────────
function drawStars(alpha) {
  if (alpha <= 0) return;
  for (let i = 0; i < NSTARS; i++) {
    const x = starX[i] * W;
    const y = starY[i] * H;
    const b = starB[i];
    const r = b > 0.92 ? 1.5 : 0.7;
    ctx.beginPath();
    ctx.arc(x, y, r, 0, Math.PI*2);
    const a = (0.3 + b * 0.7) * alpha;
    ctx.fillStyle = b > 0.85
      ? `rgba(196,170,255,${a.toFixed(2)})`
      : `rgba(255,255,255,${a.toFixed(2)})`;
    ctx.fill();
  }
}

// ── Draw accretion disk ───────────────────────────────────────
function drawDisk(bhR, bright, time) {
  if (bright <= 0 || bhR <= 0) return;

  const R2 = bhR * 1.7;   // inner disk edge
  const R3 = bhR * 4.2;   // outer disk edge

  // Disk is drawn as stacked arcs with varying brightness
  // Doppler: right side (cos>0) is brighter
  const steps = 120;
  for (let s = 0; s < steps; s++) {
    const angle = (s / steps) * Math.PI * 2;
    const next  = ((s+1) / steps) * Math.PI * 2;

    // Doppler factor: approaching side (right) is 2.5× brighter
    const doppler = 0.18 + 0.82 * Math.pow((Math.cos(angle)+1)/2, 2.0);

    // Draw arc band at multiple radii (disk depth)
    const radBands = 18;
    for (let rb = 0; rb < radBands; rb++) {
      const t    = rb / (radBands-1);
      const rad  = R2 + (R3-R2) * t;

      // Temperature: hotter (whiter/brighter) near inner edge
      const temp = 1 - t;
      const turbT = noise2(Math.cos(angle)*rad*0.012 + time*0.4,
                           Math.sin(angle)*rad*0.012 - time*0.3);
      const turbI = 0.55 + turbT * 0.8;

      const intensity = doppler * turbI * (0.4 + temp * 0.9) * bright;

      // Color: white-hot → orange → dark red
      let r,g,b;
      if (temp > 0.7) {
        const tt = (temp-0.7)/0.3;
        r = 255; g = Math.round(210+tt*45); b = Math.round(160+tt*95);
      } else if (temp > 0.35) {
        const tt = (temp-0.35)/0.35;
        r = 255; g = Math.round(100+tt*110); b = Math.round(tt*60);
      } else {
        const tt = temp/0.35;
        r = Math.round(80+tt*175); g = Math.round(tt*100); b = 0;
      }

      const a = Math.min(1, intensity * 0.55);
      if (a < 0.01) continue;

      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.arc(cx, cy, rad, angle, next);
      ctx.closePath();
      ctx.fillStyle = `rgba(${r},${g},${b},${a.toFixed(3)})`;
      ctx.fill();
    }
  }

  // Outer soft glow halo
  const glow = ctx.createRadialGradient(cx,cy,bhR*1.2, cx,cy,R3*1.4);
  glow.addColorStop(0,   `rgba(255,140,20,${(0.18*bright).toFixed(2)})`);
  glow.addColorStop(0.5, `rgba(200,80,10,${(0.06*bright).toFixed(2)})`);
  glow.addColorStop(1,   'rgba(0,0,0,0)');
  ctx.fillStyle = glow;
  ctx.beginPath(); ctx.arc(cx,cy,R3*1.4,0,Math.PI*2); ctx.fill();

  // Photon sphere glow rim
  const rimW = bhR * 0.18;
  const rim  = ctx.createRadialGradient(cx,cy,bhR-rimW, cx,cy,bhR+rimW*2);
  rim.addColorStop(0,   'rgba(0,0,0,0)');
  rim.addColorStop(0.4, `rgba(255,200,80,${(0.55*bright).toFixed(2)})`);
  rim.addColorStop(0.7, `rgba(255,140,20,${(0.25*bright).toFixed(2)})`);
  rim.addColorStop(1,   'rgba(0,0,0,0)');
  ctx.fillStyle = rim;
  ctx.beginPath(); ctx.arc(cx,cy,bhR+rimW*2,0,Math.PI*2); ctx.fill();
}

// ── Draw event horizon ────────────────────────────────────────
function drawEH(bhR) {
  if (bhR <= 0) return;
  // Perfect black circle
  ctx.beginPath();
  ctx.arc(cx, cy, bhR, 0, Math.PI*2);
  ctx.fillStyle = '#000000';
  ctx.fill();
}

// ── Main frame ────────────────────────────────────────────────
function frame(ts) {
  if (!t0) t0 = ts;
  const ms = ts - t0;

  // Clear to deep space
  ctx.fillStyle = '#000005';
  ctx.fillRect(0,0,W,H);

  // Star alpha: fade out as BH grows
  const starAlpha = Math.max(0, 1 - norm(ms, T.DISK_IN, T.SUCK_END) * 0.7);
  drawStars(starAlpha);

  // ── BH size and disk brightness across timeline ───────────────
  let bhR   = 0;
  let bright= 0;

  if (ms < T.DISK_IN) {
    // Boot up
    const t = norm(ms, 0, T.DISK_IN);
    bhR    = easeOut3(t) * Math.min(W,H) * 0.11;
    bright = easeOut3(t);

  } else if (ms < T.SUCK_END) {
    bhR    = Math.min(W,H) * 0.11;
    bright = 1.0;

  } else if (ms < T.PEAK) {
    // Intensify
    const t = norm(ms, T.SUCK_END, T.PEAK);
    bhR    = Math.min(W,H) * 0.11;
    bright = 1.0 + easeIn2(t) * 1.2; // over-bright

  } else if (ms < T.COLLAPSE) {
    // BH expands rapidly, eating disk
    const t = norm(ms, T.PEAK, T.COLLAPSE);
    bhR    = Math.min(W,H) * (0.11 + easeIn3(t) * 0.55);
    bright = Math.max(0, 1 - t * 2.2);

  } else if (ms < T.FLASH_END) {
    bhR    = 0;
    bright = 0;

  } else {
    bhR    = 0;
    bright = 0;
  }

  // Draw in correct order: disk behind, EH on top
  drawDisk(bhR, Math.min(bright, 2.2), ms * 0.001);
  drawEH(bhR);

  // ── Gravitational lensing hint ────────────────────────────────
  // Dark region + subtle light bending around EH
  if (bhR > 10) {
    const lensR = bhR * 2.2;
    const lens  = ctx.createRadialGradient(cx,cy,bhR*0.95, cx,cy,lensR);
    lens.addColorStop(0, 'rgba(0,0,0,0.85)');
    lens.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = lens;
    ctx.beginPath(); ctx.arc(cx,cy,lensR,0,Math.PI*2); ctx.fill();
    drawDisk(bhR, Math.min(bright,2.2)*0.6, ms*0.001); // redraw disk over lens
    drawEH(bhR);
  }

  // ── Particles spiral into BH ──────────────────────────────────
  if (ms > T.DISK_IN * 0.5 && ms < T.COLLAPSE) {
    const suckT  = norm(ms, T.DISK_IN * 0.5, T.COLLAPSE);
    const suckE  = easeIn2(suckT);
    const pAlpha = Math.max(0, 1 - suckE * 1.1);

    ctx.save();
    for (let i = 0; i < NP; i++) {
      const curR = pr[i];
      if (curR < bhR * 0.7) continue; // swallowed

      // Spiral inward
      const speed = pspd[i] * suckE * 0.018 * (1 + 1.2/Math.max(curR,1));
      pr[i]  = Math.max(0, curR - speed * curR);
      pa[i] -= speed * 3.5; // clockwise rotation

      // Tidal stretch: elongate tangentially near EH
      const stretch = 1 + Math.max(0, (bhR * 2.5 - pr[i])) / Math.max(bhR,1) * 3;

      const drawX = cx + Math.cos(pa[i]) * pr[i];
      const drawY = cy + Math.sin(pa[i]) * pr[i];

      // Heat up color as it approaches EH
      const heat = Math.max(0, 1 - pr[i] / (bhR * 5));
      const a    = pAlpha * (0.5 + heat * 0.5);
      if (a < 0.02) continue;

      ctx.save();
      ctx.translate(drawX, drawY);
      ctx.rotate(pa[i] + Math.PI/2);
      ctx.scale(1/stretch, stretch); // tangential elongation

      ctx.beginPath();
      ctx.arc(0, 0, psz[i] * (1 + heat), 0, Math.PI*2);

      if (heat > 0.5) {
        // Glowing orange/white as it crosses disk
        const hh = (heat - 0.5) / 0.5;
        ctx.fillStyle = `rgba(255,${Math.round(200-hh*150)},${Math.round(50-hh*50)},${a.toFixed(2)})`;
      } else {
        ctx.fillStyle = pc[i].includes('rgba')
          ? pc[i]
          : pc[i] + Math.round(a*255).toString(16).padStart(2,'0').replace('rgba','').replace(')','');
        ctx.globalAlpha = a;
      }
      ctx.fill();
      ctx.restore();
    }
    ctx.restore();
    ctx.globalAlpha = 1;
  }

  // ── Flash ─────────────────────────────────────────────────────
  if (ms >= T.COLLAPSE && ms < T.FLASH_END) {
    const t    = norm(ms, T.COLLAPSE, T.FLASH_END);
    const peak = t < 0.3 ? t/0.3 : 1-(t-0.3)/0.7;
    const a    = peak * 0.94;
    // White-hot center burst
    const burst = ctx.createRadialGradient(cx,cy,0, cx,cy,Math.min(W,H)*0.4*peak);
    burst.addColorStop(0,   `rgba(255,255,255,${(a).toFixed(2)})`);
    burst.addColorStop(0.3, `rgba(220,200,255,${(a*0.7).toFixed(2)})`);
    burst.addColorStop(0.7, `rgba(140,80,255,${(a*0.3).toFixed(2)})`);
    burst.addColorStop(1,   'rgba(0,0,0,0)');
    ctx.fillStyle = burst;
    ctx.fillRect(0,0,W,H);
  }

  // ── Fade to black → redirect ──────────────────────────────────
  if (ms >= T.FLASH_END) {
    const t = norm(ms, T.FLASH_END, T.DONE);
    veil.style.opacity = easeIn2(t).toFixed(3);
    if (t >= 1 && !done) {
      done = true;
      clearTimeout(SAFETY);
      window.location.replace('login.php?logout=1');
      return;
    }
  }

  requestAnimationFrame(frame);
}

requestAnimationFrame(frame);
</script>
</body>
</html>
