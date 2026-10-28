// Dev helper: screenshots of every key page, phone + desktop, light + dark, and an overflow check.
// node scripts/screens.mjs <outdir>   (dev server on 8112 with demo data)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
const OUT = process.argv[2] || 'dist/screens';
const BASE = process.env.BASE || 'http://127.0.0.1:8112';
fs.mkdirSync(OUT, { recursive: true });
const pub = ['/', '/services', '/past-performance', '/past-performance/tinker-road-bridge', '/about', '/capability-statement', '/contact', '/rfq', '/teaming', '/careers', '/careers/apply', '/careers/uxo-technician-ii', '/privacy', '/photo-credits'];
const adm = ['/admin', '/admin/bids', '/admin/bids/1', '/admin/vendors', '/admin/vendors/2', '/admin/applications', '/admin/applications/1', '/admin/inquiries', '/admin/rooms/1', '/admin/users'];
const browser = await puppeteer.launch({ executablePath: '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', headless: 'new' });
const problems = [];
for (const [vw, vh, tag] of [[390, 844, 'phone'], [1440, 900, 'desk']]) {
  for (const scheme of ['light', 'dark']) {
    const page = await browser.newPage();
    await page.setViewport({ width: vw, height: vh, deviceScaleFactor: 1 });
    await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: scheme }]);
    for (const [i, url] of [...pub, 'LOGIN', ...adm].entries()) {
      if (url === 'LOGIN') {
        await page.goto(BASE + '/login');
        if (!page.url().endsWith('/login')) continue; // already signed in from an earlier pass
        await page.type('input[name=email]', 'admin@environet.test');
        await page.type('input[name=password]', 'password');
        await Promise.all([page.waitForNavigation(), page.click('form .a-btn-primary')]);
        continue;
      }
      await page.goto(BASE + url, { waitUntil: 'networkidle0' });
      await page.addStyleTag({ content: '[data-reveal]{opacity:1!important;transform:none!important;transition:none!important}' });
      await page.evaluate(async () => {
        document.querySelectorAll('img[loading=lazy]').forEach((i) => { i.loading = 'eager'; });
        await Promise.all([...document.images].map((i) => (i.complete ? 0 : new Promise((r) => { i.onload = i.onerror = r; }))));
      });
      const over = await page.evaluate(() => {
        const w = document.documentElement.clientWidth; const bad = [];
        document.querySelectorAll('body *').forEach((e) => { const r = e.getBoundingClientRect(); if (r.right > w + 1 && r.width > 0 && !e.closest('.table-box,.tag-row,.overflow-x-auto,.hero-photo,[aria-hidden=true]')) bad.push(e.tagName + '.' + [...e.classList].slice(0, 3).join('.')); });
        return { scroll: document.documentElement.scrollWidth > w, bad: bad.slice(0, 5) };
      });
      if (over.scroll || over.bad.length) problems.push(`${tag}/${scheme} ${url}: ${JSON.stringify(over)}`);
      const name = `${tag}-${scheme}-${String(i).padStart(2, '0')}-${url.replace(/\W+/g, '_') || 'home'}.png`;
      await page.screenshot({ path: `${OUT}/${name}`, fullPage: true });
    }
    await page.close();
  }
}
await browser.close();
console.log(problems.length ? problems.join('\n') : 'no overflow');
