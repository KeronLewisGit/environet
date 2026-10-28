// Captures full-page screenshots of every screen (desktop + phone) for the client proposal.
// Slices follow the page's own sections so a screenshot never breaks mid-block.
// Usage: npm run proposal (dev server on 8112 with demo data; env: BASE_URL, STAFF_PASSWORD, BID_ID, VENDOR_ID, APPLICATION_ID, ROOM_URL)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';
import { execSync } from 'node:child_process';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8112';
const OUT = path.resolve('dist/proposal/shots');
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const PASSWORD = process.env.STAFF_PASSWORD ?? 'password';
const BID = process.env.BID_ID ?? '1'; // demo RFQ in pricing, assigned, with a file and notes
const VENDOR = process.env.VENDOR_ID ?? '2'; // demo teaming partner with an expired liability certificate
const APPLICATION = process.env.APPLICATION_ID ?? '1'; // demo UXO technician with certifications and a résumé

// A signed client link to the first demo document room, made with the app's own key.
const ROOM_URL = process.env.ROOM_URL ?? execSync(`php artisan tinker --execute="URL::forceRootUrl('${BASE}'); echo App\\Models\\DocumentRoom::firstOrFail()->signedUrl() ?? App\\Models\\DocumentRoom::firstOrFail()->shareLink(30);"`).toString().trim().split('\n').pop();
const ROOM_PATH = ROOM_URL.replace(/^https?:\/\/[^/]+/, '');

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

const PUBLIC = [
  ['home', '/'], ['services', '/services'], ['performance', '/past-performance'], ['award', '/past-performance/waikoloa-areas-e-f-k-p'],
  ['about', '/about'], ['capability', '/capability-statement'], ['contact', '/contact'],
];
const FLOWS = [
  ['rfq', '/rfq'], ['teaming', '/teaming'], ['careers', '/careers'], ['apply', '/careers/apply'], ['room', ROOM_PATH],
];
const ADMIN_PAGES = [
  ['admin-login', '/login'],
  ['admin-dashboard', '/admin'], ['admin-bids', '/admin/bids'], ['admin-bid', `/admin/bids/${BID}`],
  ['admin-vendors', '/admin/vendors'], ['admin-vendor', `/admin/vendors/${VENDOR}`],
  ['admin-applications', '/admin/applications'], ['admin-application', `/admin/applications/${APPLICATION}`],
  ['admin-room', `/admin/rooms/${ROOM_PATH.split('/')[2]}`], ['admin-users', '/admin/users'],
];
const MOBILE = [['m-home', '/'], ['m-rfq', '/rfq'], ['m-apply', '/careers/apply'], ['m-award', '/past-performance/tinker-road-bridge'], ['m-admin-dashboard', '/admin'], ['m-admin-application', `/admin/applications/${APPLICATION}`]];

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', protocolTimeout: 60000, args: ['--hide-scrollbars', '--no-sandbox', '--lang=en-US'] });
const page = await browser.newPage();
await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'reduce' }, { name: 'prefers-color-scheme', value: 'light' }]);

async function settle() {
  await page.evaluate(async () => {
    // Scroll-reveal blocks: show their final state.
    document.querySelectorAll('[data-reveal]').forEach(e => e.classList.add('is-visible'));
    // Chrome on macOS draws date fields in the machine's region format, whatever --lang says. Show them
    // as a US browser does (mm/dd/yyyy) so the screenshots match what Environet's staff will see.
    document.querySelectorAll('input[type=date]').forEach(i => {
      const [y, m, d] = (i.value || '').split('-');
      i.type = 'text'; i.value = y ? `${m}/${d}/${y}` : ''; i.placeholder = 'mm/dd/yyyy';
    });
        // Lazy images below the fold would be blank in a full-page capture: load them all now.
    document.querySelectorAll('img[loading="lazy"]').forEach(i => { i.loading = 'eager'; });
    window.scrollTo(0, document.body.scrollHeight); await new Promise(r => setTimeout(r, 400)); window.scrollTo(0, 0);
    await Promise.race([
      Promise.all([...document.images].filter(i => !i.complete).map(i => new Promise(r => { i.onload = i.onerror = r; }))),
      new Promise(r => setTimeout(r, 5000)),
    ]);
    await Promise.all([...document.images].map(i => i.decode?.().catch(() => {})));
  });
  await new Promise(r => setTimeout(r, 500));
}

/**
 * Measure natural content blocks (header, each section, footer). Blocks taller than the
 * page budget are replaced by their children, recursively, so slices follow real section
 * boundaries instead of cutting wherever the pixel budget runs out.
 */
async function measureBlocks(maxH) {
  return page.evaluate((maxH) => {
    const rect = (el) => { const r = el.getBoundingClientRect(); return { top: Math.round(r.top + window.scrollY), bottom: Math.round(r.bottom + window.scrollY) }; };
    const visible = (el) => { const cs = getComputedStyle(el); const r = el.getBoundingClientRect(); return cs.display !== 'none' && cs.visibility !== 'hidden' && r.height > 24 && cs.position !== 'fixed'; };
    const skip = (el) => el.matches('script, style, template, [x-cloak], [aria-hidden="true"]:not(section)');
    const label = (el) => {
      const hs = [...el.querySelectorAll('h1, h2, h3')].filter(h => visible(h) && h.closest('nav, footer, [aria-hidden="true"]') === null);
      const seen = new Set();
      const text = (h) => { const c = h.cloneNode(true); c.querySelectorAll('[aria-hidden="true"], template, .sr-only').forEach(e => e.remove()); return c.textContent.replace(/\s+/g, ' ').trim(); };
      return hs.map(text).filter(t => t && !seen.has(t.toLowerCase()) && seen.add(t.toLowerCase())).slice(0, 4);
    };
    const explode = (el) => {
      const r = { ...rect(el), labels: label(el) };
      if (r.bottom - r.top <= maxH) return [r];
      const kids = [...el.children].filter(k => !skip(k) && visible(k));
      if (kids.length === 0) return [{ top: r.top, bottom: r.bottom, labels: r.labels }];
      if (kids.length === 1) return explode(kids[0]); // wrapper div – look inside it
      return kids.flatMap(explode);
    };
    // Sales desk layout: the content column is <main id="content">; public layout: body-level header/main/footer.
    const adminMain = document.querySelector('body.admin main#content');
    const roots = adminMain
      ? [...adminMain.children].map(el => ({ el, trailing: false }))
      : [...document.body.children].filter(el => ['HEADER', 'MAIN', 'FOOTER', 'DIV', 'SECTION'].includes(el.tagName) && !skip(el) && visible(el))
          .flatMap(el => el.tagName === 'MAIN' ? [...el.children].map(k => ({ el: k, trailing: false })) : [{ el, trailing: el.tagName === 'FOOTER' || (el.tagName === 'SECTION' && el.previousElementSibling?.tagName === 'MAIN') }]);
    const blocks = roots.filter(r => visible(r.el)).flatMap(r => explode(r.el).map(b => ({ ...b, trailing: r.trailing }))).filter(b => b.bottom > b.top).sort((a, b) => a.top - b.top);
    // merge overlaps / fill gaps so nothing is lost between blocks
    const merged = [];
    for (const b of blocks) {
      const last = merged[merged.length - 1];
      if (last && b.top < last.bottom - 2) { last.bottom = Math.max(last.bottom, b.bottom); last.trailing = last.trailing && b.trailing; last.labels = [...new Set([...last.labels, ...b.labels])]; } else merged.push({ ...b, labels: [...b.labels] }); // merge true overlaps only
    }
    const docH = Math.max(document.documentElement.scrollHeight, document.body.scrollHeight);
    if (merged.length) { merged[0].top = 0; merged[merged.length - 1].bottom = Math.max(merged[merged.length - 1].bottom, docH); for (let i = 1; i < merged.length; i++) merged[i].top = merged[i - 1].bottom; }
    return { blocks: merged, docH, docW: document.documentElement.clientWidth };
  }, maxH);
}

/** Greedily pack consecutive blocks into page-sized groups; a lone oversize block is hard-cut as a last resort. */
function packBlocks(blocks, maxH, docH) {
  const groups = [];
  let cur = null;
  for (const b of blocks) {
    const h = b.bottom - b.top;
    if (cur && b.bottom - cur.top <= maxH) { cur.bottom = b.bottom; cur.labels = [...new Set([...cur.labels, ...(b.labels ?? [])])]; continue; }
    if (cur) groups.push(cur);
    if (h > maxH) {
      for (let y = b.top; y < b.bottom; y += maxH) groups.push({ top: y, bottom: Math.min(y + maxH, b.bottom), labels: b.labels ?? [] });
      cur = null;
    } else cur = { top: b.top, bottom: b.bottom, labels: [...(b.labels ?? [])] };
  }
  if (cur) groups.push(cur);
  // swallow a short trailing slice (a footer strip, a table's last rows) into the previous group, so it never sits alone on a mostly empty page
  if (groups.length > 1) {
    const last = groups[groups.length - 1], prev = groups[groups.length - 2];
    if (last.bottom - last.top < maxH * 0.45 && last.bottom - prev.top <= maxH * 1.25) { prev.bottom = last.bottom; prev.labels = [...new Set([...prev.labels, ...last.labels])]; groups.pop(); }
  }
  groups[groups.length - 1].bottom = Math.min(groups[groups.length - 1].bottom, docH);
  return groups;
}

const RATIO = 1.08; // slice height ÷ width that fits a letter page beneath the heading and bullets

async function shoot(name, url, { width = 1440, height = 900, fullPage = true, mobile = false } = {}) {
  await page.setViewport({ width, height, deviceScaleFactor: mobile ? 1.5 : 1, isMobile: mobile, hasTouch: mobile });
  await page.goto(BASE + url, { waitUntil: 'networkidle0' });
  await settle();
  const files = []; const labels = [];
  if (!fullPage) {
    const hash = new URL(BASE + url).hash;
    if (hash) { await page.evaluate((h) => document.querySelector(h)?.scrollIntoView(), hash); await new Promise(r => setTimeout(r, 300)); }
    const file = path.join(OUT, `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: 68 });
    files.push(file);
    console.log(`✓ ${name} viewport`);
    return { name, url, chunks: files.map(f => path.resolve(f)), labels: [[]], sizes: [[width, height]], mobile };
  }
  const maxH = Math.round(width * RATIO);
  let { blocks, docH } = await measureBlocks(maxH);
  if (name !== 'home') { blocks = blocks.filter(b => !b.trailing); docH = blocks[blocks.length - 1].bottom; } // CTA band + footer are shown once (home)
  const groups = packBlocks(blocks, maxH, docH);
  for (const [i, g] of groups.entries()) {
    const file = path.join(OUT, groups.length > 1 ? `${name}-${i}.jpg` : `${name}.jpg`);
    await page.screenshot({ path: file, type: 'jpeg', quality: 68, captureBeyondViewport: true, clip: { x: 0, y: g.top, width, height: g.bottom - g.top } });
    files.push(file); labels.push(g.labels ?? []);
  }
  console.log(`✓ ${name} → ${groups.length} slice(s): ${groups.map(g => (g.labels ?? []).join(' | ') || '(no heading)').join('  ·  ')}`);
  return { name, url, chunks: files.map(f => path.resolve(f)), labels, sizes: groups.map(g => [width, g.bottom - g.top]), w: width, h: docH };
}

async function login(email) {
  await page.goto(BASE + '/login', { waitUntil: 'networkidle0' });
  // Set the fields outright (typing appends to whatever the page kept) and submit the form itself.
  await page.evaluate((e, pw) => { document.querySelector('input[name=email]').value = e; document.querySelector('input[name=password]').value = pw; }, email, PASSWORD);
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.evaluate(() => document.querySelector('input[name=password]').form.requestSubmit())]);
  if (!page.url().includes('/admin')) throw new Error(`Sign-in as ${email} failed (at ${page.url()})`);
}

async function logout() {
  await page.goto(BASE + '/admin', { waitUntil: 'networkidle0' });
  await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.evaluate(() => document.querySelector('form[action$="/logout"]').submit())]);
}

const shots = [];
for (const [n, u] of [...PUBLIC, ...FLOWS]) shots.push(await shoot(n, u));
// Staff screens are captured at a 1280 × 1000 laptop size: their content is short, and the larger
// type and taller frame fill a letter page instead of leaving its lower half blank.
const DESK = { width: 1280, height: 1000 };
shots.push(await shoot(...ADMIN_PAGES[0], DESK));
for (const [n, u] of MOBILE.filter(([n]) => !n.startsWith('m-admin'))) shots.push(await shoot(n, u, { width: 390, height: 844, mobile: true, fullPage: false }));
await login('admin@environet.test');
for (const [n, u] of ADMIN_PAGES.slice(1)) shots.push(await shoot(n, u, DESK));
for (const [n, u] of MOBILE.filter(([n]) => n.startsWith('m-admin'))) shots.push(await shoot(n, u, { width: 390, height: 844, mobile: true, fullPage: false }));
await logout();
// Each role's overview leads with its own number: procurement sees certificates, HR sees lapsing cards.
await login('procurement@environet.test');
shots.push(await shoot('admin-dashboard-procurement', '/admin', DESK));
await logout();
await login('hr@environet.test');
shots.push(await shoot('admin-dashboard-hr', '/admin', DESK));
await logout();
fs.writeFileSync(path.join(OUT, 'chunks.json'), JSON.stringify(Object.fromEntries(shots.map(s => [s.name, s])), null, 1));
await browser.close();
console.log('done');
