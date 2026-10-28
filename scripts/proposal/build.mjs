// Builds the client proposal (letter-size PDF) from the captured screenshots, in the site's own
// palette and fonts (survey navy, brand green and flag orange; Space Grotesk and IBM Plex Sans).
// Usage: npm run proposal (or node scripts/proposal/build.mjs after a capture).
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const ROOT = path.resolve('.');
const SHOTS = path.resolve('dist/proposal/shots');
const chunks = JSON.parse(fs.readFileSync(path.join(SHOTS, 'chunks.json'), 'utf8'));
const OUT_HTML = path.resolve('dist/proposal/proposal.html');
const OUT_PDF = path.resolve('dist/proposal/Environet-Website-Proposal.pdf');
const MAX_MB = 9.5;
const DATE = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
const TESTS = process.env.TEST_COUNT ?? '106';
const img = (f) => 'file://' + f;
// The cover photo as a JPEG: Chrome embeds JPEGs as they are but re-encodes WebP losslessly, which
// would add several megabytes. macOS sips does the conversion.
const COVER = path.resolve('dist/proposal/cover.jpg');
if (!fs.existsSync(COVER)) {
  (await import('node:child_process')).execSync(`sips -s format jpeg -s formatOptions 70 ${JSON.stringify(path.join(ROOT, 'public/img/waikoloa-survey-1920.webp'))} --out ${JSON.stringify(COVER)}`);
}
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;');

// The site's self-hosted fonts (built by the Vite fonts plugin into public/build/assets).
const ASSETS = path.join(ROOT, 'public/build/assets');
// Each weight comes in a latin and a latin-ext file; take the latin one (the face whose
// unicode-range starts at U+0000 in the plugin's fonts CSS), or the only file there is.
const FONT_CSS = fs.readdirSync(ASSETS).filter((n) => n.startsWith('fonts-') && n.endsWith('.css')).map((n) => fs.readFileSync(path.join(ASSETS, n), 'utf8')).join('\n');
const LATIN = new Set([...FONT_CSS.matchAll(/url\("[^"]*\/([^"/]+\.woff2)"\)[^}]*unicode-range: U\+0000-00FF/g)].map((m) => m[1]));
const font = (prefix) => {
  const all = fs.readdirSync(ASSETS).filter((n) => n.startsWith(prefix) && n.endsWith('.woff2'));
  const f = all.find((n) => LATIN.has(n)) ?? all[0];
  if (!f) throw new Error(`Font ${prefix} not found in public/build/assets; run npm run build first.`);
  return 'file://' + path.join(ASSETS, f);
};
const FONTS = [
  ['Space Grotesk', 600, 'space-grotesk-600-normal'], ['Space Grotesk', 700, 'space-grotesk-700-normal'],
  ['IBM Plex Sans', 400, 'ibm-plex-sans-400-normal'], ['IBM Plex Sans', 500, 'ibm-plex-sans-500-normal'],
  ['IBM Plex Sans', 600, 'ibm-plex-sans-600-normal'], ['IBM Plex Sans', 700, 'ibm-plex-sans-700-normal'],
  ['Okina', 400, 'okina-space-grotesk-500'],
].map(([fam, w, p]) => `@font-face { font-family: "${fam}"; font-weight: ${fam === 'Okina' ? '100 900' : w}; font-style: normal; src: url("${font(p)}") format("woff2");${fam === 'Okina' ? ' unicode-range: U+02BB;' : ''} }`).join('\n');

// The site's vector redraw of the firm's "e" ring mark (navy ring, green landmasses and dot).
const MARK = (h = 34, ring = '#003386') => `<span class="mark"><svg viewBox="0 0 100 100" width="${h}" height="${h}"><g fill="#43a01b"><circle cx="86" cy="13" r="8.5"/><path d="M31 7c9-6 23-7 32-2 5 3 8 7 10 12-7-4-13-6-21-5-8 1-15-1-21-5z"/><path d="M0 45c5-4 12-6 19-4l3 3c-6 0-11 1-15 3-3 1-5 0-7-2z"/><path d="M53 84c5 3 7 7 7 12 0 2-1 3-2 4-3-3-3-7-3-11 0-2-1-3-2-5z"/><path d="M87 38c5 5 8 12 7 20-1 7-3 11-7 15 0-7 1-12 0-18-1-7-3-11-6-15z"/><path d="M30 53c16 2 33 1 46-4 4-2 6-5 7-10l4 1c-1 6-5 11-11 14-13 5-29 6-46 5z"/></g><path fill="${ring}" d="M79 36C74 23 64 16 52 16 36 16 23 30 23 50s12 34 29 34c11 0 20-5 27-14l-3-2c-6 7-13 11-21 11-13 0-23-12-23-29S38 20 52 20c10 0 17 6 21 17z"/></svg></span>`;
const WORDMARK = (dark = true) => `<div class="brand">${MARK(dark ? 46 : 36, dark ? '#ffffff' : '#003386')}<div><span class="n ${dark ? '' : 'ink'}">environet<span class="inc">, inc.</span></span><span class="s ${dark ? '' : 'ink'}">Since 1995 · Hawaiʻi</span></div></div>`;
// The site's survey pin flag, used as the section label.
const plate = (code, label, dark = false) => `<p class="plate ${dark ? 'dark' : ''}"><svg viewBox="0 0 12 14" width="10" height="12"><path d="M1 0v14" stroke="currentColor" stroke-width="2"/><path d="M2 1h9l-2.5 3L11 7H2z" fill="${dark ? '#f08a24' : '#43a01b'}"/></svg><span class="c">${esc(code)}</span><span class="l">${esc(label)}</span></p>`;

/** Hand-written captions per slice (key = screen name, index = slice number), from what each slice shows. */
const CAPTIONS = {
  home: [
    'Hero on the firm\'s own photograph of geophysical survey lanes at Waikoloa, the two actions a contracting officer needs (send an RFQ, open the capability statement) and a chain-of-custody tag with the UEI, CAGE, primary NAICS and SBA status. Below it, four figures from the public record, each with its source.',
    'The three divisions, in the firm\'s own words, each opening the services page at the right place.',
    'Federal awards exactly as USAspending.gov publishes them: agency, office, obligated amount, award date and period of performance.',
    'For contracting officers and primes: the capability statement, the RFQ form and private document sharing, then teaming registration and careers.',
    'All four offices as the firm lists them, and the footer with the registration identifiers.',
  ],
  services: [
    'Services: environmental services with every service line from the firm\'s website and, beside it, the awards on the public record that show it.',
    'Munitions response, stated plainly, with the Department of Defense 3Rs safety message (Recognize, Retreat, Report) for anyone who finds a suspected munition.',
    'Construction, and the closing call to action.',
  ],
  performance: [
    'Past performance: all 21 federal awards found on USAspending.gov, filterable by division, newest first. No award is described as complete; the card shows the recorded period of performance.',
    'More awards across Oʻahu, Guam and the mainland.',
    'Munitions response and environmental awards back to 2009, and the source note: where each figure comes from and when it was checked.',
  ],
  award: [
    'An award page (MEC removal action at the former Waikoloa Maneuver Area): agency, office, PIID, obligated amount, award date and place of performance, as published.',
    'The description exactly as USAspending.gov publishes it, the link to the record, and an RFQ prompt for similar requirements.',
    'Related awards in the same division.',
  ],
  about: [
    'About: the firm\'s own history, strengths and core values from its current website, the President as named in the SBA profile, and the four offices.',
    'The SBA certifications with their dates, and a timeline built only from dated federal awards.',
    'Links to the capability statement and contact.',
  ],
  capability: [
    'The two-page capability statement: company data, differentiators and core competencies on page one. It prints to Letter and downloads as a PDF.',
    'Page two: NAICS codes, the largest federal awards with their PIIDs, and every office and contact.',
  ],
  contact: [
    'Contact: the Honolulu office line, fax and email as published, and a message form that issues an EN- reference.',
    'All four offices with addresses, phones and maps, including the note that the SAM registration lists a Kapolei address.',
  ],
  rfq: [
    'Send an RFQ or bid inquiry: who it is for and what happens next.',
    'Who is asking (federal contracting office, prime, state or county agency, or private client), then the requirement: type, solicitation number, set-aside, NAICS and place of performance. The side panel shows the firm\'s registration data.',
    'Estimated magnitude, only if it has been published.',
    'Key dates in Hawaiʻi time (responses due, questions, site visit), bonding, and up to five documents or a SAM.gov link.',
  ],
  teaming: [
    'Teaming and vendor registration: for teaming partners, subcontractors, suppliers and professional services.',
    'The company: type of relationship, legal name, UEI (checked as 12 characters), CAGE, NAICS and address, then the contact person. A tag explains the renewal promise.',
    'Self-declared small-business status (confirmed on SAM.gov before relying on it), type of work and where the company works.',
    'Required documents with their expiry dates: certificate of general liability insurance, workers\' compensation and the contractor license. Expired documents are refused.',
    'Optional SBA certification letter and capability statement, the declaration, and submit.',
  ],
  careers: [
    'Careers: openings by division (sample postings here) with office, employment type and closing date, then the kinds of role, the certifications to list, and the equal-opportunity statement.',
  ],
  apply: [
    'Job application: choose the opening and the office (Oʻahu, Hawaiʻi Island, Guam or Colorado), and say whether you can travel to project sites.',
    'Certifications with their expiry dates: HAZWOPER, UXO Technician I to III, OSHA and others. HR checks each card before anyone goes to site.',
    'About the applicant, résumé upload (required for scientist and management roles) and consent.',
  ],
  room: ['A private document page for a subcontractor bid package, opened from a signed link that expires. It replaces the plain FTP links on the current bids page; every download is counted.'],
  'admin-login': ['Staff sign-in. Accounts that have been switched off are refused and signed out.'],
  'admin-dashboard': [
    'Overview for business development: the number of bid responses due in the next seven days comes first, with the closing-soonest list, tiles for every area and the bid pipeline. Figures are from the demo data.',
  ],
  'admin-dashboard-procurement': ['The same overview signed in as procurement: it leads with vendor insurance and licenses that have expired or expire within 30 days.'],
  'admin-dashboard-hr': ['Signed in as HR: applications waiting more than three days come first, with applicant certifications that are lapsing.'],
  'admin-bids': [
    'Bid register: open requests first, ordered by response date, with status tabs, source and owner filters, search and CSV export.',
    'Every request with its agency or prime, set-aside, countdown, status and owner, with paging and export.',
  ],
  'admin-bid': ['A request: response date in Hawaiʻi time, set-aside and NAICS, documents, the bid / no-bid decision and owner, one-tap call to the contracting office, and a timeline of every change.'],
  'admin-vendors': [
    'Teaming and vendor register: companies by status with UEI, self-declared set-asides and the state of their insurance and licenses (valid, expiring, expired, missing).',
    'Filters by type, set-aside and work, and a "needs renewal" view.',
  ],
  'admin-vendor': ['A vendor: each policy and license with its number and expiry, opened privately and marked as checked. A vendor cannot be approved while a required document is missing or expired. Business development sees the company but not the documents.'],
  'admin-applications': [
    'Applications: status tabs, division, office, certification and "lapsing" filters, and search.',
    'Each applicant with position, office, certification chips (lapsing ones flagged), résumé and status.',
    'The rest of the page: hired and interviewing applicants keep their checked certifications on record.',
  ],
  'admin-application': [
    'An application: one-tap call, office and travel, the résumé (HR and administrators only, every download logged), the status, and self-declared certifications with expiry dates, where HR records "seen the card" with the date on it.',
  ],
  'admin-room': [
    'A document room for a sub bid package: upload drawings and specifications, create a share link valid for 7, 30 or 90 days, email it, revoke it at any time, and see every upload, link and download in the timeline.',
  ],
  'admin-users': ['Staff accounts with four roles: administrator, business development, procurement and contracts, and HR.'],
};

/** Screen definitions: section, key, title, bullets. */
const SECTIONS = [
  { id: 'public', eyebrow: 'Part 1', code: '01', title: 'The public website', intro: 'A new environetinc.com, written from the firm\'s own website, its SBA profile and its federal awards on USAspending.gov. Every inner page opens with the same navy survey-lane header, and every page works on a phone.', screens: [
    ['home', 'Home page', ['Leads with what the firm does and the two actions a contracting officer needs.', 'Four figures from the public record, each with its source one click away.']],
    ['services', 'Services', ['Three divisions with every service line from the current site, each linked to the awards that show it.', 'Old addresses such as /environet-services.html redirect to the new pages.']],
    ['performance', 'Past performance', ['Only federal awards on USAspending.gov, with agency, office, amount and dates as published.']],
    ['award', 'Award page', ['One page per award with the published description and a link to the record.']],
    ['about', 'About the firm', ['The firm\'s own history, strengths and values, and its SBA certifications.']],
    ['capability', 'Capability statement', ['A printable statement that updates itself whenever an award is added, plus a downloadable PDF.']],
    ['contact', 'Contact', ['All four offices, phones and email as published, and a message form.']],
  ]},
  { id: 'flows', eyebrow: 'Part 2', code: '02', title: 'Bids, partners and people', intro: 'Four intake flows replace email chains and phone tag: RFQs and bid inquiries, teaming and vendor registration, job applications and messages. Each gives the sender a reference number on screen and by email and lands in the right team\'s register. A fifth, private flow shares bid packages and project documents.', screens: [
    ['rfq', 'RFQ and bid inquiry', ['Response dates are checked (never in the past), held in Hawaiʻi time and shown as a countdown in the register.', 'Solicitation documents are stored privately, outside the public website.']],
    ['teaming', 'Teaming and vendor registration', ['UEI, CAGE, NAICS and self-declared set-asides; insurance and licenses with their expiry dates.', 'Thirty days before a document expires, the vendor is emailed a signed renewal link and uploads the new copy themselves.']],
    ['careers', 'Careers', ['Openings posted by HR, with Google job-posting data so they appear in job search.']],
    ['apply', 'Job application', ['Office choice and certifications with expiry dates, checked by HR before anyone goes to site.']],
    ['room', 'Private document rooms', ['A private page per bid package or project, on a link that expires and can be revoked.']],
  ]},
  { id: 'admin', eyebrow: 'Part 3', code: '03', title: 'The back office', intro: 'A private, role-based back office for business development, procurement and HR. Each role sees only its own registers, and the overview opens on the one number that role has to act on today.', screens: [
    ['admin-login', 'Staff sign-in', ['Individual accounts with four roles; switched-off accounts are refused.']],
    ['admin-dashboard', 'Overview', ['Business development sees bid responses due in the next seven days first.']],
    ['admin-dashboard-procurement', 'Overview for procurement', ['Procurement sees expiring and expired vendor documents first.']],
    ['admin-dashboard-hr', 'Overview for HR', ['HR sees applications waiting for review, and lapsing certifications.']],
    ['admin-bids', 'Bid register', ['Bid / no-bid, pricing, submitted, won or lost, with an owner on every request.']],
    ['admin-bid', 'Bid detail', ['Documents, decision, owner and notes in one place, with a full timeline.']],
    ['admin-vendors', 'Teaming and vendor register', ['Every company\'s document state and set-asides at a glance.']],
    ['admin-vendor', 'Vendor detail', ['Documents are checked and dated by a named officer; approval is blocked while one is missing or expired.']],
    ['admin-applications', 'Applications', ['Applicants waiting more than three days and lapsing certifications are flagged.']],
    ['admin-application', 'Applicant detail', ['Résumés open for HR and administrators only.']],
    ['admin-room', 'Document room', ['Share links are signed, expire, and can be revoked in one click.']],
    ['admin-users', 'Staff and roles', ['Add staff, change roles and switch accounts off.']],
  ]},
];
const SCREEN_COUNT = SECTIONS.reduce((n, s) => n + s.screens.length, 0);

const FEATURES = [
  ['Public website', ['Three divisions linked to awards', 'Past performance from USAspending.gov', 'One page per award, with its source', 'Capability statement page and PDF', 'Munitions safety (3Rs) message', 'About and four offices', 'Redirects from the old site\'s links', 'Sitemap and Google structured data']],
  ['Intake', ['RFQ and bid inquiry with documents', 'Teaming and vendor registration', 'UEI, CAGE, NAICS and set-asides', 'Insurance and license expiry tracking', 'Signed renewal links', 'Careers with office choice', 'Applicant certifications with expiry dates', 'Reference number on screen and by email']],
  ['Back office', ['Overview that leads with each role\'s number', 'Bid register with response dates', 'Vendor register and document checks', 'Applications, certification checks and postings', 'Document rooms with signed links', 'Notes and a timeline on every record', 'CSV exports', 'Four staff roles']],
  ['Platform', ['Laravel 13 on PHP 8.3', 'HTTPS throughout', 'All uploads stored privately', 'Each team opens only its own files', 'Rate-limited, spam-protected forms', 'Light and dark themes', `${TESTS} automated tests`, 'Packaged for one-zip installation']],
];

/**
 * Pack a screen's slices onto pages. A slice's height ÷ width is its share of a page. Every slice keeps
 * its own caption; slices never break across pages and keep their order. The layout is chosen by cost:
 * one point per page, one more for a page left about a third empty, half a point for shrinking slices
 * below 90% of a 1440px screen to share a page (never below 85%), and a quarter point for moving the
 * bullets off the first page. So a short slice joins its neighbour instead of sitting alone on a
 * half-blank page; ties go to the layout whose emptiest page is fullest.
 */
function pages(c, hasBullets) {
  const sizes = c.sizes ?? c.chunks.map(() => [1440, 1440]);
  // Page capacity as image height ÷ text width: about 1.22 below the heading, 0.98 when bullets sit on top.
  // The estimate leaves out caption height, so a page counts as gappy at 25% estimated (about 35% printed).
  const CAP = 0.08, GOOD_SCALE = 0.9, MIN_SCALE = 0.85, GAP = 0.25;
  const ratios = sizes.map(([w, h]) => h / w);
  const page = (from, to, bullets) => {
    const budget = bullets ? 0.98 : 1.22;
    const sum = ratios.slice(from, to).reduce((a, r) => a + r, 0);
    const n = to - from;
    const scale = Math.min(1, (budget - CAP * n) / sum);
    const readable = scale * 1440 / sizes[from][0]; // narrower captures carry larger type
    if (n > 1 && readable < MIN_SCALE) return null;
    const empty = 1 - (sum * scale + CAP * n) / budget;
    return { items: [...Array(n).keys()].map((k) => from + k), bullets, budget, scale, empty, cost: 1 + (empty > GAP ? 1 : 0) + (readable < GOOD_SCALE ? 0.5 : 0) + (bullets && from > 0 ? 0.25 : 0) };
  };
  // best(i, owed) = cheapest packing of slices i..end, where owed means the bullets still need a page.
  const n = ratios.length;
  const memo = new Map();
  const best = (i, owed) => {
    if (i === n) return owed ? null : { list: [], cost: 0, worst: 0 };
    const key = i + ':' + owed;
    if (memo.has(key)) return memo.get(key);
    let pick = null;
    for (const here of owed ? [true, false] : [false]) {
      for (let j = i + 1; j <= n; j++) {
        const pg = page(i, j, here);
        if (!pg) break;
        const rest = best(j, owed && !here);
        if (!rest) continue;
        const cand = { list: [pg, ...rest.list], cost: pg.cost + rest.cost, worst: Math.max(pg.empty, rest.worst) };
        if (!pick || cand.cost < pick.cost - 1e-9 || (Math.abs(cand.cost - pick.cost) < 1e-9 && cand.worst < pick.worst - 1e-9)) pick = cand;
      }
    }
    memo.set(key, pick);
    return pick;
  };
  return best(0, hasBullets).list;
}

function caption(key, i, c) {
  const text = CAPTIONS[key]?.[i];
  if (!text) return '';
  const n = c.chunks.length > 1 ? `${i + 1}/${c.chunks.length} · ` : '';
  return `<figcaption class="cap"><span class="n">${esc(n)}</span>${esc(text)}</figcaption>`;
}

function screenPages(sec, [key, title, bullets]) {
  const c = chunks[key];
  if (!c) throw new Error(`Screenshot missing: ${key}`);
  return pages(c, bullets.length > 0).map((p, pi) => `
    <section class="page shot">
      <header class="shot-head">
        <div><p class="eyebrow">${esc(sec.title)}${pi ? ' · continued' : ''}</p><h2>${esc(title)}</h2></div>
        <p class="url">${esc(c.url.split('?')[0].replace(/^(\/documents\/)[^/]+\/.+$/, '$1…'))}</p>
      </header>
      ${p.bullets ? `<ul class="bullets">${bullets.map((b) => `<li>${esc(b)}</li>`).join('')}</ul>` : ''}
      ${p.items.map((i) => `<figure><img style="width:${(p.scale * 100).toFixed(1)}%" src="${img(c.chunks[i])}" alt="${esc(title)}">${caption(key, i, c)}</figure>`).join('')}
      ${foot()}
    </section>`).join('');
}


const foot = () => `<footer class="foot"><span class="fl">${MARK(14)}Environet, Inc. · Website proposal</span><span>Code Canvas Consultants Ltd · ${esc(DATE)}</span></footer>`;
const notes = (list) => `<div class="phone-notes">${list.map(([t, d]) => `<div><b>${esc(t)}</b>${esc(d)}</div>`).join('')}</div>`;
const phones = (list) => `<div class="phones">${list.map(([k, c]) => `<figure><img src="${img(chunks[k].chunks[0])}" alt="${esc(c)}"><figcaption>${esc(c)}</figcaption></figure>`).join('')}</div>`;

const html = `<!doctype html><html lang="en-US"><head><meta charset="utf-8"><title>Environet, Inc. – Website Proposal</title>
<style>

  ${FONTS}
  @page { size: Letter; margin: 0.5in 0.6in 0.55in; }
  :root { --graphite: #071a40; --graphite-950: #04112b; --gm: #0b2557; --bg: #f5f6f2; --surface-2: #e9ebe4; --ink: #0f1d33; --ink-2: #33405a; --ink-3: #55607a; --line: #d6d9d0; --coral: #f08a24; --coral-text: #003386; --coral-soft: #eaf0fb; --green: #43a01b; --leaf-text: #2c6e10; --manila: #f1ecdc; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; font-family: "Okina", "IBM Plex Sans", system-ui, sans-serif; color: var(--ink); font-size: 10.5pt; line-height: 1.45; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  h1, h2, h3, .display { font-family: "Okina", "Space Grotesk", system-ui, sans-serif; font-weight: 700; letter-spacing: -0.03em; color: var(--ink); margin: 0; line-height: 1.05; }
  h3 { font-family: "Okina", "IBM Plex Sans", system-ui, sans-serif; letter-spacing: 0; font-size: 12pt; }
  .page { page-break-after: always; break-after: page; height: 9.95in; display: flex; flex-direction: column; overflow: hidden; }
  .page:last-child { page-break-after: auto; }
  .eyebrow { font-size: 8pt; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--coral-text); margin: 0 0 5px; }
  .muted { color: var(--ink-3); }
  /* number-plate label, as on the site */
  .plate { align-self: flex-start; display: inline-flex; border: 1.5px solid currentColor; border-radius: 3px; padding: 2px; font-size: 7.5pt; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; line-height: 1; color: var(--ink-2); margin: 0 0 12px; }
  .plate span { display: inline-flex; align-items: center; padding: 5px 7px 4px; border-radius: 3px; }
  .plate .c { background: var(--ink); color: var(--bg); font-family: "Space Grotesk"; font-weight: 800; letter-spacing: .04em; }
  .plate .l { box-shadow: inset 0 0 0 1px rgba(0,0,0,.18); margin-left: 2px; }
  .plate.dark { color: rgba(255,255,255,.85); } .plate.dark .c { background: var(--coral); color: var(--graphite); } .plate.dark .l { box-shadow: inset 0 0 0 1px rgba(255,255,255,.28); }
  .ticks { height: 6px; background: linear-gradient(var(--green), var(--green)) top / 100% 3px no-repeat, linear-gradient(var(--coral), var(--coral)) bottom / 100% 3px no-repeat; }
  /* cover and dividers: the site's graphite hero band */
  .dark-page { background: var(--graphite); color: #fff; border-radius: 3px; padding: 0.6in 0.6in 0.5in; position: relative; overflow: hidden; }
  .dark-page h1 { color: #fff; }
  .cover { justify-content: space-between; background-image: linear-gradient(180deg, rgba(4,17,43,.45), rgba(4,17,43,.94) 60%), url("${img(COVER)}"); background-size: cover; background-position: 50% 30%; }
  .cover h1 { font-size: 40pt; margin: 14px 0 16px; max-width: 6.2in; }
  .cover .accent { color: var(--coral); }
  .cover .lead { color: rgba(255,255,255,.85); font-size: 13pt; max-width: 5.8in; margin: 0; }
  .cover .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; border-top: 1px solid rgba(255,255,255,.18); padding-top: 16px; font-size: 9.5pt; color: rgba(255,255,255,.8); }
  .cover .meta b { display: block; color: #fff; font-size: 9pt; letter-spacing: .14em; text-transform: uppercase; margin-bottom: 4px; }
  .brand { display: flex; align-items: center; gap: 10px; } .brand .n { display: block; font-family: "Space Grotesk"; font-weight: 700; font-size: 17pt; color: #fff; line-height: 1; letter-spacing: -0.02em; } .brand .s { display: block; margin-top: 4px; font-size: 7pt; font-weight: 600; letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.7); }
  .mark { display: inline-flex; align-items: center; flex: none; background: #fff; border-radius: 5px; } .mark img { display: block; width: auto; }
  .brand .ink { color: var(--ink); } .brand .s.ink { color: var(--ink-3); }
  .divider { justify-content: center; }
  .divider h1 { font-size: 38pt; margin: 6px 0 18px; } .divider p.intro { color: rgba(255,255,255,.85); font-size: 12.5pt; max-width: 6in; margin: 0; }
  .divider ol { color: rgba(255,255,255,.85); columns: 2; font-size: 10pt; margin: 26px 0 0; padding-left: 18px; } .divider li { break-inside: avoid; margin: 3px 0; }
  .divider .note { margin-top: 22px; font-size: 9pt; color: rgba(255,255,255,.65); max-width: 6in; }
  .strata { position: absolute; left: 0; right: 0; bottom: 0; height: 14px; background: repeating-linear-gradient(90deg, #43a01b 0 46px, #2c6e10 46px 60px, #f1ecdc 60px 132px, #f08a24 132px 140px, #0b2557 140px 220px); opacity: .9; }
  /* text pages */
  .prose h1 { font-size: 24pt; margin-bottom: 12px; }
  .prose h2 { font-size: 13pt; margin: 16px 0 6px; letter-spacing: -0.01em; }
  .prose p { margin: 0 0 8px; }
  .prose ul.plain { margin: 0 0 8px; padding-left: 16px; } .prose ul.plain li { margin: 2px 0; }
  .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .card { border: 1px solid var(--line); border-radius: 3px; padding: 11px 13px; background: #fff; break-inside: avoid; }
  .card h3 { margin-bottom: 5px; }
  .card ul { margin: 0; padding-left: 15px; } .card li { margin: 2px 0; font-size: 9.6pt; }
  .evidence { border-left: 4px solid var(--coral-text); background: var(--bg); border-radius: 0 10px 10px 0; padding: 10px 14px 10px 30px; margin: 8px 0 10px; }
  .evidence li { margin: 3px 0; }
  .src { font-size: 8pt; color: var(--ink-3); }
  .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 12px 0 6px; }
  .stat { background: var(--graphite); color: #fff; border-radius: 3px; padding: 12px 12px 11px; }
  .stat b { display: block; font-family: "Space Grotesk"; font-weight: 800; font-size: 22pt; line-height: 1; letter-spacing: -0.03em; color: #fff; border-left: 3px solid var(--coral); padding-left: 8px; }
  .stat span { display: block; margin-top: 8px; font-size: 8.5pt; color: rgba(255,255,255,.78); line-height: 1.3; }
  table.feat { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
  table.feat th, table.feat td { border-bottom: 1px solid var(--line); padding: 8px 8px; text-align: left; vertical-align: top; }
  table.feat th { background: var(--surface-2); font-size: 8pt; text-transform: uppercase; letter-spacing: .1em; color: var(--ink-3); }
  .pill-row { display: flex; flex-wrap: wrap; gap: 5px; } .pill { border: 1px solid #a9cfcc; background: var(--coral-soft); color: var(--coral-text); border-radius: 3px; padding: 2px 9px; font-size: 8.5pt; font-weight: 600; }
  /* screenshot pages */
  .shot-head { display: flex; justify-content: space-between; align-items: flex-end; gap: 12px; border-bottom: 2px solid var(--ink); padding-bottom: 6px; margin-bottom: 7px; }
  .shot-head h2 { font-size: 16pt; } .url { font-family: Menlo, Consolas, monospace; font-size: 8pt; color: var(--ink-3); margin: 0; white-space: nowrap; }
  .bullets { margin: 0 0 7px; padding-left: 16px; font-size: 9.3pt; } .bullets li { margin: 1px 0; } .bullets li::marker { color: var(--coral-text); }
  figure { margin: 0 0 8px; display: block; text-align: center; }
  figure img { display: block; margin: 0 auto; border: 1px solid var(--line); border-radius: 3px; }
  .cap { text-align: left; margin: 5px 0 0; font-size: 8.6pt; line-height: 1.35; color: var(--ink-2); border-left: 3px solid var(--coral-text); padding-left: 8px; }
  .cap .n { font-weight: 700; color: var(--ink-3); }
  .phones { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 12px; }
  .phones figure { margin: 0; }
  .phones img { width: 100%; border-radius: 3px; border: 7px solid var(--graphite); }
  .phone-notes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 14px; }
  .phone-notes div { background: var(--bg); border-radius: 3px; padding: 9px 12px; font-size: 9pt; color: var(--ink-2); }
  .phone-notes b { display: block; color: var(--ink); font-size: 9.5pt; margin-bottom: 2px; }
  .phones figcaption { text-align: center; font-size: 9pt; color: var(--ink-2); margin-top: 6px; font-weight: 600; }
  /* investment */
  .price { display: grid; grid-template-columns: 1.45fr 1fr; gap: 14px; align-items: stretch; margin: 6px 0 4px; }
  .price .big { background: var(--graphite); color: #fff; border-radius: 3px; padding: 16px 18px; }
  .price .big .amt { white-space: nowrap; font-family: "Space Grotesk"; font-weight: 700; font-size: 26pt; line-height: 1; color: #fff; } .price .big .amt small { font-size: 11pt; font-weight: 500; color: rgba(255,255,255,.75); }
  .price .big p { color: rgba(255,255,255,.82); margin: 8px 0 0; font-size: 9.6pt; }
  .price .year { border: 2px solid var(--coral-text); border-radius: 3px; padding: 14px 16px; background: var(--coral-soft); }
  .price .year div { display: flex; justify-content: space-between; gap: 8px; padding: 4px 0; border-bottom: 1px dotted #8fbdb9; font-size: 10pt; } .price .year div:last-child { border: 0; }
  .price .year b { font-family: "Space Grotesk"; font-weight: 700; }
  .terms li { margin: 3px 0; font-size: 9.6pt; }
  .sla { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 4px; } .sla td, .sla th { border-bottom: 1px solid var(--line); padding: 4px 6px; text-align: left; } .sla th { font-size: 7.8pt; letter-spacing: .1em; text-transform: uppercase; color: var(--ink-3); background: var(--surface-2); }
  .weeks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin: 6px 0 10px; }
  .week { border: 1px solid var(--line); border-radius: 3px; padding: 10px 12px; background: #fff; }
  .week b { display: inline-block; font-family: "Space Grotesk"; font-weight: 700; font-size: 8.5pt; background: var(--ink); color: var(--bg); border-radius: 4px; padding: 3px 6px; margin-bottom: 5px; }
  .week p { margin: 0; font-size: 9.5pt; }
  footer.foot { margin-top: auto; padding-top: 6px; font-size: 7.8pt; color: #8a939d; display: flex; justify-content: space-between; border-top: 1px solid var(--line); align-items: center; } footer.foot .fl { display: inline-flex; align-items: center; gap: 6px; }

  .phones img { border-radius: 14px; }
  .dark-page { border-radius: 0; }

  .plate { gap: 6px; border: 0; padding: 0; align-items: center; } .plate svg { flex: none; color: var(--ink); } .plate.dark svg { color: #fff; }
  .plate .c { border-radius: 2px; } .plate .l { box-shadow: none; padding-left: 2px; }
  .brand .n .inc { font-weight: 500; opacity: .75; } .mark { background: transparent; }
  .cap { border-left-color: var(--green); } .bullets li::marker { color: var(--green); }
  .evidence { border-left-color: var(--coral); }
  .stat b { border-left-color: var(--green); }
  .price .year { border-color: var(--coral-text); } .price .year div { border-bottom-color: #9fb3d9; }
  .pill { border-color: #b7c6e6; }
</style></head><body>

<section class="page dark-page cover">
  ${WORDMARK()}
  <div>
    ${plate('ENV', 'Website proposal · ' + DATE, true)}
    <h1>Win the bid, team faster, <span class="accent">staff the field.</span></h1>
    <p class="lead">A new environetinc.com with past performance and a capability statement built from the public record, RFQ and bid intake, teaming and vendor registration with insurance tracking, a careers pipeline that records HAZWOPER and UXO certifications, private document rooms, and a back office for business development, procurement and HR.</p>
  </div>
  <div class="meta">
    <div><b>Prepared for</b>Environet, Inc.<br>Attn. Joseph G. Pickard, President<br>1286 Queen Emma Street, Honolulu, HI 96813</div>
    <div><b>Prepared by</b>Keron Lewis, Code Canvas Consultants Ltd<br>Port of Spain, Trinidad and Tobago<br>+1 868 466-CODE · info@codecanvastt.com</div>
  </div>
  <div class="strata"></div>
</section>

<section class="page prose">
  ${plate('01', 'Executive summary')}
  <h1>Why rebuild now</h1>
  <p>Environet has worked across Hawaiʻi and the Pacific since 1995, and the federal record shows a firm that is busy and growing: <b>US$19.38 million</b> in federal contract transactions in the last twelve months on USAspending.gov, including the US$16.84 million Tinker Road Bridge repair on Oʻahu for the Army Corps of Engineers and BEAR K-Span storage structures at Andersen Air Force Base. In September 2026 the SBA certified the firm as a HUBZone and Veteran-Owned Small Business. The website a contracting officer or teaming partner finds when they look the firm up tells none of that. When we checked on October 27, 2026:</p>
  <ul class="evidence">
    <li><b>The site is built on a 2006 template.</b> Its footer reads "© 2006 Environet, Inc.", it has a fixed 770-pixel layout with no mobile view, and it still carries the Macromedia Flash scripts its banner and navigation were made with; browsers stopped running Flash in 2021.</li>
    <li><b>Bid packages were shared as plain FTP links.</b> The site's bids page, as the Wayback Machine archived it, linked subcontractor bid packages to a numeric FTP server address. Current Chrome, Edge and Firefox no longer open FTP links, and FTP sends files unencrypted.</li>
    <li><b>The federal track record is not on the site at all.</b> There is no UEI, CAGE code, NAICS list, SBA status or capability statement, and the address on the site differs from the one in SAM.</li>
  </ul>
  <p class="src">Sources: environetinc.com and its Wayback Machine captures; USAspending.gov recipient profile and award records; SBA Small Business Search profile for UEI EEWLLJUH4SC9; all checked October 27, 2026.</p>
  <p>This proposal replaces the old site with one built for a federal contractor: past performance and a capability statement drawn straight from the public record, an RFQ form that issues a reference number, teaming and vendor registration that tracks every subcontractor's insurance and license, careers that capture each applicant's office and certifications, and private document rooms in place of the FTP links. A working demo is built and can be shown live, on a laptop or a phone.</p>
  <div class="stat-grid">
    <div class="stat"><b>${SCREEN_COUNT}</b><span>screens designed, built and shown in this proposal</span></div>
    <div class="stat"><b>21</b><span>federal awards in past performance, each linked to its USAspending record</span></div>
    <div class="stat"><b>4</b><span>staff roles, each seeing only its own registers</span></div>
    <div class="stat"><b>${TESTS}</b><span>automated tests run before every release</span></div>
  </div>
  <h2>What the firm gains</h2>
  <div class="cols">
    <div class="card"><h3>Contracting officers and primes</h3><ul><li>UEI, CAGE, NAICS and SBA status on every page</li><li>The capability statement in one click</li><li>RFQs acknowledged with a reference number</li></ul></div>
    <div class="card"><h3>Business development, procurement and HR</h3><ul><li>Every response date in one register, with a seven-day warning</li><li>Vendor insurance that reminds the vendor before it lapses</li><li>Applicants by office, with certifications and expiry dates</li></ul></div>
  </div>
  ${foot()}
</section>

<section class="page prose">
  ${plate('02', 'At a glance')}
  <h1>What is included</h1>
  <table class="feat"><thead><tr><th style="width:20%">Area</th><th>Features</th></tr></thead><tbody>
    ${FEATURES.map(([a, f]) => `<tr><td><b>${esc(a)}</b></td><td><div class="pill-row">${f.map((x) => `<span class="pill">${esc(x)}</span>`).join('')}</div></td></tr>`).join('')}
  </tbody></table>
  <h2>How an RFQ flows</h2>
  <p>A contract specialist at an Army Corps district sends an RFQ with the statement of work and a response date. The form checks the date is in the future, records it in Hawaiʻi time, issues a reference such as <b>RQ-26-00014</b>, emails the specialist and alerts the business-development inbox. In the bid register it shows as New with a countdown. An owner is assigned and records the bid / no-bid decision, the bid package goes to subcontractors through a document room, quotes come in from registered vendors whose insurance is in date, and the request moves to Submitted, then Won or Lost. Seven days before the response date it rises to the top of the overview.</p>
  <h2>Content and imagery</h2>
  <p>The history, divisions, service lines and values come from the firm's own website. The UEI, CAGE code, NAICS codes, SBA certifications and the President's name come from the SBA profile, and every award comes from USAspending.gov with its agency, office, amount and dates as published. Nothing is invented: no staff numbers, safety statistics, bonding capacity, clearances, licenses or testimonials, and no award is described as complete. Three photographs are the firm's own, from its website; the rest are licensed from Wikimedia Commons, credited on the site and labelled "Illustrative photo" until the firm supplies its own. Bids, vendors, applicants and staff in these screenshots are clearly marked samples with fictitious names.</p>
  ${foot()}
</section>

${SECTIONS.map((sec) => `
<section class="page dark-page divider">
  ${plate(sec.code, sec.eyebrow, true)}
  <h1>${esc(sec.title)}</h1>
  <p class="intro">${esc(sec.intro)}</p>
  <ol>${sec.screens.map((s) => `<li>${esc(s[1])}</li>`).join('')}</ol>
  <p class="note">Screens are from the working demo at 1440 pixels wide (back office at 1280). Bids, vendors, applicants, staff names and figures in the back office are sample data.</p>
  <div class="strata"></div>
</section>
${sec.screens.map((s) => screenPages(sec, s)).join('')}
`).join('')}

<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design</p><h2>Built for phones first</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">Field staff, subcontractors and job seekers often arrive on a phone. Every page adapts to a small screen, and a bar with Call, Email and Send an RFQ stays within thumb reach.</p>
  ${phones([['m-home', 'Home'], ['m-award', 'Award page'], ['m-rfq', 'RFQ and bid inquiry']])}
  ${notes([['Thumb-sized controls', 'Every button and choice is at least 44 pixels tall.'], ['The right keyboard', 'Phone fields open the number pad; contact fields fill in from the phone\'s saved details.'], ['Light on data', 'Photos are served as WebP in three sizes, so a phone downloads the small one.']])}
  ${foot()}
</section>
<section class="page">
  <header class="shot-head"><div><p class="eyebrow">Responsive design · continued</p><h2>Applying and reviewing on a phone</h2></div><p class="url">390 × 844</p></header>
  <p style="margin:0 0 4px">A UXO technician can apply from a phone and photograph a certification card, and HR can check what is lapsing from the road.</p>
  ${phones([['m-apply', 'Job application'], ['m-admin-dashboard', 'Back-office overview'], ['m-admin-application', 'Applicant certifications']])}
  ${notes([['Upload from anywhere', 'Résumés and certificates can be PDFs or phone photos (JPG or PNG).'], ['Due first', 'The overview opens on what is due this week.'], ['One tap to call', 'Call or email the contracting office, vendor or applicant from the record.']])}
  ${foot()}
</section>

<section class="page prose">
  ${plate('04', 'Technology, hosting & security')}
  <h1>Built properly, hosted for you</h1>
  <div class="cols">
    <div class="card"><h3>Platform</h3><ul><li>Laravel 13 on PHP 8.3 with a MySQL database: open-source and widely supported, with no proprietary page builder</li><li>Tailwind CSS and Alpine.js; fonts served from your own site, not a third-party font service</li><li>No background servers; emails send directly and one daily task sends insurance reminders</li><li>${TESTS} automated tests cover every form, role, register and private document</li></ul></div>
    <div class="card"><h3>Security and privacy</h3><ul><li>HTTPS enforced, with security headers on every page</li><li>Solicitation documents, vendor insurance, résumés and bid packages are stored outside the public website and served only to signed-in staff with the right role</li><li>Every opening of a private file is logged</li><li>Document-room and renewal links are signed, expire, and are never indexed</li><li>No Social Security numbers are collected; a privacy notice written for Hawaiʻi law</li></ul></div>
    <div class="card"><h3>Speed and search</h3><ul><li>Responsive WebP photography</li><li>XML sitemap, and schema.org data for the firm (with its UEI and CAGE) and every job posting</li><li>The old .html addresses redirect permanently to the new pages</li><li>Accessible: 17px body text, keyboard navigation, labelled forms, contrast checked in light and dark themes, and every form works without JavaScript</li></ul></div>
    <div class="card"><h3>Ownership</h3><ul><li>Your content, photos, domain and data are always yours</li><li>Staff manage bids, vendors, postings, document rooms and users themselves</li><li>Hosting, backups, monitoring and updates are handled by Code Canvas under the monthly plan</li><li>Your Microsoft 365 email stays exactly where it is</li></ul></div>
  </div>
  <h2>Emails included</h2>
  <p>Every submission sends the sender a confirmation with their reference number and what happens next, and alerts the right team's inbox (business development, procurement or HR) with a link to the record. Vendors receive renewal reminders 30 days before an insurance certificate or license expires, and subcontractors receive document-room links by email. Private files are never attached to emails.</p>
  ${foot()}
</section>

<section class="page prose">
  ${plate('05', 'Investment')}
  <h1>The US$1,000 a month plan</h1>
  <div class="price">
    <div class="big"><div class="amt">US$1,000 <small>per month</small></div><p><b>US$0 for design and development.</b> No setup fee and no deposit. Billing starts at the end of the month in which the site launches, prorated for the days it was live; monthly invoices are due within 14 days.</p></div>
    <div class="year"><div><span>Year one</span><b>US$12,000</b></div><div><span>Or prepaid annually (10% off)</span><b>US$10,800</b></div><div><span>Build cost</span><b>US$0</b></div></div>
  </div>
  <div class="cols" style="margin-top:8px">
    <div class="card"><h3>Included every month</h3><ul>
      <li>The complete design and build shown in this proposal</li>
      <li>Managed hosting with CDN and SSL</li>
      <li>Daily backups plus a weekly off-site copy</li>
      <li>Security patches and software updates</li>
      <li>24/7 uptime monitoring</li>
      <li><b>Up to 10 hours a month</b> of content changes and small improvements, such as adding awards or postings</li>
      <li>Staff training</li>
      <li>Google Analytics, Search Console and a monthly summary</li>
      <li>Support by email, WhatsApp and phone, plus a quarterly review call</li>
      <li>A 30-day post-launch warranty</li>
    </ul></div>
    <div class="card"><h3>Terms</h3><ul class="terms">
      <li>No minimum term. 30 days' notice by email from either side.</li>
      <li>Price fixed for the first 24 months.</li>
      <li>Optional annual prepayment at 10% off: US$10,800 a year.</li>
      <li><b>Handover fee US$7,000, only if you leave.</b> It buys the website outright and moves it to hosting in Environet, Inc.'s name. It is waived if Code Canvas stops trading.</li>
      <li>Your content, domain and data are always yours.</li>
    </ul>
    <h3 style="margin-top:8px">Quoted separately, only with your approval</h3><ul>
      <li>Work beyond 10 hours in a month: US$50 an hour, or a fixed quote</li>
      <li>SMS reminders for response dates or expiring insurance</li>
      <li>Integrations with an accounting, HR or document-control system</li>
      <li>A full vendor portal with logins; paid advertising and SEO campaigns; professional site photography or drone footage</li>
    </ul></div>
  </div>
  <h2>Service levels</h2>
  <table class="sla"><thead><tr><th>Issue</th><th>Response</th><th>Fix target</th></tr></thead><tbody>
    <tr><td>Site down or forms not sending</td><td>1 hour, any day</td><td>4 hours</td></tr>
    <tr><td>Something on a page is broken</td><td>4 business hours</td><td>1 business day</td></tr>
    <tr><td>Content changes (text, photos, awards, postings)</td><td>Acknowledged in 1 business day</td><td>Done in 2 business days</td></tr>
  </tbody></table>
  ${foot()}
</section>

<section class="page prose">
  ${plate('06', 'Timeline')}
  <h1>Six weeks to launch</h1>
  <div class="weeks">
    <div class="week"><b>Week 1</b><p>Kick-off, brand assets and content: your logo files, project photos and confirmation of every fact on the site.</p></div>
    <div class="week"><b>Week 2</b><p>Revision round 1. Forms, emails and staff roles configured with your real inboxes.</p></div>
    <div class="week"><b>Week 3</b><p>Content load: project narratives you want to add, open job postings, vendor document requirements.</p></div>
    <div class="week"><b>Week 4</b><p>Revision round 2.</p></div>
    <div class="week"><b>Week 5</b><p>Formal testing with your business-development, procurement and HR staff on every form and screen.</p></div>
    <div class="week"><b>Week 6</b><p>Launch on HTTPS, redirects from the old addresses, Search Console, and staff training.</p></div>
  </div>
  <h2>What we need from Environet</h2>
  <ul class="plain">
    <li>Your logo as a vector file (the demo redraws the "e" mark from the current website) and any brand guidelines.</li>
    <li>Photos from your own projects, so the illustrative photographs can be replaced, and confirmation that the three photos taken from the current site can be reused.</li>
    <li>Which office addresses are current (the website lists Queen Emma Street; SAM lists Kapolei), and a Guam address if you want one shown.</li>
    <li>Any project narratives you want beyond the federal record, and the insurance limits and licenses you require from subcontractors.</li>
    <li>The staff who need accounts and their roles, and the inboxes for bids, vendors and HR.</li>
    <li>Access to the environetinc.com domain settings (your Microsoft 365 email records stay untouched).</li>
  </ul>
  <h2>When billing starts</h2>
  <p>Not on signing. The build, both revision rounds, testing and the preview site cost nothing. Billing starts at the end of the month in which the site launches, prorated for the days it was live. Nothing goes live until you approve it in writing.</p>
  ${foot()}
</section>

<section class="page prose">
  ${plate('07', 'Next steps')}
  <h1>From proposal to launch</h1>
  <ol class="plain" style="padding-left:18px">
    <li style="margin:5px 0"><b>See the demo live.</b> We walk through the website and the back office together, on a laptop and a phone.</li>
    <li style="margin:5px 0"><b>Tell us what to change.</b> Pages, wording, roles or the intake flows; revision round 1 opens straight away.</li>
    <li style="margin:5px 0"><b>Sign a one-page agreement</b> with the terms in this proposal: US$1,000 a month, month to month on 30 days' notice, US$0 build cost, and a US$7,000 handover fee only if you leave.</li>
    <li style="margin:5px 0"><b>Kick off the same week</b>, with launch six weeks later.</li>
  </ol>
  <div class="card" style="margin-top:18px; max-width:4.6in">
    <h3>Contact</h3>
    <p style="margin:0">Keron Lewis, Director<br>Code Canvas Consultants Ltd, Port of Spain<br>+1 868 466-CODE · codecanvastt.com<br><a href="mailto:info@codecanvastt.com">info@codecanvastt.com</a><br><a href="https://calendly.com/keronlewis/30min">Book a call: calendly.com/keronlewis/30min</a></p>
  </div>
  <div style="margin-top:auto; margin-bottom:12px">${WORDMARK(false)}</div>
  ${foot()}
</section>
</body></html>`;


fs.writeFileSync(OUT_HTML, html);
const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox', '--allow-file-access-from-files'] });
const page = await browser.newPage();
await page.goto('file://' + OUT_HTML, { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: OUT_PDF, format: 'Letter', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: false });
await browser.close();
const mb = Math.round(fs.statSync(OUT_PDF).size / 1024 / 1024 * 10) / 10;
console.log('PDF written:', OUT_PDF, mb + ' MB');
if (mb > MAX_MB) {
  console.error(`The PDF is over ${MAX_MB} MB, too large for most email attachments. Lower the JPEG quality in capture.mjs.`);
  process.exit(1);
}
