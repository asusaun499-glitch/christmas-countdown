(() => {
  const config = window.CHRISTMAS_CONFIG;
  let target = Number(config.targetMs);
  const serverAnchor = Number(config.serverNowMs);
  let clockOffset = 0;
  let celebrated = false;
  const song = document.getElementById('christmasSong');
  const fields = {
    days: document.getElementById('days'),
    hours: document.getElementById('hours'),
    minutes: document.getElementById('minutes'),
    seconds: document.getElementById('seconds')
  };
  const two = value => String(value).padStart(2, '0');

  function animate(el, value, width) {
    const next = String(value).padStart(width, '0');
    if (el.textContent === next) return;
    el.textContent = next;
    if (window.gsap && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
      gsap.fromTo(el, { y: 7, opacity: .6 }, { y: 0, opacity: 1, duration: .3, ease: 'power2.out', overwrite: true });
    }
  }

  function celebrate() {
    if (celebrated) return;
    celebrated = true;
    const overlay = document.getElementById('celebration');
    overlay.classList.add('show');
    overlay.setAttribute('aria-hidden', 'false');
    song.play().catch(() => {
      // Audible autoplay may be blocked until the visitor interacts with the page.
    });
    fireworks.start();
  }

  function tick() {
    const now = serverAnchor + performance.now() + clockOffset;
    let remaining = Math.max(0, target - now);
    const days = Math.floor(remaining / 86400000); remaining %= 86400000;
    const hours = Math.floor(remaining / 3600000); remaining %= 3600000;
    const minutes = Math.floor(remaining / 60000);
    const seconds = Math.floor((remaining % 60000) / 1000);
    animate(fields.days, days, 3);
    animate(fields.hours, hours, 2);
    animate(fields.minutes, minutes, 2);
    animate(fields.seconds, seconds, 2);
    if (now >= target) celebrate();
  }

  async function syncServerTime() {
    try {
      const response = await fetch('time.php', { cache: 'no-store' });
      if (!response.ok) return;
      const data = await response.json();
      clockOffset = Number(data.nowMs) - (serverAnchor + performance.now());
      if (data.targetMs) target = Number(data.targetMs);
    } catch (_) {
      // The countdown still runs from its initial clock if the PHP endpoint is unavailable.
    }
  }

  const fireworks = (() => {
    const canvas = document.getElementById('fireworks');
    const ctx = canvas.getContext('2d');
    const colors = ['#f3d18a', '#e57969', '#b9d296', '#f4eee0', '#d18c66'];
    let width = 0, height = 0, particles = [], active = false;
    function resize() {
      const ratio = Math.min(devicePixelRatio || 1, 2);
      width = innerWidth; height = innerHeight;
      canvas.width = width * ratio; canvas.height = height * ratio;
      ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    }
    function burst(x = Math.random() * width, y = height * (.18 + Math.random() * .45)) {
      const color = colors[Math.random() * colors.length | 0];
      for (let i = 0, count = 85; i < count; i++) {
        const angle = Math.PI * 2 * i / count, speed = 1.2 + Math.random() * 4.2;
        particles.push({ x, y, vx: Math.cos(angle) * speed, vy: Math.sin(angle) * speed, life: 65 + Math.random() * 35, color, size: 1 + Math.random() * 2 });
      }
    }
    function draw() {
      ctx.clearRect(0, 0, width, height);
      particles = particles.filter(p => p.life > 0);
      for (const p of particles) {
        p.x += p.vx; p.y += p.vy; p.vy += .025; p.vx *= .99; p.life--;
        ctx.globalAlpha = Math.min(1, p.life / 22); ctx.fillStyle = p.color;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2); ctx.fill();
      }
      ctx.globalAlpha = 1;
      if (active) requestAnimationFrame(draw);
    }
    return {
      start() {
        if (active) return;
        active = true; resize(); burst(width * .5, height * .3);
        let count = 0;
        const interval = setInterval(() => {
          burst();
          if (++count >= 28) clearInterval(interval);
        }, 650);
        draw();
      },
      resize
    };
  })();

  addEventListener('resize', fireworks.resize);
  tick();
  setInterval(tick, 200);
  setInterval(syncServerTime, 5000);
  syncServerTime();
})();
