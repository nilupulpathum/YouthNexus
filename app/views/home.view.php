<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="YouthNexus Pulse — The official digital ecosystem of the Sri Lanka Youth Leadership Council. Empowering Sri Lanka's youth, connecting communities through structured governance.">
  <title>YouthNexus Pulse — Empowering Sri Lanka's Youth</title>

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Stylesheet -->
  <link rel="stylesheet" href="<?=ROOT?>/assets/css/landing.css">
</head>
<body>

  <!-- ========== NAVBAR ========== -->
  <nav class="navbar" id="navbar">
    <div class="container">
      <a href="#" class="navbar__brand">
        <div class="navbar__logo"></div>
        <span class="navbar__title">YouthNexus Pulse</span>
      </a>

      <ul class="navbar__links" id="navbar-links">
        <li><a href="#hero" class="navbar__link active" id="nav-home">Home</a></li>
        <li><a href="#mission" class="navbar__link" id="nav-about">About</a></li>
        <li><a href="#news" class="navbar__link" id="nav-news">News</a></li>
        <li><a href="#stories" class="navbar__link" id="nav-stories">Stories</a></li>
      </ul>

      <div class="navbar__actions" id="navbar-actions">
        <?php if (!empty($isLoggedIn)): ?>
          <div class="navbar__profile">
            <div class="navbar__profile-avatar" title="<?= htmlspecialchars($userName ?? 'User') ?>">
              <?= htmlspecialchars(strtoupper(substr($userName ?? 'U', 0, 1))) ?>
            </div>
            <span class="navbar__profile-name"><?= htmlspecialchars($userName ?? 'User') ?></span>
            <a href="<?=ROOT?>/auth/logout" class="btn btn--outline-danger btn--sm" id="btn-logout">Logout</a>
          </div>
        <?php else: ?>
          <a href="<?=ROOT?>/auth/signup" class="btn btn--primary" id="btn-signup">Sign Up</a>
          <a href="<?=ROOT?>/auth/signin" class="btn btn--primary" id="btn-login">Login</a>
        <?php endif; ?>
      </div>

      <button class="navbar__hamburger" id="navbar-hamburger" aria-label="Toggle navigation menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </nav>

  <!-- ========== HERO CAROUSEL ========== -->
  <section class="hero" id="hero">
    <div class="hero__carousel" id="hero-carousel">
      <!-- Slide 1 -->
      <div class="hero__slide hero__slide--active" data-slide="0">
        <div class="hero__bg">
          <img src="<?=ROOT?>/assets/images/v381_335.png" alt="Sri Lankan youth collaborating in a classroom with laptops and books">
        </div>
        <div class="hero__overlay"></div>
        <div class="hero__content">
          <span class="hero__badge">EMPOWERING YOUTH</span>
          <h1 class="hero__title">Empowering Sri Lanka's Youth,<br>Connecting Communities.</h1>
          <p class="hero__subtitle">A national platform built to streamline club management, amplify grassroots voices, and bridge the gap between local action and national impact.</p>
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="hero__slide" data-slide="1">
        <div class="hero__bg">
          <img src="<?=ROOT?>/assets/images/v271_471.png" alt="YouthNexus Pulse digital governance meeting with city skyline">
        </div>
        <div class="hero__overlay"></div>
        <div class="hero__content">
          <span class="hero__badge">DIGITAL GOVERNANCE</span>
          <h1 class="hero__title">Transparent Leadership,<br>Data-Driven Decisions.</h1>
          <p class="hero__subtitle">Our 4-tier governance model ensures every youth member from rural villages to the national board has a direct channel to policy makers.</p>
        </div>
      </div>

      <!-- Slide 3 -->
      <div class="hero__slide" data-slide="2">
        <div class="hero__bg">
          <img src="<?=ROOT?>/assets/images/v271_575.png" alt="Youth educator teaching children by the coast in Galle">
        </div>
        <div class="hero__overlay"></div>
        <div class="hero__content">
          <span class="hero__badge">GRASSROOTS IMPACT</span>
          <h1 class="hero__title">150,000+ Active Members,<br>Across the Island.</h1>
          <p class="hero__subtitle">From coastal conservation to digital literacy, our youth clubs drive meaningful change in every corner of Sri Lanka.</p>
        </div>
      </div>

      <!-- Slide 4 -->
      <div class="hero__slide" data-slide="3">
        <div class="hero__bg">
          <img src="<?=ROOT?>/assets/images/v271_583.png" alt="Young woman coding in a tech hub in Colombo">
        </div>
        <div class="hero__overlay"></div>
        <div class="hero__content">
          <span class="hero__badge">FUTURE LEADERS</span>
          <h1 class="hero__title">Join the Movement,<br>Shape the Future.</h1>
          <p class="hero__subtitle">Create or join a youth club today and become part of the largest youth leadership network in Sri Lanka.</p>
        </div>
      </div>
    </div>

    <!-- Carousel Navigation Arrows -->
    <button class="hero__arrow hero__arrow--prev" id="hero-prev" aria-label="Previous slide">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>
    <button class="hero__arrow hero__arrow--next" id="hero-next" aria-label="Next slide">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>

    <!-- Dot Indicators -->
    <div class="hero__dots" id="hero-dots">
      <button class="hero__dot hero__dot--active" data-dot="0" aria-label="Go to slide 1"></button>
      <button class="hero__dot" data-dot="1" aria-label="Go to slide 2"></button>
      <button class="hero__dot" data-dot="2" aria-label="Go to slide 3"></button>
      <button class="hero__dot" data-dot="3" aria-label="Go to slide 4"></button>
    </div>

    <div class="hero__line"></div>
  </section>

  <!-- ========== ECOSYSTEM SECTION ========== -->
  <section class="ecosystem" id="ecosystem">
    <div class="container">
      <div class="ecosystem__badge-wrapper fade-in">
        <span class="section-badge section-badge--teal">
          <span class="dot"></span>
          Sri Lanka Youth Leadership Council Ecosystem
        </span>
      </div>
      <h2 class="ecosystem__title fade-in delay-1">Empowering Sri Lanka's Youth,<br>Connecting Communities.</h2>
      <p class="ecosystem__description fade-in delay-2">YouthNexus Pulse is the definitive digital ecosystem designed to streamline club management, foster national leadership, and amplify the voices of 150,000+ active youth members across the island.</p>
      <div class="ecosystem__cta fade-in delay-3">
        <a href="<?= !empty($isLoggedIn) ? ROOT . '/registration' : ROOT . '/auth/signin' ?>" class="btn btn--primary btn--large" id="btn-create-club">
          Create Club
          <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 8H13M13 8L9 4M13 8L9 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      </div>
    </div>
  </section>

  <!-- ========== 4-TIER STRUCTURE ========== -->
  <section class="tiers" id="tiers">
    <div class="container">
      <div class="tiers__content fade-in-left">
        <h2 class="tiers__title">The Power Structure of Youth Development</h2>
        <p class="tiers__description">Our unique 4-tier governance model ensures that every youth member, from the smallest rural village to the highest national board, has a direct channel to policy makers and resources.</p>

        <div class="tier-list">
          <!-- Tier 01 — National -->
          <div class="tier-item">
            <div class="tier-item__header">
              <div class="tier-item__info">
                <span class="tier-item__badge tier-item__badge--national">National</span>
                <span class="tier-item__label">Policy &amp; Strategy</span>
              </div>
              <span class="tier-item__tier-num tier-item__tier-num--national">Tier 01</span>
            </div>
            <div class="tier-bar">
              <div class="tier-bar__fill tier-bar__fill--national" style="--bar-width: 100%"></div>
            </div>
          </div>

          <!-- Tier 02 — Zonal -->
          <div class="tier-item">
            <div class="tier-item__header">
              <div class="tier-item__info">
                <span class="tier-item__badge tier-item__badge--zonal">Zonal</span>
                <span class="tier-item__label">Regional Coordination</span>
              </div>
              <span class="tier-item__tier-num tier-item__tier-num--zonal">Tier 02</span>
            </div>
            <div class="tier-bar">
              <div class="tier-bar__fill tier-bar__fill--zonal" style="--bar-width: 75%"></div>
            </div>
          </div>

          <!-- Tier 03 — Divisional -->
          <div class="tier-item">
            <div class="tier-item__header">
              <div class="tier-item__info">
                <span class="tier-item__badge tier-item__badge--divisional">Divisional</span>
                <span class="tier-item__label">Local Implementation</span>
              </div>
              <span class="tier-item__tier-num tier-item__tier-num--divisional">Tier 03</span>
            </div>
            <div class="tier-bar">
              <div class="tier-bar__fill tier-bar__fill--divisional" style="--bar-width: 50%"></div>
            </div>
          </div>

          <!-- Tier 04 — Club -->
          <div class="tier-item">
            <div class="tier-item__header">
              <div class="tier-item__info">
                <span class="tier-item__badge tier-item__badge--club">Club</span>
                <span class="tier-item__label">Ground Level Impact</span>
              </div>
              <span class="tier-item__tier-num tier-item__tier-num--club">Tier 04</span>
            </div>
            <div class="tier-bar">
              <div class="tier-bar__fill tier-bar__fill--club" style="--bar-width: 25%"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="tiers__visual fade-in-right">
        <div class="tiers__image-card">
          <img src="<?=ROOT?>/assets/images/v271_471.png" alt="YouthNexus Pulse digital governance meeting with city skyline">
        </div>
        <div class="tiers__stat">
          <div class="tiers__stat-value">98%</div>
          <div class="tiers__stat-label">Synchronization across all<br>four tiers of leadership.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== MISSION & VISION ========== -->
  <section class="mission" id="mission">
    <div class="container">
      <div class="mission__badge-wrapper fade-in">
        <span class="section-badge section-badge--blue">
          <span class="dot"></span>
          Official National Platform
        </span>
      </div>
      <h2 class="mission__title fade-in delay-1">Empowering the Next Generation of Sri Lankan Leaders.</h2>
      <p class="mission__description fade-in delay-2">YouthNexus Pulse is the centralized nervous system for youth governance, connecting local clubs to national authority with transparent, data-driven leadership.</p>

      <div class="mission__cards">
        <!-- Mission Card -->
        <div class="mission-card fade-in delay-2">
          <div class="mission-card__icon mission-card__icon--mission">
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="10" cy="10" r="8" stroke="var(--navy)" stroke-width="2"/>
              <circle cx="10" cy="10" r="3" fill="var(--navy)"/>
            </svg>
          </div>
          <h3 class="mission-card__title">Our Mission</h3>
          <p class="mission-card__text">To cultivate a robust ecosystem where every young Sri Lankan has the tools, the network, and the official mandate to lead, innovate, and contribute to national development through structured governance.</p>
        </div>

        <!-- Vision Card -->
        <div class="mission-card fade-in delay-3">
          <div class="mission-card__icon mission-card__icon--vision">
            <svg width="22" height="15" viewBox="0 0 22 15" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M11 1C5.5 1 1 7.5 1 7.5C1 7.5 5.5 14 11 14C16.5 14 21 7.5 21 7.5C21 7.5 16.5 1 11 1Z" stroke="var(--navy)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="11" cy="7.5" r="3" stroke="var(--navy)" stroke-width="2"/>
            </svg>
          </div>
          <h3 class="mission-card__title">Our Vision</h3>
          <p class="mission-card__text">A future where youth leadership is seamlessly integrated into the national fabric, powered by technology that bridges the gap between grassroots initiatives and high-level policy making.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== NEWS SECTION ========== -->
  <section class="news" id="news">
    <div class="container">
      <div class="news__main">
        <div class="news__header fade-in">
          <h2 class="news__title">Latest National Updates</h2>
        </div>

        <div class="news__cards">
          <!-- News Card 1 -->
          <article class="news-card fade-in delay-1" id="news-card-1">
            <div class="news-card__image">
              <span class="news-card__badge news-card__badge--national">National</span>
              <img src="<?=ROOT?>/assets/images/v271_513.png" alt="Digital analytics dashboards showing platform adoption metrics">
            </div>
            <div class="news-card__body">
              <h3 class="news-card__headline">Digital Literacy Campaign Reaches 100,000 Milestone</h3>
              <p class="news-card__excerpt">The Ministry of Youth Affairs reports a significant surge in digital platform adoption among rural…</p>
            </div>
          </article>

          <!-- News Card 2 -->
          <article class="news-card fade-in delay-2" id="news-card-2">
            <div class="news-card__image">
              <span class="news-card__badge news-card__badge--zonal">Zonal</span>
              <img src="<?=ROOT?>/assets/images/v271_523.png" alt="Mangrove restoration along the Southern coast of Sri Lanka">
            </div>
            <div class="news-card__body">
              <h3 class="news-card__headline">Coastal Conservation: Southern Zone Initiative</h3>
              <p class="news-card__excerpt">New mangrove restoration projects have been initiated across the Southern coast, led by regional youth clubs.</p>
            </div>
          </article>
        </div>
      </div>

      <!-- Trending Topics Sidebar -->
      <aside class="news__sidebar fade-in delay-3" id="trending-topics">
        <div class="sidebar__header">
          <div class="sidebar__icon"></div>
          <h3 class="sidebar__title">Trending Topics</h3>
        </div>

        <div class="trending-list">
          <div class="trending-item">
            <span class="trending-item__number">01</span>
            <div class="trending-item__content">
              <span class="trending-item__tag">#YouthEmpowermentSL</span>
              <span class="trending-item__meta">2.4k interactions this week</span>
            </div>
          </div>
          <div class="trending-item">
            <span class="trending-item__number">02</span>
            <div class="trending-item__content">
              <span class="trending-item__tag">#GreenLankaProject</span>
              <span class="trending-item__meta">1.8k interactions this week</span>
            </div>
          </div>
          <div class="trending-item">
            <span class="trending-item__number">03</span>
            <div class="trending-item__content">
              <span class="trending-item__tag">#DigitalFrontiers</span>
              <span class="trending-item__meta">1.2k interactions this week</span>
            </div>
          </div>
        </div>

        <a href="#" class="sidebar__cta" id="btn-view-all-news">View All News</a>
      </aside>
    </div>
  </section>

  <!-- ========== STORIES SECTION ========== -->
  <section class="stories" id="stories">
    <div class="container">
      <div class="stories__header fade-in">
        <div class="stories__header-left">
          <span class="stories__label">EXCELLENCE</span>
          <h2 class="stories__title">Voices from the Field</h2>
        </div>
        <a href="#" class="stories__view-all" id="btn-view-all-stories">
          View All Stories
          <span class="stories__view-all-arrow">›</span>
        </a>
      </div>

      <div class="stories__grid">
        <!-- Story 1 -->
        <article class="story-card fade-in delay-1" id="story-card-1">
          <div class="story-card__image">
            <img src="<?=ROOT?>/assets/images/v271_575.png" alt="Youth educator teaching children by the coast in Galle">
          </div>
          <div class="story-card__overlay">
            <div class="story-card__info">
              <span class="story-card__badge story-card__badge--divisional">DIVISIONAL</span>
              <h3 class="story-card__headline">Navigating the Tides of Education</h3>
            </div>
          </div>
        </article>

        <!-- Story 2 -->
        <article class="story-card fade-in delay-2" id="story-card-2">
          <div class="story-card__image">
            <img src="<?=ROOT?>/assets/images/v271_583.png" alt="Young woman coding in a tech hub in Colombo">
          </div>
          <div class="story-card__overlay">
            <div class="story-card__info">
              <span class="story-card__badge story-card__badge--zonal">ZONAL</span>
              <h3 class="story-card__headline">Coding the Future of Governance</h3>
            </div>
          </div>
        </article>

        <!-- Story 3 -->
        <article class="story-card fade-in delay-3" id="story-card-3">
          <div class="story-card__image">
            <img src="<?=ROOT?>/assets/images/v271_591.png" alt="Young woman in front of traditional Sri Lankan mural in Jaffna">
          </div>
          <div class="story-card__overlay">
            <div class="story-card__info">
              <span class="story-card__badge story-card__badge--national">NATIONAL</span>
              <h3 class="story-card__headline">Art as a Bridge to Reconciliation</h3>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>

  <!-- ========== FOOTER ========== -->
  <footer class="footer" id="footer">
    <div class="container">
      <div class="footer__main">
        <div class="footer__brand">
          <h4 class="footer__brand-name">YouthNexus Pulse</h4>
          <p class="footer__brand-desc">The official ecosystem of the Sri Lanka Youth Leadership Council. Empowering the next generation through digital transparency.</p>
        </div>

        <div class="footer__links-group">
          <div class="footer__links-col">
            <p class="footer__links-heading">QUICK LINKS</p>
            <ul class="footer__links-list">
              <li><a href="#hero" class="footer__link">Home</a></li>
              <li><a href="#mission" class="footer__link">About</a></li>
              <li><a href="#news" class="footer__link">News</a></li>
              <li><a href="#stories" class="footer__link">Stories</a></li>
            </ul>
          </div>
          <div class="footer__links-col">
            <p class="footer__links-heading">LEGAL</p>
            <ul class="footer__links-list">
              <li><a href="<?= ROOT ?>/privacy" class="footer__link">Privacy Policy</a></li>
              <li><a href="<?= ROOT ?>/terms" class="footer__link">Terms of Service</a></li>
              <li><a href="https://mail.google.com/mail/?view=cm&fs=1&to=governance@youthnexus.gov.lk" target="_blank" class="footer__link">Contact Support</a></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="footer__bottom">
        <p class="footer__copyright">© 2024 YouthNexus Pulse. Sri Lanka Youth Leadership Council.</p>
        <div class="footer__socials">
          <a href="#" class="footer__social-link" id="social-facebook">Facebook</a>
          <a href="#" class="footer__social-link" id="social-twitter">Twitter</a>
          <a href="#" class="footer__social-link" id="social-linkedin">LinkedIn</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- JavaScript -->
  <script src="<?=ROOT?>/assets/js/landing.js"></script>
</body>
</html>