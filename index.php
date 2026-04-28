<?php
/**
 * AMBOZY GRAPHICS SOLUTIONS LTD
 * Landing Page — ambozygraphics.shop
 */

$site_name  = 'Ambozy Graphics Solutions Ltd';
$site_desc  = 'Printing · Designing · Contractors · General Supplies — Kampala, Uganda';
$site_url   = 'https://ambozygraphics.shop';
$whatsapp   = '256782187799'; // no +, no spaces
$phone_1    = '+256 782 187 799';
$phone_2    = '+256 702 371 230';
$phone_off  = '+256 392 839 447';
$email_main = 'ambozygraphics@gmail.com';
$address    = 'Plot 43 Nasser / Nkrumah Road, opposite Picfare, Kampala, Uganda';
$pobox      = 'P.O. Box 14521, Kampala';

$services = [
    ['icon' => '👕', 'title' => 'Branded Merchandise',   'items' => 'T-shirts, Polo Shirts, Caps, Aprons, Fleece, Overalls, Bags'],
    ['icon' => '🎁', 'title' => 'Branded Giveaways',     'items' => 'Keyrings, Mugs, Pens, USB Drives, Umbrellas, Wristbands, Watches'],
    ['icon' => '📖', 'title' => 'Books & Magazines',     'items' => 'Magazines, Invite Cards, Certificates, Business Cards, Newsletters'],
    ['icon' => '📋', 'title' => 'Stationery',            'items' => 'Letterheads, Envelopes, Corporate & Computer Stationery'],
    ['icon' => '📣', 'title' => 'Marketing Materials',   'items' => 'Posters, Brochures, Banners, Calendars, Diaries, Car Branding'],
    ['icon' => '🪟', 'title' => 'Signage & Signs',       'items' => 'Neon, Illuminated, Light Boxes, Acrylic, Pull-ups, Backdrops'],
    ['icon' => '🛒', 'title' => 'Point of Sale',         'items' => 'Wobblers, Shelf Strips, Danglers, POS Displays'],
    ['icon' => '📦', 'title' => 'Packaging Solutions',   'items' => 'Product Labels, Shopping Bags, Kraft Paper, Boxes, Paper Cups'],
    ['icon' => '🏆', 'title' => 'Awards & Plaques',      'items' => 'Crystal Awards, Wooden Plaques, Trophies, Desk Sign Holders'],
    ['icon' => '📡', 'title' => 'Outdoor Advertising',   'items' => 'Tents, Billboards, Pavement Signs, Pull-up Banners, Light Boxes'],
];

$clients = [
    'Min. of Trade & Industry','Min. of Water & Environment','Inspectorate of Govt.',
    'Uganda Investment Authority','ChildFund International','NCDC','Marie Stopes Uganda',
    'AFENET','Finance Trust Bank','Kyambogo University','MSC','ERAM (U) Ltd',
    'Semliki Dairy','Dooba Enterprises','Embassy of Eritrea','InterAid Uganda',
    'LACCODEF','CEHURD','PACE','Private Sector Foundation UG',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $site_name ?> — Printing, Branding & Promotional Solutions</title>
  <meta name="description" content="<?= $site_desc ?>">
  <meta property="og:title" content="<?= $site_name ?>">
  <meta property="og:description" content="<?= $site_desc ?>">
  <meta property="og:url" content="<?= $site_url ?>">
  <meta property="og:type" content="website">
  <link rel="icon" type="image/png" href="assets/images/logo_main.png">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<!-- ============================================================
     NAV
============================================================ -->
<nav class="nav" id="nav">
  <a href="#" class="nav__logo">
    <img src="assets/images/logo_main.png" alt="Ambozy Graphics Solutions Ltd">
  </a>
  <ul class="nav__links">
    <li><a href="#about">About</a></li>
    <li><a href="#services">Services</a></li>
    <li><a href="#clients">Clients</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <div class="nav__cta">
    <a href="#contact" class="btn btn-primary">Get a Quote</a>
  </div>
  <button class="nav__hamburger" id="hamburger" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- Mobile Nav -->
<nav class="nav__mobile" id="mobileNav">
  <a href="#about">About</a>
  <a href="#services">Services</a>
  <a href="#clients">Clients</a>
  <a href="#contact">Get a Quote</a>
</nav>

<!-- ============================================================
     HERO
============================================================ -->
<section class="hero" id="home">
  <div class="hero__bg">
    <!-- Compass star SVG background motif -->
    <svg class="hero__star-bg" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
      <path fill="#F15E24" d="M100 10 L115 85 L190 100 L115 115 L100 190 L85 115 L10 100 L85 85 Z"/>
      <circle cx="100" cy="100" r="90" fill="none" stroke="#F15E24" stroke-width="2"/>
      <circle cx="100" cy="100" r="70" fill="none" stroke="#F15E24" stroke-width="1"/>
    </svg>
  </div>

  <div class="hero__content">
    <div class="hero__left">
      <div class="hero__eyebrow">Est. 2010 — Kampala, Uganda</div>
      <h1 class="hero__title">
        <span class="line-1">We Print.</span><br>
        <span class="line-orange">We Brand.</span><br>
        <span class="line-outline">We Deliver.</span>
      </h1>
      <p class="hero__body">
        From bold branded merchandise to stunning outdoor signage — Ambozy Graphics Solutions brings your brand to life with premium printing, creative design, and end-to-end supply.
      </p>
      <div class="hero__actions">
        <a href="#contact" class="btn btn-primary">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          Get a Quote
        </a>
        <a href="#services" class="btn btn-outline">
          View Services
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
      </div>
      <div class="hero__services-strip">
        <span class="hero__strip-label">We do</span>
        <div class="hero__strip-items">
          <span class="hero__strip-item">Printing</span>
          <span class="hero__strip-item">Designing</span>
          <span class="hero__strip-item">Branding</span>
          <span class="hero__strip-item">Supply</span>
        </div>
      </div>
    </div>

    <div class="hero__right">
      <div class="hero__logo-showcase">
        <div class="hero__logo-ring">
          <div class="hero__logo-inner">
            <img src="assets/images/logo_main.png" alt="Ambozy Graphics">
          </div>
        </div>
        <div class="hero__stat">
          <div class="hero__stat-num" data-target="14" data-suffix="+">0+</div>
          <div class="hero__stat-label">Years Active</div>
        </div>
        <div class="hero__stat">
          <div class="hero__stat-num" data-target="200" data-suffix="+">0+</div>
          <div class="hero__stat-label">Happy Clients</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     ABOUT
============================================================ -->
<section class="section about" id="about">
  <div class="container">
    <div class="about__grid">
      <div class="about__left reveal">
        <div class="tag">Who We Are</div>
        <h2 class="section-title">Built on <span>Quality</span><br>& Creativity</h2>
        <p class="section-sub">Incorporated in 2010, Ambozy Graphics Solutions Ltd is Kampala's trusted partner for printing, branding, and promotional services — delivering individualized solutions that add real value.</p>

        <div class="about__vm">
          <div class="about__vm-card reveal reveal-delay-1">
            <div class="about__vm-label">Our Vision</div>
            <p class="about__vm-text">To maintain and be the acknowledged leader in designing, printing, branding and promotional services through consistent improvement of quality.</p>
          </div>
          <div class="about__vm-card reveal reveal-delay-2">
            <div class="about__vm-label">Our Mission</div>
            <p class="about__vm-text">To provide exceptional designing, printing, branding and promotional services — delivering individualized solutions that add value to all our stakeholders.</p>
          </div>
        </div>
      </div>

      <div class="about__right reveal reveal-delay-1">
        <div class="about__values-title">Our Core Values</div>
        <div class="about__values">
          <?php
          $values = [
            ['icon'=>'⭐','name'=>'Service'],
            ['icon'=>'💎','name'=>'Premium Quality'],
            ['icon'=>'🤝','name'=>'Professionalism'],
            ['icon'=>'💡','name'=>'Exceptional Value'],
            ['icon'=>'🛡️','name'=>'Integrity'],
            ['icon'=>'🎨','name'=>'Creativity'],
            ['icon'=>'👥','name'=>'Team Work'],
          ];
          foreach ($values as $v): ?>
          <div class="about__value">
            <div class="about__value-icon"><?= $v['icon'] ?></div>
            <span class="about__value-name"><?= $v['name'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="about__founded">
          <div class="about__founded-year">2010</div>
          <div>
            <div class="about__founded-text">Founded in Kampala</div>
            <div class="about__founded-text" style="opacity:0.6;font-size:0.78rem;margin-top:4px;">Plot 43 Nasser / Nkrumah Road</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     SERVICES
============================================================ -->
<section class="section services" id="services">
  <div class="container">
    <div class="services__header">
      <div>
        <div class="tag reveal">What We Do</div>
        <h2 class="section-title reveal">Our <span>Services</span></h2>
      </div>
      <p class="section-sub reveal" style="text-align:right">
        From a single business card to a full outdoor branding campaign — if you can imagine it, we can print it, brand it, and supply it.
      </p>
    </div>

    <div class="services__grid">
      <?php foreach ($services as $i => $s): ?>
      <div class="service-card reveal" style="transition-delay: <?= ($i % 4) * 0.08 ?>s">
        <div class="service-card__num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
        <span class="service-card__icon"><?= $s['icon'] ?></span>
        <div class="service-card__title"><?= $s['title'] ?></div>
        <div class="service-card__items"><?= $s['items'] ?></div>
        <div class="service-card__arrow">→</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     CLIENTS
============================================================ -->
<section class="section clients" id="clients">
  <div class="container">
    <div class="clients__header reveal">
      <div class="tag">Trusted By</div>
      <h2 class="section-title">Our <span>Clients</span></h2>
      <p class="section-sub" style="margin:16px auto 0;text-align:center">
        We've served government ministries, international NGOs, universities, and businesses across Uganda and beyond.
      </p>
    </div>
  </div>

  <!-- Infinite scroll ticker -->
  <div class="clients__track-wrap">
    <div class="clients__track">
      <?php
        // Duplicate for seamless loop
        $all = array_merge($clients, $clients);
        foreach ($all as $c): ?>
      <div class="client-badge"><?= htmlspecialchars($c) ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="container">
    <div class="clients__count-row">
      <div class="clients__count reveal">
        <div class="clients__count-num" data-target="14" data-suffix="+">0+</div>
        <div class="clients__count-label">Years in Business</div>
      </div>
      <div class="clients__count reveal reveal-delay-1">
        <div class="clients__count-num" data-target="20" data-suffix="+">0+</div>
        <div class="clients__count-label">Notable Clients</div>
      </div>
      <div class="clients__count reveal reveal-delay-2">
        <div class="clients__count-num" data-target="10" data-suffix="">0</div>
        <div class="clients__count-label">Service Categories</div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     QUOTE / CONTACT
============================================================ -->
<section class="section quote" id="contact">
  <div class="container">
    <div class="quote__wrap">
      <div class="quote__left reveal">
        <div class="tag">Reach Out</div>
        <h2 class="section-title">Request a <span>Quote</span></h2>
        <p class="section-sub">Tell us about your project and we'll get back to you within 24 hours with a custom quote.</p>

        <div class="quote__contact-items">
          <div class="quote__contact-item">
            <div class="quote__contact-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.09 1.18 2 2 0 012.09 0h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.91 7.09a16 16 0 006 6l.45-.45a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.9v2.02z"/></svg>
            </div>
            <div>
              <div class="quote__contact-label">Phone / WhatsApp</div>
              <div class="quote__contact-value"><?= $phone_1 ?> &nbsp;|&nbsp; <?= $phone_2 ?></div>
            </div>
          </div>
          <div class="quote__contact-item">
            <div class="quote__contact-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
              <div class="quote__contact-label">Email</div>
              <div class="quote__contact-value"><?= $email_main ?></div>
            </div>
          </div>
          <div class="quote__contact-item">
            <div class="quote__contact-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <div class="quote__contact-label">Location</div>
              <div class="quote__contact-value"><?= $address ?></div>
            </div>
          </div>
          <div class="quote__contact-item">
            <div class="quote__contact-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            </div>
            <div>
              <div class="quote__contact-label">Office</div>
              <div class="quote__contact-value"><?= $phone_off ?> &nbsp;|&nbsp; <?= $pobox ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="quote__right reveal reveal-delay-2">
        <form class="quote__form" id="quoteForm" novalidate>
          <!-- Honeypot -->
          <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off">

          <div class="form__title">Tell Us About Your Project</div>
          <div class="form__sub">We'll respond within 24 hours with a custom quote.</div>

          <div class="form__row">
            <div class="form__group">
              <label class="form__label" for="name">Full Name *</label>
              <input class="form__input" type="text" id="name" name="name" placeholder="John Doe" required>
            </div>
            <div class="form__group">
              <label class="form__label" for="email">Email Address *</label>
              <input class="form__input" type="email" id="email" name="email" placeholder="john@company.com" required>
            </div>
          </div>

          <div class="form__row">
            <div class="form__group">
              <label class="form__label" for="phone">Phone / WhatsApp</label>
              <input class="form__input" type="tel" id="phone" name="phone" placeholder="+256 700 000 000">
            </div>
            <div class="form__group">
              <label class="form__label" for="company">Company / Organisation</label>
              <input class="form__input" type="text" id="company" name="company" placeholder="Your Company">
            </div>
          </div>

          <div class="form__row">
            <div class="form__group">
              <label class="form__label" for="service">Service Needed</label>
              <select class="form__select" id="service" name="service">
                <option value="">— Select a service —</option>
                <?php foreach ($services as $s): ?>
                <option value="<?= htmlspecialchars($s['title']) ?>"><?= htmlspecialchars($s['title']) ?></option>
                <?php endforeach; ?>
                <option value="Multiple Services">Multiple Services</option>
                <option value="Not sure yet">Not sure yet</option>
              </select>
            </div>
            <div class="form__group">
              <label class="form__label" for="budget">Estimated Budget</label>
              <select class="form__select" id="budget" name="budget">
                <option value="">— Select range —</option>
                <option value="Under 500K UGX">Under 500,000 UGX</option>
                <option value="500K–2M UGX">500,000 – 2,000,000 UGX</option>
                <option value="2M–10M UGX">2,000,000 – 10,000,000 UGX</option>
                <option value="Over 10M UGX">Over 10,000,000 UGX</option>
                <option value="To be discussed">To be discussed</option>
              </select>
            </div>
          </div>

          <div class="form__group">
            <label class="form__label" for="message">Project Details *</label>
            <textarea class="form__textarea" id="message" name="message" placeholder="Describe your project — quantities, sizes, deadline, special requirements…" required></textarea>
          </div>

          <button type="submit" class="btn btn-primary form__submit">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            Send Inquiry
          </button>

          <div id="formSuccess" class="form__success">✓ Inquiry sent! We'll contact you within 24 hours.</div>
          <div id="formError" class="form__error"></div>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     FOOTER
============================================================ -->
<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        <img src="assets/images/logo_main.png" alt="Ambozy Graphics Solutions Ltd" class="footer__logo" style="height:40px;filter:brightness(0) invert(1);margin-bottom:16px">
        <p class="footer__desc">Your trusted partner for printing, branding, and promotional solutions in Uganda since 2010.</p>
        <div class="footer__social">
          <!-- Social icons — links to be added later -->
          <a href="#" title="Facebook" aria-label="Facebook">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
          </a>
          <a href="#" title="Instagram" aria-label="Instagram">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
          </a>
          <a href="#" title="Twitter/X" aria-label="Twitter">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
          </a>
          <a href="https://wa.me/<?= $whatsapp ?>" title="WhatsApp" aria-label="WhatsApp">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </a>
        </div>
      </div>

      <div>
        <div class="footer__col-title">Services</div>
        <ul class="footer__links">
          <li><a href="#services">Branded Merchandise</a></li>
          <li><a href="#services">Marketing Materials</a></li>
          <li><a href="#services">Signage & Signs</a></li>
          <li><a href="#services">Packaging Solutions</a></li>
          <li><a href="#services">Outdoor Advertising</a></li>
          <li><a href="#services">Awards & Plaques</a></li>
        </ul>
      </div>

      <div>
        <div class="footer__col-title">Quick Links</div>
        <ul class="footer__links">
          <li><a href="#home">Home</a></li>
          <li><a href="#about">About Us</a></li>
          <li><a href="#services">Our Services</a></li>
          <li><a href="#clients">Our Clients</a></li>
          <li><a href="#contact">Request Quote</a></li>
        </ul>
      </div>

      <div>
        <div class="footer__col-title">Get in Touch</div>
        <div class="footer__contact-item">
          <span class="footer__contact-icon">📍</span>
          <span class="footer__contact-text">Plot 43 Nasser/Nkrumah Rd, opposite Picfare, Kampala</span>
        </div>
        <div class="footer__contact-item">
          <span class="footer__contact-icon">📞</span>
          <span class="footer__contact-text"><?= $phone_1 ?><br><?= $phone_2 ?></span>
        </div>
        <div class="footer__contact-item">
          <span class="footer__contact-icon">✉️</span>
          <span class="footer__contact-text"><?= $email_main ?></span>
        </div>
        <div class="footer__contact-item">
          <span class="footer__contact-icon">📮</span>
          <span class="footer__contact-text"><?= $pobox ?></span>
        </div>
      </div>
    </div>

    <div class="footer__bottom">
      <div class="footer__copy">
        © <?= date('Y') ?> <span>Ambozy Graphics Solutions Ltd</span>. All rights reserved.
      </div>
      <div class="footer__strip">
        Printing <span>◆</span> Designing <span>◆</span> Contractors <span>◆</span> General Supplies
      </div>
    </div>
  </div>
</footer>

<!-- ============================================================
     WHATSAPP BUTTON
============================================================ -->
<a class="wa-btn" href="https://wa.me/<?= $whatsapp ?>?text=Hello%20Ambozy%20Graphics%21%20I%20would%20like%20to%20request%20a%20quote." target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
  </svg>
  <span class="wa-tooltip">Chat with us</span>
</a>

<script src="assets/js/main.js"></script>
</body>
</html>
