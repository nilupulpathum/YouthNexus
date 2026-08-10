/* ============================================
   YouthNexus Pulse — Landing Page JavaScript
   Vanilla JS — Scroll Animations, Navbar, etc.
   ============================================ */

document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initScrollSpy();
  initScrollAnimations();
  initTierBars();
  initSmoothScroll();
  initMobileMenu();
  initHeroCarousel();
});

/* ---------- Navbar Scroll Effect ---------- */
function initNavbar() {
  const navbar = document.getElementById('navbar');
  if (!navbar) return;

  let lastScroll = 0;

  window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;

    if (currentScroll > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }

    lastScroll = currentScroll;
  }, { passive: true });
}

/* ---------- Scroll Spy — Active Nav Link ---------- */
function initScrollSpy() {
  const navLinks = document.querySelectorAll('.navbar__link');
  // Map each nav link to the section it targets
  const sections = [];

  navLinks.forEach(link => {
    const href = link.getAttribute('href');
    if (href && href.startsWith('#')) {
      const section = document.querySelector(href);
      if (section) {
        sections.push({ link, section });
      }
    }
  });

  if (sections.length === 0) return;

  function updateActiveLink() {
    const scrollPos = window.pageYOffset + 120; // offset for fixed navbar

    let currentSection = sections[0]; // default to first section

    for (let i = sections.length - 1; i >= 0; i--) {
      const { section } = sections[i];
      if (section.offsetTop <= scrollPos) {
        currentSection = sections[i];
        break;
      }
    }

    navLinks.forEach(link => link.classList.remove('active'));
    currentSection.link.classList.add('active');
  }

  window.addEventListener('scroll', updateActiveLink, { passive: true });
  // Run once on load
  updateActiveLink();
}

/* ---------- Scroll-triggered Fade-in Animations ---------- */
function initScrollAnimations() {
  const animatedElements = document.querySelectorAll('.fade-in, .fade-in-left, .fade-in-right');

  if (animatedElements.length === 0) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.15,
    rootMargin: '0px 0px -50px 0px'
  });

  animatedElements.forEach(el => observer.observe(el));
}

/* ---------- Tier Progress Bar Animation ---------- */
function initTierBars() {
  const tierBars = document.querySelectorAll('.tier-bar__fill');

  if (tierBars.length === 0) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animated');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.5
  });

  tierBars.forEach(bar => observer.observe(bar));
}

/* ---------- Smooth Scroll for Anchor Links ---------- */
function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;

      const target = document.querySelector(targetId);
      if (!target) return;

      e.preventDefault();
      target.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });

      // Close mobile menu if open
      closeMobileMenu();
    });
  });
}

/* ---------- Mobile Hamburger Menu ---------- */
function initMobileMenu() {
  const hamburger = document.getElementById('navbar-hamburger');
  const links = document.getElementById('navbar-links');
  const actions = document.getElementById('navbar-actions');

  if (!hamburger || !links) return;

  hamburger.addEventListener('click', () => {
    const isOpen = hamburger.classList.toggle('open');
    links.classList.toggle('open', isOpen);
    if (actions) actions.classList.toggle('open', isOpen);

    // Prevent body scroll when menu is open
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  // Close menu when clicking outside
  document.addEventListener('click', (e) => {
    if (!hamburger.contains(e.target) && !links.contains(e.target)) {
      closeMobileMenu();
    }
  });
}

function closeMobileMenu() {
  const hamburger = document.getElementById('navbar-hamburger');
  const links = document.getElementById('navbar-links');
  const actions = document.getElementById('navbar-actions');

  if (hamburger) hamburger.classList.remove('open');
  if (links) links.classList.remove('open');
  if (actions) actions.classList.remove('open');
  document.body.style.overflow = '';
}

/* ---------- Hero Carousel ---------- */
function initHeroCarousel() {
  const slides = document.querySelectorAll('.hero__slide');
  const dots = document.querySelectorAll('.hero__dot');
  const prevBtn = document.getElementById('hero-prev');
  const nextBtn = document.getElementById('hero-next');

  if (slides.length === 0) return;

  let currentSlide = 0;
  const totalSlides = slides.length;
  let autoPlayInterval = null;
  let isTransitioning = false;

  function goToSlide(index) {
    if (isTransitioning || index === currentSlide) return;
    isTransitioning = true;

    // Remove active from current slide and dot
    slides[currentSlide].classList.remove('hero__slide--active');
    if (dots[currentSlide]) dots[currentSlide].classList.remove('hero__dot--active');

    // Update current index
    currentSlide = ((index % totalSlides) + totalSlides) % totalSlides;

    // Add active to new slide and dot
    slides[currentSlide].classList.add('hero__slide--active');
    if (dots[currentSlide]) dots[currentSlide].classList.add('hero__dot--active');

    // Allow next transition after animation completes
    setTimeout(() => {
      isTransitioning = false;
    }, 800);
  }

  function nextSlide() {
    goToSlide(currentSlide + 1);
  }

  function prevSlide() {
    goToSlide(currentSlide - 1);
  }

  // Arrow button clicks
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      nextSlide();
      resetAutoPlay();
    });
  }

  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      prevSlide();
      resetAutoPlay();
    });
  }

  // Dot clicks
  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const index = parseInt(dot.getAttribute('data-dot'), 10);
      goToSlide(index);
      resetAutoPlay();
    });
  });

  // Auto-play
  function startAutoPlay() {
    autoPlayInterval = setInterval(nextSlide, 5000);
  }

  function resetAutoPlay() {
    clearInterval(autoPlayInterval);
    startAutoPlay();
  }

  // Pause on hover
  const heroSection = document.getElementById('hero');
  if (heroSection) {
    heroSection.addEventListener('mouseenter', () => {
      clearInterval(autoPlayInterval);
    });
    heroSection.addEventListener('mouseleave', () => {
      startAutoPlay();
    });
  }

  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    // Only handle if hero is visible in viewport
    const heroRect = heroSection?.getBoundingClientRect();
    if (!heroRect || heroRect.bottom < 0 || heroRect.top > window.innerHeight) return;

    if (e.key === 'ArrowLeft') {
      prevSlide();
      resetAutoPlay();
    } else if (e.key === 'ArrowRight') {
      nextSlide();
      resetAutoPlay();
    }
  });

  // Start auto-play
  startAutoPlay();
}
