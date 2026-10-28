# Environet, Inc.

Website and back office for Environet, Inc., a Hawaiʻi environmental, munitions response (MEC) and construction contractor established in 1995, with offices listed in Kamuela, Honolulu, Guam and Colorado (UEI EEWLLJUH4SC9, CAGE 1NQM6). The firm's current site, environetinc.com, is a 2006 template: its footer reads "© 2006 Environet, Inc.", it has a fixed 770px layout with no viewport meta, and it still carries the Macromedia Flash scripts its banner and navigation were made with (checked 27 October 2026).

This is an unsolicited concept build for a pitch. Every fact on the site has a public source (the firm's own site, its SBA profile, USAspending.gov); see "Before launch" for what still needs the firm's confirmation.

## What it does

- **Public site.** Home, services (three divisions with every service line from the firm's site, each linked to its federal awards), past performance (21 federal awards from USAspending.gov, one page per award with agency, office, PIID, obligated amount, dates and the published description, linked to the record), about, a print-styled two-page capability statement with a downloadable PDF, contact (four offices), privacy notice (Hawaiʻi HRS 487N/487R, no SSNs collected), photo credits and a site map. The munitions division carries the DoD 3Rs safety message. Mobile-first, 17px body text, light and dark themes, and every page and form works without JavaScript. Motion (hero entrance, survey sweep, NAICS strip, count-ups, self-drawing award timeline, Pacific map routes, scroll progress, theme wipe, page cross-fades) is all CSS/IntersectionObserver/rAF in `resources/js/motion.js`, off for `prefers-reduced-motion`, and adds nothing the HTML does not already show. Links from the old site (`/environet-services.html` and the rest) redirect to the new pages.
- **Four intake flows, each with a reference number and an email confirmation.**
  - RFQ / bid inquiry (`RQ-26-00014`): who is asking (federal contracting office, prime, state or county agency, private client), title, type (RFQ, RFP, IFB, sources sought, task order, sub quote request, teaming or JV), solicitation number, set-aside, NAICS, place of performance, scope, response date and time in Hawaiʻi time (never in the past), questions and site-visit dates, bonding, up to 5 documents or a SAM.gov link.
  - Teaming and vendor registration (`TP-…`): type (teaming partner, subcontractor, supplier, equipment rental, professional services), legal name and DBA, UEI (12 characters; required for teaming partners and subcontractors), CAGE, NAICS, self-declared SBA status (checked on SAM.gov before anyone relies on it), type of work, where the company works, and the general liability certificate, workers' compensation and contractor license with their expiry dates. Expired documents are refused.
  - Job application (`JA-…`): for a posted opening or a general application, with office (Oʻahu, Hawaiʻi Island, Guam, Colorado or any), willingness to travel, and up to 8 self-declared certifications (HAZWOPER 40/8/supervisor, UXO sweep and Technician I–III, OSHA 10/30, EM 385-1-1, CQM, first aid, CDL, other) with expiry dates where they lapse. Trades can apply without a résumé or email; scientist and management roles must attach a résumé.
  - Contact form (`EN-…`).
  All uploads go to the private `local` disk (`storage/app/private/{solicitations,vendors,cvs,rooms}`), never the public web root. If a form comes back with an error, the files that passed are held (`storage/app/private/held`, keyed to the visitor's session) so nobody attaches them twice; leftovers are pruned after 24 hours.
- **Insurance and license renewals.** `php artisan app:remind-expiring` (daily at 07:00 HST) emails each approved vendor whose document expires within 30 days a signed link to upload the renewed copy. The link only works until those documents are renewed, and not for a declined vendor. The old copy is kept as superseded.
- **Document rooms.** Staff create a room per bid package or project, upload documents and share a signed link that expires after 7, 30 or 90 days; revoking rotates the room's key. This replaces the plain `ftp://` bid-package links on the old site's bids page. Pages and downloads are `noindex` and `no-referrer`, and downloads are counted.
- **Back office** at `/admin`, with four roles:
  - **Admin**: everything, including staff accounts.
  - **Business development**: the bid register, inquiries, document rooms, and the vendor register read-only (to find teaming partners; can add notes, cannot open documents or decide).
  - **Procurement and contracts**: the vendor register, document checks and approvals, and document rooms (sub bid packages).
  - **HR**: job postings, applications, résumés and applicant certifications.

  The overview leads with one number for the signed-in role: bid responses due in the next 7 days (BD and admin), vendor documents expired or expiring within 30 days (procurement), or applications waiting more than 3 days (HR, with a tile for applicant certifications lapsing within 30 days). Each register has status tabs, filters, search and formula-safe CSV export. Every record has a notes timeline. A vendor cannot be approved while a required document is missing or expired; HR records "seen the card" on each applicant certification. Every opening of a private file is logged.

## Requirements

PHP 8.3 or 8.4, Composer 2, Node 20+ for building assets (and Chrome for the capability PDF and proposal), and SQLite locally or MySQL in production.

## Local setup

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seeder=DemoSeeder   # demo staff, 14 bid requests, 12 vendors, 7 postings, 22 applications, 2 document rooms
npm install && npm run build
php artisan serve --port=8112
npm run capability                        # public/downloads/Environet-Capability-Statement.pdf (dev server running)
```

Demo logins all use the password `password`: `admin@environet.test`, `bd@environet.test`, `procurement@environet.test`, `hr@environet.test` (and `proposals@environet.test`, a second BD user). Every demo organization, request, vendor, UEI (`SMPL…`) and applicant is fictional and marked "(sample)". `DatabaseSeeder` only creates the first admin from `ADMIN_*`.

## Checks

```sh
php artisan test                                                  # every public form, role boundary, policy and private file
/opt/homebrew/opt/php@8.3/bin/php vendor/bin/phpstan analyse --memory-limit=1G
vendor/bin/pint --test
node scripts/screens.mjs /tmp/shots                               # phone/desktop, light/dark screenshots + overflow check
```

The suite, migrations, DemoSeeder and a crawl of every public and admin page also pass on MySQL (`DB_CONNECTION=mysql … php artisan test`).

## Deploying

See [INSTALL-HOSTINGER.md](INSTALL-HOSTINGER.md). `scripts/package-hostinger.sh` builds `dist/environet-hostinger.zip`, which extracts straight into `public_html/`, with the app in `environet-app/`. One cron job (`schedule:run` every minute) is needed for the reminders. Mail for environetinc.com is on Microsoft 365 (MX `environetinc-com.mail.protection.outlook.com`), so send through an M365 mailbox and leave the MX and SPF records alone when pointing the domain at Hostinger.

## Proposal

With the dev server on 8112 and demo data loaded, `npm run proposal` captures every screen and builds `dist/proposal/Environet-Website-Proposal.pdf` (Letter; the build fails if it is over 9.5 MB).

## Before launch: confirm with the client

- **Office addresses.** The site footer gives Honolulu as 1286 Queen Emma Street; the SAM/SBA registration gives **91-799 Komohana Street, Kapolei, HI 96707**. Both are shown (contact page notes the SAM address). The home page of the old site calls Kamuela the headquarters. The Colorado office is shown at the Lafayette address from the current footer; an older capture lists Boulder (303-295-0250), and the current Colorado number has an 808 area code. Guam is named but has no published address. Ask which are current.
- **Phones and email.** (808) 833-2225 / fax 833-2231 and `enetmail@environetinc.com` are from the site footer (the domain's MX is Microsoft 365). The SBA profile lists (808) 369-2710 and `contracts@gsisg.com`, a parked domain; neither is used.
- **NAICS.** The SBA profile gives 236220 as primary and 40 codes in all; the site shows 11 selected codes. Confirm which to feature.
- **People.** Only Joseph G. Pickard, President (SBA profile) is named. The old personnel page's names are not repeated; add staff only with their consent.
- **Claims left out.** The old site's client list, MEC quantities and safety record are not repeated, and no clearances, bonding capacity, EMR or safety statistics are claimed. Add them only with a source the firm supplies.
- **Past performance.** Every award is as published on USAspending.gov (checked 27 October 2026); no award is described as complete. Ask for project narratives, photos and any non-federal work they want shown.
- **SBA status.** HUBZone and VOSB are shown with the dates on the SBA certification search (from 10 September 2026; VOSB to 10 September 2029). Recheck at launch.
- **Logo and brand (rights to confirm).** `<x-logo>` (and `public/favicon.svg`) is an SVG redraw of the "e" ring mark with its green honu flippers from the current site's banner (`images/topbanner.jpg`, Wayback capture of 17 June 2026); the "Environet, Inc." wordmark is set in Marcellus as the closest open font to the banner's flared lettering, over the banner's own tagline "Preserving Earth's Resources for the Future". Colours are from the site's stylesheet (`environet.css`: navy #003366, #033491, green #01A02C/#009933). Ask for the vector logo, the wordmark's actual font and any brand guidelines, and confirm we may use the mark until then.
- **Photos (rights to confirm).** `waikoloa-survey`, `crew-concrete` and `mec-item` are the firm's own, taken from environetinc.com via the Wayback Machine; reuse rights need confirming before launch. Every other photo is from Wikimedia Commons (credited on `/photo-credits`) and labelled "Illustrative photo".
- **Map.** The home-page Pacific map (`<x-pacific-map>`, places in `App\Support\PacificMap`) plots the four offices, two award places of performance outside Hawaiʻi and Guam (USAspending.gov) and the earlier work the old home page names (Japan, Korea, Saipan, Yap, as countries or islands, not sites). Coastlines are Natural Earth 1:50m (public domain). Confirm the firm is happy to show the earlier-work places.
- **Privacy notice** and retention periods (12 months for applications) need the firm's sign-off.
- **Inboxes** for `ENET_NOTIFY_*`, the staff list and their roles, and an M365 mailbox with SMTP AUTH for `MAIL_*`.
- **Vendor requirements.** The required documents (general liability, workers' comp, contractor license) are a reasonable default; confirm the firm's insurance limits and requirements.
