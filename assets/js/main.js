/* ============================================================
   assets/js/main.js — BUP Shared JavaScript
   ============================================================ */

/* ── Scroll Bar & Navbar ─────────────────────────────────────── */
window.addEventListener('scroll', () => {
  const navbar = document.getElementById('navbar');
  const scrollBar = document.getElementById('scrollBar');
  if (navbar) navbar.classList.toggle('scrolled', scrollY > 50);
  if (scrollBar) {
    const pct = scrollY / (document.body.scrollHeight - innerHeight) * 100;
    scrollBar.style.width = pct + '%';
  }
});

/* ── Mobile Menu ─────────────────────────────────────────────── */
function toggleMenu() {
  document.getElementById('mobMenu')?.classList.toggle('open');
}

/* ── Fade-Up Observer ────────────────────────────────────────── */
function initFadeObs() {
  const obs = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('vis'); });
  }, { threshold: 0.1 });
  document.querySelectorAll('.fade-up').forEach(el => obs.observe(el));
}
document.addEventListener('DOMContentLoaded', initFadeObs);

/* ── Gallery Slider ──────────────────────────────────────────── */
let galIdx = 0;
let galAutoInterval = null;

function galGetSlideWidth() {
  const track = document.getElementById('galTrack');
  if (!track) return 0;
  const slide = track.querySelector('.gal-slide');
  return slide ? slide.offsetWidth + 16 : 0;
}

function galGetMax() {
  const track = document.getElementById('galTrack');
  if (!track) return 0;
  const slides = track.querySelectorAll('.gal-slide');
  const visible = window.innerWidth > 768 ? 4 : 1;
  return Math.max(0, slides.length - Math.floor(visible));
}

function galApply() {
  const track = document.getElementById('galTrack');
  if (!track) return;
  track.style.transform = `translateX(-${galIdx * galGetSlideWidth()}px)`;
}

function galNext() {
  galIdx = galIdx >= galGetMax() ? 0 : galIdx + 1;
  galApply();
}

function galPrev() {
  galIdx = galIdx <= 0 ? galGetMax() : galIdx - 1;
  galApply();
}

function startGalAuto() {
  if (galAutoInterval) return;
  galAutoInterval = setInterval(galNext, 3000);
}

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('galTrack')) startGalAuto();
});

// Pastikan kode ini ada di dalam file assets/js/main.js

function togglePlay() {
  var vid = document.getElementById("promoVideo");
  var btn = document.getElementById("playBtn");
  if (vid.paused) { vid.play(); btn.classList.add("hidden"); } 
  else { vid.pause(); btn.classList.remove("hidden"); }
}

