/*
 * Motion for the public site. Plain IntersectionObserver + requestAnimationFrame, no library.
 * Rules: content is complete and visible in the HTML (no-JS and reduced motion see the final
 * state); JS only adds the "before" state and animates transform, opacity or SVG stroke.
 */
const reduced = () => matchMedia('(prefers-reduced-motion: reduce)').matches;

const onView = (els, fn, options = { rootMargin: '0px 0px -12% 0px' }) => {
    if (!els.length) return;
    if (!('IntersectionObserver' in window)) { els.forEach(fn); return; }
    const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => { if (e.isIntersecting) { io.unobserve(e.target); fn(e.target); } });
    }, options);
    els.forEach((el) => io.observe(el));
};

/* Staggered reveal: siblings that reveal together step in 70 ms apart (at most four steps). */
export const reveal = () => {
    const items = [...document.querySelectorAll('[data-reveal]')];
    items.forEach((el) => {
        const peers = [...el.parentElement.children].filter((c) => c.hasAttribute('data-reveal'));
        const i = peers.indexOf(el);
        if (i > 0) el.style.setProperty('--reveal-delay', `${Math.min(i, 4) * 70}ms`);
    });
    onView(items, (el) => el.classList.add('is-visible'), { rootMargin: '0px 0px -8% 0px' });
};

/* Count-ups: the final figure is already in the HTML; count up to it once it is on screen. */
const countUps = () => {
    const els = [...document.querySelectorAll('[data-count]')];
    if (reduced()) return;
    onView(els, (el) => {
        const target = parseFloat(el.dataset.count);
        const decimals = parseInt(el.dataset.decimals || '0', 10);
        const fmt = new Intl.NumberFormat('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
        const final = el.textContent;
        const text = (v) => `${el.dataset.prefix || ''}${fmt.format(v)}${el.dataset.suffix || ''}`;
        const t0 = performance.now();
        const ms = 1400;
        const step = (now) => {
            const p = Math.min(1, (now - t0) / ms);
            const eased = 1 - (1 - p) ** 3;
            el.textContent = p < 1 ? text(target * eased) : final;
            if (p < 1) requestAnimationFrame(step);
        };
        el.textContent = text(0);
        requestAnimationFrame(step);
    });
};

/* Pacific map: routes draw when it scrolls into view (CSS does the drawing). */
const maps = () => onView([...document.querySelectorAll('[data-map]')], (el) => el.classList.add('is-drawn'), { rootMargin: '0px 0px -20% 0px' });

/* Capability strip: copy the list once for a seamless loop; pause button and hover/focus stop it. */
const marquees = () => {
    document.querySelectorAll('[data-marquee]').forEach((band) => {
        if (reduced()) return;
        const track = band.querySelector('.capstrip-track');
        const button = band.querySelector('[data-marquee-toggle]');
        [...track.children].forEach((li) => {
            const copy = li.cloneNode(true);
            copy.setAttribute('aria-hidden', 'true');
            track.appendChild(copy);
        });
        band.classList.add('is-running');
        requestAnimationFrame(() => band.style.setProperty('--marquee-duration', `${Math.max(30, track.scrollWidth / 2 / 38)}s`));
        button.hidden = false;
        button.addEventListener('click', () => {
            const paused = band.classList.toggle('is-paused');
            button.setAttribute('aria-pressed', String(paused));
            button.querySelector('.sr-only').textContent = paused ? 'Play the moving list' : 'Pause the moving list';
        });
    });
};

/* Scroll-linked pieces (progress bar, timeline rail), batched into one rAF per frame. */
const scrollLinked = () => {
    const bar = document.querySelector('[data-progress]');
    const lines = [...document.querySelectorAll('[data-timeline]')];
    if (!bar && !lines.length) return;
    if (reduced()) { lines.forEach((l) => l.style.setProperty('--tl', '1')); }
    let queued = false;
    const update = () => {
        queued = false;
        const doc = document.documentElement;
        const max = doc.scrollHeight - innerHeight;
        if (bar) bar.style.transform = `scaleX(${max > 0 ? Math.min(1, scrollY / max) : 0})`;
        if (!reduced()) {
            lines.forEach((l) => {
                const r = l.getBoundingClientRect();
                const p = Math.max(0, Math.min(1, (innerHeight * 0.75 - r.top) / r.height));
                l.style.setProperty('--tl', p.toFixed(3));
            });
        }
    };
    const queue = () => { if (!queued) { queued = true; requestAnimationFrame(update); } };
    addEventListener('scroll', queue, { passive: true });
    addEventListener('resize', queue);
    update();
};

/*
 * Theme toggle with a circular wipe from the button (View Transitions), falling back to an
 * instant switch where the API is missing or motion is reduced.
 */
export const switchTheme = (apply, event) => {
    if (!document.startViewTransition || reduced()) { apply(); return; }
    const x = event?.clientX || innerWidth - 40;
    const y = event?.clientY || 40;
    const r = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));
    const root = document.documentElement;
    root.classList.add('theme-wipe');
    const t = document.startViewTransition(apply);
    t.ready.then(() => {
        root.animate({ clipPath: [`circle(0px at ${x}px ${y}px)`, `circle(${r}px at ${x}px ${y}px)`] },
            { duration: 520, easing: 'cubic-bezier(0.4, 0, 0.2, 1)', pseudoElement: '::view-transition-new(root)' });
    }).catch(() => {});
    t.finished.finally(() => root.classList.remove('theme-wipe'));
};

export const startMotion = () => {
    reveal();
    countUps();
    maps();
    marquees();
    scrollLinked();
};
