// Renders /capability-statement to public/downloads/<capability_pdf> (Letter), so Hostinger,
// which has no Node, can serve a real PDF at /capability-statement.pdf.
// Usage: npm run capability (dev server on 8112, ENET_PREVIEW may be on: the banner is not on this page)
import puppeteer from 'puppeteer-core';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8112';
const CHROME = process.env.CHROME_PATH ?? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const NAME = process.env.CAPABILITY_PDF ?? 'Environet-Capability-Statement.pdf';
const OUT = path.resolve('public/downloads', NAME);
fs.mkdirSync(path.dirname(OUT), { recursive: true });

const browser = await puppeteer.launch({ executablePath: CHROME, headless: 'new', args: ['--no-sandbox'] });
const page = await browser.newPage();
await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'light' }]);
await page.goto(BASE + '/capability-statement', { waitUntil: 'networkidle0' });
await page.evaluate(() => document.fonts.ready);
await page.pdf({ path: OUT, format: 'Letter', printBackground: true, preferCSSPageSize: true });
await browser.close();
console.log('Capability statement written:', OUT, Math.round(fs.statSync(OUT).size / 1024) + ' KB');
