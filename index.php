<?php
/**
 * AMBOZY GRAPHICS SOLUTIONS LTD
 * Landing Page — ambozygraphics.com
 */

$site_name  = 'Ambozy Graphics Solutions Ltd';
$site_desc  = 'Printing · Designing · Contractors · General Supplies — Kampala, Uganda';
$site_url   = 'https://ambozygraphics.com';
$whatsapp   = '256782187799'; // no +, no spaces
$phone_1    = '+256 782 187 799';
$phone_2    = '+256 702 371 230';
$phone_off  = '+256 392 839 447';
$email_main = 'info@ambozygraphics.com';
$address    = 'Plot 1314 Church Road, Buye, Ntinda, Kampala, Uganda';
$pobox      = 'P.O. Box 14521, Kampala';

$services = [
    ['fa' => 'fa-shirt',        'title' => 'Branded Merchandise',  'items' => 'T-shirts, Polo Shirts, Caps, Aprons, Fleece, Overalls, Bags'],
    ['fa' => 'fa-gift',         'title' => 'Branded Giveaways',    'items' => 'Keyrings, Mugs, Pens, USB Drives, Umbrellas, Wristbands, Watches'],
    ['fa' => 'fa-book-open',    'title' => 'Books & Magazines',    'items' => 'Magazines, Invite Cards, Certificates, Business Cards, Newsletters'],
    ['fa' => 'fa-file-lines',   'title' => 'Stationery',           'items' => 'Letterheads, Envelopes, Corporate & Computer Stationery'],
    ['fa' => 'fa-bullhorn',     'title' => 'Marketing Materials',  'items' => 'Posters, Brochures, Banners, Calendars, Diaries, Car Branding'],
    ['fa' => 'fa-signs-post',   'title' => 'Signage & Signs',      'items' => 'Neon, Illuminated, Light Boxes, Acrylic, Pull-ups, Backdrops'],
    ['fa' => 'fa-cash-register','title' => 'Point of Sale',        'items' => 'Wobblers, Shelf Strips, Danglers, POS Displays'],
    ['fa' => 'fa-box-open',     'title' => 'Packaging Solutions',  'items' => 'Product Labels, Shopping Bags, Kraft Paper, Boxes, Paper Cups'],
    ['fa' => 'fa-trophy',       'title' => 'Awards & Plaques',     'items' => 'Crystal Awards, Wooden Plaques, Trophies, Desk Sign Holders'],
    ['fa' => 'fa-flag',         'title' => 'Outdoor Advertising',  'items' => 'Tents, Billboards, Pavement Signs, Pull-up Banners, Light Boxes'],
];

$values = [
    ['fa' => 'fa-handshake',    'name' => 'Service'],
    ['fa' => 'fa-gem',          'name' => 'Premium Quality'],
    ['fa' => 'fa-briefcase',    'name' => 'Professionalism'],
    ['fa' => 'fa-star',         'name' => 'Exceptional Value'],
    ['fa' => 'fa-shield-halved','name' => 'Integrity'],
    ['fa' => 'fa-palette',      'name' => 'Creativity'],
    ['fa' => 'fa-people-group', 'name' => 'Team Work'],
];

$client_logos = [
    ['img' => 'assets/images/clients/client-1.jpeg',  'name' => 'Min. of Trade & Industry'],
    ['img' => 'assets/images/clients/client-2.jpeg',  'name' => 'Min. of Water & Environment'],
    ['img' => 'assets/images/clients/client-3.jpeg',  'name' => 'Inspectorate of Govt.'],
    ['img' => 'assets/images/clients/client-4.jpeg',  'name' => 'Uganda Investment Authority'],
    ['img' => 'assets/images/clients/client-5.jpeg',  'name' => 'ChildFund International'],
    ['img' => 'assets/images/clients/client-6.jpeg',  'name' => 'NCDC'],
    ['img' => 'assets/images/clients/client-8.jpeg',  'name' => 'Marie Stopes Uganda'],
    ['img' => 'assets/images/clients/client-9.jpeg',  'name' => 'AFENET'],
    ['img' => 'assets/images/clients/client-10.jpeg', 'name' => 'Finance Trust Bank'],
    ['img' => 'assets/images/clients/client-11.jpeg', 'name' => 'Kyambogo University'],
    ['img' => 'assets/images/clients/client-12.jpeg', 'name' => 'MSC'],
    ['img' => 'assets/images/clients/client-13.jpeg', 'name' => 'ERAM (U) Ltd'],
    ['img' => 'assets/images/clients/client-14.jpeg', 'name' => 'Semliki Dairy'],
    ['img' => 'assets/images/clients/client-15.jpeg', 'name' => 'Dooba Enterprises'],
    ['img' => 'assets/images/clients/client-16.jpeg', 'name' => 'Embassy of Eritrea'],
    ['img' => 'assets/images/clients/client-17.jpeg', 'name' => 'InterAid Uganda'],
    ['img' => 'assets/images/clients/client-18.jpeg', 'name' => 'LACCODEF'],
    ['img' => 'assets/images/clients/client-19.jpeg', 'name' => 'CEHURD'],
    ['img' => 'assets/images/clients/client-20.jpeg', 'name' => 'PACE'],
    ['img' => 'assets/images/clients/client-21.jpeg', 'name' => 'Private Sector Foundation UG'],
    ['img' => 'assets/images/clients/client-22.jpeg', 'name' => 'Partner Organisation'],
    ['img' => 'assets/images/clients/client-23.jpeg', 'name' => 'Partner Organisation'],
    ['img' => 'assets/images/clients/client-24.jpeg', 'name' => 'Partner Organisation'],
    ['img' => 'assets/images/clients/client-25.jpeg', 'name' => 'Partner Organisation'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $site_name ?> — Printing, Branding & Promotional Solutions</title>
  <meta name="description" content="<?= $site_desc ?>">
  <meta property="og:title"       content="<?= $site_name ?>">
  <meta property="og:description" content="<?= $site_desc ?>">
  <meta property="og:url"         content="<?= $site_url ?>">
  <meta property="og:type"        content="website">

  <!-- SVG favicon -->
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  <link rel="alternate icon"      href="assets/images/favicon.svg">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer">

  <!-- Brand CSS -->
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<!-- ============================================================
     NAV
============================================================ -->
<nav class="nav" id="nav">
  <a href="#" class="nav__logo">
    <div class="logo-badge"><i class="fa-solid fa-palette"></i></div>
    <div class="logo-wordmark">
      <span class="logo-wordmark__main">Ambozy</span>
      <span class="logo-wordmark__sub">Graphics Solutions Ltd</span>
    </div>
  </a>

  <ul class="nav__links">
    <li><a href="#about">About</a></li>
    <li><a href="#services">Services</a></li>
    <li><a href="#clients">Clients</a></li>
    <li><a href="#contact">Contact</a></li>
  </ul>
  <div class="nav__cta">
    <a href="#contact" class="btn btn-primary">
      <i class="fa-solid fa-paper-plane"></i> Get a Quote
    </a>
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
  <!-- Paint blobs -->
  <div class="blobs">
    <div class="blob blob--pink"   style="width:650px;height:650px;top:-200px;left:-180px"></div>
    <div class="blob blob--cyan"   style="width:550px;height:550px;top:-80px;right:-180px"></div>
    <div class="blob blob--orange" style="width:420px;height:420px;bottom:-80px;right:18%"></div>
    <div class="blob blob--violet" style="width:280px;height:280px;bottom:15%;left:10%;opacity:0.08"></div>
  </div>

  <div class="hero__content">

    <!-- Left copy -->
    <div class="hero__left">
      <div class="hero__eyebrow">
        <i class="fa-solid fa-location-dot" style="color:var(--pink)"></i>
        Est. 2010 — Kampala, Uganda
      </div>
      <h1 class="hero__title">
        <span class="line-1">We Print.</span><br>
        <span class="line-2">We Brand.</span><br>
        <span class="line-3">We Deliver.</span>
      </h1>
      <p class="hero__body">
        From bold branded merchandise to stunning outdoor signage — Ambozy Graphics Solutions brings your brand to life with premium printing, creative design, and end-to-end supply.
      </p>
      <div class="hero__actions">
        <a href="#contact" class="btn btn-primary">
          <i class="fa-solid fa-comment-dots"></i> Get a Quote
        </a>
        <a href="#services" class="btn btn-outline">
          View Services <i class="fa-solid fa-arrow-right"></i>
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

    <!-- Right graphic composition -->
    <div class="hero__right">
      <div class="hero__art">
        <!-- Spinning arcs -->
        <div class="hero__arc"></div>
        <div class="hero__arc"></div>
        <div class="hero__arc"></div>

        <!-- Center palette icon -->
        <div class="hero__art-center">
          <i class="fa-solid fa-palette"></i>
        </div>

        <!-- Orbit bubbles: top · right · bottom · left · top-right · bottom-right -->
        <div class="hero__orb" title="Design"><i class="fa-solid fa-pen-nib"></i></div>
        <div class="hero__orb" title="Print"><i class="fa-solid fa-print"></i></div>
        <div class="hero__orb" title="Merchandise"><i class="fa-solid fa-shirt"></i></div>
        <div class="hero__orb" title="Marketing"><i class="fa-solid fa-bullhorn"></i></div>
        <div class="hero__orb" title="Awards"><i class="fa-solid fa-trophy"></i></div>
        <div class="hero__orb" title="Packaging"><i class="fa-solid fa-box-open"></i></div>

        <!-- Floating stat badges -->
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
  <!-- Paint blobs -->
  <div class="blobs">
    <div class="blob blob--violet" style="width:500px;height:500px;right:-80px;top:-100px"></div>
    <div class="blob blob--teal"   style="width:380px;height:380px;left:-80px;bottom:-60px;opacity:0.1"></div>
  </div>

  <div class="container">
    <div class="about__grid">

      <div class="about__left reveal">
        <div class="tag tag--fire">
          <i class="fa-solid fa-circle-info"></i> Who We Are
        </div>
        <h2 class="section-title">Built on <span>Quality</span><br>&amp; Creativity</h2>
        <p class="section-sub">Incorporated in 2010, Ambozy Graphics Solutions Ltd is Kampala's trusted partner for printing, branding, and promotional services — delivering individualized solutions that add real value.</p>

        <div class="about__vm">
          <div class="about__vm-card reveal reveal-delay-1">
            <div class="about__vm-label"><i class="fa-solid fa-eye"></i> &nbsp;Our Vision</div>
            <p class="about__vm-text">To maintain and be the acknowledged leader in designing, printing, branding and promotional services through consistent improvement of quality.</p>
          </div>
          <div class="about__vm-card reveal reveal-delay-2">
            <div class="about__vm-label"><i class="fa-solid fa-rocket"></i> &nbsp;Our Mission</div>
            <p class="about__vm-text">To provide exceptional designing, printing, branding and promotional services — delivering individualized solutions that add value to all our stakeholders.</p>
          </div>
        </div>
      </div>

      <div class="about__right reveal reveal-delay-1">
        <div class="about__values-title">Our Core Values</div>
        <div class="about__values">
          <?php foreach ($values as $v): ?>
          <div class="about__value">
            <div class="about__value-icon"><i class="fa-solid <?= $v['fa'] ?>"></i></div>
            <span class="about__value-name"><?= $v['name'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="about__founded">
          <div class="about__founded-year">2010</div>
          <div>
            <div class="about__founded-text">Founded in Kampala</div>
            <div class="about__founded-text" style="opacity:0.75;font-size:0.78rem;margin-top:5px;">
              <i class="fa-solid fa-location-dot" style="margin-right:5px"></i>Plot 1314 Church Road, Buye, Ntinda
            </div>
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
  <!-- Paint blobs -->
  <div class="blobs">
    <div class="blob blob--pink"  style="width:600px;height:600px;left:-180px;top:50%;transform:translateY(-50%)"></div>
    <div class="blob blob--amber" style="width:450px;height:450px;right:-120px;bottom:0"></div>
  </div>

  <div class="container">
    <div class="services__header">
      <div>
        <div class="tag tag--cool reveal">
          <i class="fa-solid fa-wand-magic-sparkles"></i> What We Do
        </div>
        <h2 class="section-title reveal">Our <span>Services</span></h2>
      </div>
      <p class="section-sub reveal" style="text-align:right">
        From a single business card to a full outdoor branding campaign — if you can imagine it, we can print it, brand it, and supply it.
      </p>
    </div>

    <div class="services__grid">
      <?php foreach ($services as $i => $s): ?>
      <div class="service-card reveal" style="transition-delay:<?= ($i % 4) * 0.07 ?>s">
        <div class="service-card__num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></div>
        <div class="service-card__icon"><i class="fa-solid <?= $s['fa'] ?>"></i></div>
        <div class="service-card__title"><?= $s['title'] ?></div>
        <div class="service-card__items"><?= $s['items'] ?></div>
        <div class="service-card__arrow"><i class="fa-solid fa-arrow-right"></i></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     CLIENTS
============================================================ -->
<section class="section clients" id="clients">
  <!-- Paint blobs -->
  <div class="blobs">
    <div class="blob blob--cyan"   style="width:500px;height:500px;right:-80px;top:-80px"></div>
    <div class="blob blob--orange" style="width:400px;height:400px;left:-80px;bottom:-80px;opacity:0.09"></div>
  </div>

  <div class="container">
    <div class="clients__header reveal">
      <div class="tag tag--violet">
        <i class="fa-solid fa-building"></i> Trusted By
      </div>
      <h2 class="section-title">Our <span>Clients</span></h2>
      <p class="section-sub" style="margin:16px auto 0;text-align:center">
        We've served government ministries, international NGOs, universities, and businesses across Uganda and beyond.
      </p>
    </div>
  </div>

  <!-- Dual-row logo marquee -->
  <div class="clients__marquee-wrap">
    <!-- Row 1 — scrolls left -->
    <div class="clients__row">
      <div class="clients__track clients__track--left">
        <?php
          $row1 = array_slice($client_logos, 0, 12);
          $row1 = array_merge($row1, $row1);
          foreach ($row1 as $c): ?>
        <div class="client-logo-card" title="<?= htmlspecialchars($c['name']) ?>">
          <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" loading="lazy">
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- Row 2 — scrolls right -->
    <div class="clients__row">
      <div class="clients__track clients__track--right">
        <?php
          $row2 = array_slice($client_logos, 12);
          $row2 = array_merge($row2, $row2);
          foreach ($row2 as $c): ?>
        <div class="client-logo-card" title="<?= htmlspecialchars($c['name']) ?>">
          <img src="<?= htmlspecialchars($c['img']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" loading="lazy">
        </div>
        <?php endforeach; ?>
      </div>
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
  <!-- Paint blobs -->
  <div class="blobs">
    <div class="blob blob--pink"  style="width:520px;height:520px;right:-100px;top:-80px"></div>
    <div class="blob blob--teal"  style="width:380px;height:380px;left:-80px;bottom:-80px;opacity:0.1"></div>
  </div>

  <div class="container">
    <div class="quote__wrap">

      <div class="quote__left reveal">
        <div class="tag tag--amber">
          <i class="fa-solid fa-envelope-open-text"></i> Reach Out
        </div>
        <h2 class="section-title">Request a <span>Quote</span></h2>
        <p class="section-sub">Tell us about your project and we'll get back to you within 24 hours with a custom quote.</p>

        <div class="quote__contact-items">
          <div class="quote__contact-item">
            <div class="quote__contact-icon"><i class="fa-solid fa-phone"></i></div>
            <div>
              <div class="quote__contact-label">Phone / WhatsApp</div>
              <div class="quote__contact-value"><?= $phone_1 ?> &nbsp;|&nbsp; <?= $phone_2 ?></div>
            </div>
          </div>
          <div class="quote__contact-item">
            <div class="quote__contact-icon"><i class="fa-solid fa-envelope"></i></div>
            <div>
              <div class="quote__contact-label">Email</div>
              <div class="quote__contact-value"><?= $email_main ?></div>
            </div>
          </div>
          <div class="quote__contact-item">
            <div class="quote__contact-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <div class="quote__contact-label">Location</div>
              <div class="quote__contact-value"><?= $address ?></div>
            </div>
          </div>
          <div class="quote__contact-item">
            <div class="quote__contact-icon"><i class="fa-solid fa-building-columns"></i></div>
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
            <textarea class="form__textarea" id="message" name="message"
                      placeholder="Describe your project — quantities, sizes, deadline, special requirements…" required></textarea>
          </div>

          <button type="submit" class="btn btn-primary form__submit">
            <i class="fa-solid fa-paper-plane"></i> Send Inquiry
          </button>

          <div id="formSuccess" class="form__success">
            <i class="fa-solid fa-circle-check"></i> Inquiry sent! We'll contact you within 24 hours.
          </div>
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
        <div class="footer__logo">
          <div class="logo-badge"><i class="fa-solid fa-palette"></i></div>
          <div class="logo-wordmark">
            <span class="logo-wordmark__main">Ambozy</span>
            <span class="logo-wordmark__sub">Graphics Solutions Ltd</span>
          </div>
        </div>
        <p class="footer__desc">Your trusted partner for printing, branding, and promotional solutions in Uganda since 2010.</p>
        <div class="footer__social">
          <a href="#" title="Facebook"  aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" title="Instagram" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" title="Twitter/X" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
          <a href="https://wa.me/<?= $whatsapp ?>" title="WhatsApp" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
      </div>

      <div>
        <div class="footer__col-title">Services</div>
        <ul class="footer__links">
          <li><a href="#services"><i class="fa-solid fa-chevron-right"></i>Branded Merchandise</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right"></i>Marketing Materials</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right"></i>Signage &amp; Signs</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right"></i>Packaging Solutions</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right"></i>Outdoor Advertising</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right"></i>Awards &amp; Plaques</a></li>
        </ul>
      </div>

      <div>
        <div class="footer__col-title">Quick Links</div>
        <ul class="footer__links">
          <li><a href="#home"><i class="fa-solid fa-chevron-right"></i>Home</a></li>
          <li><a href="#about"><i class="fa-solid fa-chevron-right"></i>About Us</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right"></i>Our Services</a></li>
          <li><a href="#clients"><i class="fa-solid fa-chevron-right"></i>Our Clients</a></li>
          <li><a href="#contact"><i class="fa-solid fa-chevron-right"></i>Request Quote</a></li>
        </ul>
      </div>

      <div>
        <div class="footer__col-title">Get in Touch</div>
        <div class="footer__contact-item">
          <span class="footer__contact-icon"><i class="fa-solid fa-location-dot"></i></span>
          <span class="footer__contact-text">Plot 1314 Church Road, Buye, Ntinda, Kampala</span>
        </div>
        <div class="footer__contact-item">
          <span class="footer__contact-icon"><i class="fa-solid fa-phone"></i></span>
          <span class="footer__contact-text"><?= $phone_1 ?><br><?= $phone_2 ?></span>
        </div>
        <div class="footer__contact-item">
          <span class="footer__contact-icon"><i class="fa-solid fa-envelope"></i></span>
          <span class="footer__contact-text"><?= $email_main ?></span>
        </div>
        <div class="footer__contact-item">
          <span class="footer__contact-icon"><i class="fa-solid fa-inbox"></i></span>
          <span class="footer__contact-text"><?= $pobox ?></span>
        </div>
      </div>

    </div>

    <div class="footer__bottom">
      <div class="footer__copy">
        &copy; <?= date('Y') ?> <span>Ambozy Graphics Solutions Ltd</span>. All rights reserved.
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
<a class="wa-btn" href="https://wa.me/<?= $whatsapp ?>?text=Hello%20Ambozy%20Graphics%21%20I%20would%20like%20to%20request%20a%20quote."
   target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
  <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
  </svg>
  <span class="wa-tooltip">Chat with us</span>
</a>

<script src="assets/js/main.js"></script>
</body>
</html>
