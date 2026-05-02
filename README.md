# Ambozy Graphics Solutions Ltd — Website Project

**Client:** Ambozy Graphics Solutions Ltd  
**Domain:** [ambozygraphics.com](http://ambozygraphics.com)  
**Deployment:** FTP  
**Stack:** PHP (landing) + PHP CMS (admin)  
**Built by:** Thirdsan Enterprises / SEVIA

---

## Company Intelligence

### Identity
- **Full Name:** Ambozy Graphics Solutions Ltd
- **Incorporated:** 2010 (Registered Company, Uganda)
- **Location:** Plot 43 Nasser / Nkrumah Road, opposite Picfare, Kampala
- **P.O. Box:** 74521 (profile cover) / 14521 (back page) — use **14521** (back page is more recent)
- **Office:** +256-392839447
- **Tel:** +256-782187799 / +256-702371230
- **Email:** ambozygraphics@gmail.com | ambozygraphics@yahoo.com | info@ambozygroup

### Brand Colors (official from logos.pdf)
| Role | CMYK | RGB | HEX |
|------|------|-----|-----|
| Primary Orange | 0 / 78.52 / 98.05 / 0 | 241, 94, 36 | `#F15E24` |
| Near-Black | 74.61 / 67.58 / 66.8 / 89.84 | 1, 1, 1 | `#010101` |
| White | — | 255, 255, 255 | `#FFFFFF` |

> Accent suggestion for web: a warm mid-grey `#2A2A2A` for backgrounds, and `#F15E24` as the hero/CTA color.

### Tagline / Services Strip
`PRINTING | DESIGNING | CONTRACTORS | GENERAL SUPPLIES`

### Vision
> To maintain and be the acknowledged leader in designing, printing, branding and promotional services through consistent improvement of quality.

### Mission
> To provide exceptional designing, printing, branding and promotional services and to deliver individualized solutions to our clients in a way that adds value to all our stake holders.

### Core Values
Service · Premium Quality · Professionalism · Exceptional Value · Integrity · Creativity & Innovation · Team Work

---

## Service Categories (from profile)

### 1. Branded Merchandise
T-shirts (Round-neck, Polo, Customised), Shirts, Caps/Hats, Aprons, Fleece, Overalls, Bags

### 2. Branded Giveaways
Keyrings, Thermal Mugs, Ceramics, Clocks, Pens, Bags, USB Drives, Mouse Mats, Water Bottles, Umbrellas, Shoulder/Back Packs, Awards, Wristbands, Watches, Pen Sets, Games

### 3. Books & Magazines
Magazines, Invitation Cards, Certificates, Presentations, Visiting Cards, Greeting Cards, Textbooks, Annual Reports, Newsletters

### 4. Stationery
Corporate & Computer Stationery, Letter Heads, Envelopes, Customised Books

### 5. Marketing Materials
Posters, Brochures, Lanyards, Calendars, Car Branding, Leaflets, Company Profiles, Banners, Catalogues, File Folders, Diaries & Notebooks

### 6. Signage
Neon Signs, Illuminated Signs, Light Boxes, Pavement Signs, Wall Signs, Pull-ups, L/Tear Drops, Backdrops, Acrylic Signs

### 7. Point of Sale
Wobblers, Shelf Strips, Danglers, POS Displays

### 8. Packaging
Product Labels, Inner Packaging, Shopping Bags, Kraft Paper, Craft Packaging, Boxes, Paper Cups

### 9. Outdoor Advertising
Event Tents, Banners, Billboards, Pavement Signs, Pull-up Banners, Light Boxes

### 10. Promotional Awards
Crystal Awards, Wooden Plaques, Custom-made Trophies, Desk & Wall Sign Holders

---

## Notable Clients (from profile)
Ministry of Trade Industry & Co-operatives · Ministry of Water & Environment · Inspectorate of Government · Uganda Investment Authority · ChildFund International · NCDC · Marie Stopes Uganda · AFENET · Semliki Dairy · ERAM (U) Ltd · Finance Trust Bank · Kyambogo University · MSC · DEL (Dooba Enterprises) · Embassy of Eritrea · InterAid Uganda · LACCODEF · CEHURD · PACE · BMCT · Educate · Busia Area Communities Federation · Private Sector Foundation Uganda

---

## Project Phases

### Phase 1 — Repo & Strategy (Current)
- [x] Initialize git repo
- [x] Copy brand assets
- [x] Write README / project intelligence
- [ ] Finalize folder structure
- [ ] Set up `.gitignore`

### Phase 2 — Landing Page (PHP)
**Goal:** Stunning, professional single-page or multi-section landing page.

**Sections to build:**
1. **Hero** — Full-width, brand colors, logo, tagline, CTA ("Get a Quote" / "View Services")
2. **About** — Vision, Mission, 2010 founding, values (icon grid)
3. **Services** — Card grid of 10 service categories with icons/imagery
4. **Clients** — Logo wall of notable clients
5. **Gallery / Portfolio** — Sample work (use profile imagery)
6. **Contact** — Address, phone, email, embedded map (Nasser/Nkrumah, Kampala), PHP contact form
7. **Footer** — Brand strip, social links, copyright

**Design Language:**
- Font pairing: Heavy sans-serif for headings (e.g. Montserrat Black / Anton) + clean body (Inter/Open Sans)
- Color: `#F15E24` orange as primary CTA/accent, `#010101` near-black for structure, white space for breathing room
- Aesthetic: Bold, graphic, energetic — reflects a design/print company
- The logo mark (4-pointed star in orange ring) should be prominent
- Responsive: mobile-first

**Tech:**
- Pure PHP (no framework)
- CSS custom properties for the brand system
- Vanilla JS for interactions (smooth scroll, mobile nav, form validation)
- PHP mailer for contact form

### Phase 3 — CMS / Admin Panel
**Goal:** Client-facing CMS for Ambozy staff to manage content and client inquiries.

**Modules planned:**
- `admin/login.php` — Auth (session-based, hashed passwords)
- `admin/dashboard.php` — Overview stats
- `admin/inquiries.php` — View/respond to contact form submissions (stored in MySQL/SQLite)
- `admin/services.php` — CRUD for service categories
- `admin/gallery.php` — Upload / manage portfolio images
- `admin/clients.php` — Manage client logo wall
- `admin/settings.php` — Site meta, contact info, social links

**DB:** MySQL (compatible with typical shared hosting)

### Phase 4 — FTP Deployment
- Build production ZIP
- FTP deploy to `ambozygraphics.com`
- Test all PHP forms and DB connections on live server

---

## Folder Structure

```
ambozy-website/
├── index.php                  # Landing page entry
├── contact-handler.php        # Form submission processor
├── config.php                 # DB credentials, site config
├── .htaccess                  # Rewrites, security headers
├── .gitignore
├── README.md
│
├── assets/
│   ├── css/
│   │   ├── main.css           # Brand system + landing styles
│   │   └── admin.css          # Admin panel styles
│   ├── js/
│   │   ├── main.js            # Landing page interactions
│   │   └── admin.js           # Admin panel scripts
│   ├── images/
│   │   ├── logo_main.png      # PRIMARY LOGO (use this)
│   │   ├── Logo.png           # Alternate
│   │   ├── logos.jpeg         # Logo variants reference
│   │   └── gallery/           # Portfolio images
│   └── fonts/                 # Self-hosted fonts if needed
│
├── includes/
│   ├── db.php                 # PDO connection
│   ├── header.php             # Site-wide head + nav
│   ├── footer.php             # Site-wide footer
│   └── mailer.php             # PHPMailer wrapper
│
├── admin/
│   ├── index.php              # Redirect to dashboard/login
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── inquiries.php
│   ├── services.php
│   ├── gallery.php
│   ├── clients.php
│   └── settings.php
│
└── public/                    # Publicly accessible uploads
    └── uploads/
```

---

## Design Inspiration Notes
- The logo uses a **bracket frame** motif (top-left, bottom-right corners) — this can be echoed in section dividers and card borders on the web
- The **4-pointed compass star** icon in the logo is distinctive — use as a decorative motif, favicon, and section bullet
- Bold slab/serif mixed with italic lowercase in "GRAPHICS SOLUTIONS LTD" — the brand has energy; the web design should too
- Reference: the black-background logo variant reads exceptionally well at large scale for hero sections

---

## Decisions / Open Questions
- [ ] Confirm PHP version on hosting (for modern syntax support)
- [ ] Confirm if MySQL is available or SQLite preferred for CMS
- [ ] Social media links for Ambozy (Instagram, Facebook, etc.)
- [ ] Does client want a WhatsApp chat button? (common for Ugandan businesses)
- [ ] Preferred quote/inquiry flow — form only, or WhatsApp CTA too?
- [ ] Do we need multilingual support? (English only assumed)
