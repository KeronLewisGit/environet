// Stills of the site's motion for the proposal's "Motion and interaction" page, and an optional
// short MP4 of the home page. Motion is on here (the main capture runs with reduced motion, so
// its screenshots show every element in its final state).
// Usage: node scripts/proposal/motion.mjs [--video <path.mp4>]   (dev server on 8112, demo data)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8112';
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const OUT = path.resolve('dist/proposal/shots/motion');
const videoArg = process.argv.indexOf('--video');
const VIDEO = videoArg > -1 ? path.resolve(process.argv[videoArg + 1]) : null;
const wait = (ms) => new Promise((r) => setTimeout(r, ms));

fs.rmSync(OUT, { recursive: true, force: true });
fs.mkdirSync(OUT, { recursive: true });

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--hide-scrollbars', '--no-sandbox', '--lang=en-US'] });
const fresh = async () => {
  const page = await browser.newPage();
  await page.setViewport({ width: 1440, height: 900, deviceScaleFactor: 1 });
  await page.emulateMediaFeatures([{ name: 'prefers-reduced-motion', value: 'no-preference' }, { name: 'prefers-color-scheme', value: 'light' }]);
  await page.evaluateOnNewDocument(() => { try { localStorage.removeItem('theme'); } catch (e) { /* ignore */ } });
  return page;
};
// Frames are shown about 3.4 inches wide in the PDF: 1100 pixels at JPEG quality 72 is plenty.
const shot = async (page, name) => {
  const f = path.join(OUT, `${name}.jpg`);
  await page.screenshot({ path: f, type: 'jpeg', quality: 90, captureBeyondViewport: false });
  execFileSync('sips', ['-Z', '1100', '-s', 'formatOptions', '72', f, '--out', f], { stdio: 'ignore' });
};
// Put an element's top a set distance below the sticky header, without smooth scrolling.
const scrollTo = (page, selector, offset = 110) => page.evaluate((s, o) => {
  document.documentElement.style.scrollBehavior = 'auto';
  const el = document.querySelector(s);
  scrollTo(0, el.getBoundingClientRect().top + scrollY - o);
}, selector, offset);

// 1. Hero entrance, mid-way, and 2. the survey sweep crossing the registration tag.
let page = await fresh();
page.goto(`${BASE}/`);
await page.waitForSelector('.hero-in h1');
await wait(380);
await shot(page, 'hero-entrance');
await page.waitForNetworkIdle();
await wait(1900);
await shot(page, 'hero-sweep');
await page.close();

// 3. Count-ups mid-count, with the capability strip moving above them.
page = await fresh();
await page.goto(`${BASE}/`, { waitUntil: 'networkidle0' });
await wait(1200);
await scrollTo(page, '[data-marquee]', 380);
await wait(450);
await shot(page, 'count-up');

// 4. Timeline rail drawing as the list scrolls.
await scrollTo(page, '[data-timeline]', -120);
await wait(500);
await shot(page, 'timeline');

// 5. Map routes drawing out from Honolulu, then 6. complete.
await scrollTo(page, '[data-map]', 120);
await wait(950);
await shot(page, 'map-drawing');
await wait(2600);
await shot(page, 'map-drawn');

// 7. Theme switch: the dark theme opening as a circle from the toggle.
await page.evaluate(() => scrollTo(0, 0));
await wait(300);
const toggle = await page.$('header button[aria-label="Switch light or dark theme"]');
const box = await toggle.boundingBox();
await page.mouse.click(box.x + box.width / 2, box.y + box.height / 2);
await wait(200);
await shot(page, 'theme-wipe');
await page.close();

if (VIDEO) {
  // About 20 s of the home page: entrance and sweep, a slow scroll through the strip, figures,
  // timeline and map, then back up and a theme switch.
  page = await fresh();
  const webm = VIDEO.replace(/\.mp4$/, '.webm');
  page.goto(`${BASE}/`);
  await page.waitForSelector('.hero-in');
  const rec = await page.screencast({ path: webm });
  await wait(4600);
  const glide = (to, ms) => page.evaluate((to, ms) => new Promise((done) => {
    document.documentElement.style.scrollBehavior = 'auto';
    const from = scrollY; const t0 = performance.now();
    const step = (now) => {
      const p = Math.min(1, (now - t0) / ms); const e = p < 0.5 ? 2 * p * p : 1 - (-2 * p + 2) ** 2 / 2;
      scrollTo(0, from + (to - from) * e);
      p < 1 ? requestAnimationFrame(step) : done();
    };
    requestAnimationFrame(step);
  }), to, ms);
  const y = (sel, off) => page.evaluate((s, o) => document.querySelector(s).getBoundingClientRect().top + scrollY - o, sel, off);
  await glide(await y('[data-marquee]', 330), 1800); await wait(1600);
  await glide(await y('[data-timeline]', 200), 2600); await wait(400);
  await glide(await y('[data-timeline]', -500), 2600); await wait(300);
  await glide(await y('[data-map]', 130), 2000); await wait(3000);
  await glide(0, 1800); await wait(400);
  const t = await page.$('header button[aria-label="Switch light or dark theme"]');
  const b = await t.boundingBox();
  await page.mouse.move(b.x + b.width / 2, b.y + b.height / 2);
  await page.mouse.click(b.x + b.width / 2, b.y + b.height / 2);
  await wait(1500);
  await rec.stop();
  await page.close();
  execFileSync('ffmpeg', ['-loglevel', 'error', '-y', '-i', webm, '-vf', 'scale=1280:-2,fps=30', '-c:v', 'libx264', '-preset', 'slow', '-crf', '27', '-pix_fmt', 'yuv420p', '-movflags', '+faststart', '-an', VIDEO]);
  fs.rmSync(webm);
  const mb = fs.statSync(VIDEO).size / 1024 / 1024;
  console.log(`Video: ${VIDEO} (${mb.toFixed(1)} MB)`);
  if (mb > 10) throw new Error('The video is over 10 MB.');
}

await browser.close();
console.log(`Motion stills in ${OUT}: ${fs.readdirSync(OUT).join(', ')}`);
