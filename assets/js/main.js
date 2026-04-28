/* ============================================================
   AMBOZY GRAPHICS — main.js
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

  /* ---- NAV SCROLL STATE ---- */
  const nav = document.getElementById('nav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 40);
  }, { passive: true });

  /* ---- MOBILE NAV ---- */
  const hamburger = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobileNav');
  hamburger?.addEventListener('click', () => {
    mobileNav.classList.toggle('open');
    document.body.style.overflow = mobileNav.classList.contains('open') ? 'hidden' : '';
  });
  mobileNav?.querySelectorAll('a').forEach(a => {
    a.addEventListener('click', () => {
      mobileNav.classList.remove('open');
      document.body.style.overflow = '';
    });
  });

  /* ---- SCROLL REVEAL ---- */
  const revealEls = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  revealEls.forEach(el => io.observe(el));

  /* ---- COUNTER ANIMATION ---- */
  function animateCounter(el) {
    const target = parseInt(el.dataset.target, 10);
    const duration = 1600;
    const start = performance.now();
    const suffix = el.dataset.suffix || '';
    const animate = (now) => {
      const elapsed = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - elapsed, 3);
      el.textContent = Math.round(eased * target) + suffix;
      if (elapsed < 1) requestAnimationFrame(animate);
    };
    requestAnimationFrame(animate);
  }
  const counterEls = document.querySelectorAll('[data-target]');
  const counterIO = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        animateCounter(e.target);
        counterIO.unobserve(e.target);
      }
    });
  }, { threshold: 0.5 });
  counterEls.forEach(el => counterIO.observe(el));

  /* ---- QUOTE FORM AJAX ---- */
  const form = document.getElementById('quoteForm');
  const successMsg = document.getElementById('formSuccess');
  const errorMsg = document.getElementById('formError');

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = form.querySelector('.form__submit');
    const originalText = btn.textContent;
    btn.textContent = 'Sending…';
    btn.disabled = true;
    successMsg.style.display = 'none';
    errorMsg.style.display = 'none';

    try {
      const data = new FormData(form);
      const res = await fetch('contact-handler.php', { method: 'POST', body: data });
      const json = await res.json();
      if (json.success) {
        successMsg.style.display = 'block';
        form.reset();
      } else {
        errorMsg.textContent = json.message || 'Something went wrong. Please try again.';
        errorMsg.style.display = 'block';
      }
    } catch {
      errorMsg.textContent = 'Network error. Please call us directly.';
      errorMsg.style.display = 'block';
    } finally {
      btn.textContent = originalText;
      btn.disabled = false;
    }
  });

  /* ---- SMOOTH ACTIVE NAV LINKS ---- */
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav__links a');
  window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(s => {
      if (window.scrollY >= s.offsetTop - 100) current = s.id;
    });
    navLinks.forEach(a => {
      a.style.color = a.getAttribute('href') === `#${current}`
        ? 'var(--orange)'
        : '';
    });
  }, { passive: true });

});
