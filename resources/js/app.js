import Alpine from 'alpinejs';
import { startMotion, switchTheme } from './motion';

document.documentElement.classList.add('js'); // also set in <head>; kept for safety

/* Theme toggle. The initial theme is set by an inline script in <head> to avoid a flash. */
window.toggleTheme = (event) => {
    const root = document.documentElement;
    const current = root.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    const next = current === 'dark' ? 'light' : 'dark';
    switchTheme(() => { root.dataset.theme = next; }, event);
    try { localStorage.setItem('theme', next); } catch (e) { /* storage blocked: the choice lasts this page only */ }
};

/* Submit buttons: one press only, with a visible "Sending…" state. */
Alpine.data('sendForm', () => ({
    submitting: false,
    submit(e) {
        if (!e.target.checkValidity()) return;
        this.submitting = true;
    },
}));

/* Teaming registration: the company type decides which documents and the UEI are required. */
Alpine.data('teamingForm', (type, required, needsUei) => ({
    submitting: false,
    type,
    required,
    needsUei,
    submit(e) {
        if (!e.target.checkValidity()) return;
        this.submitting = true;
    },
}));

/* Job application: certification rows are all in the HTML (usable without JS); show a few, add more on request. */
Alpine.data('certRows', (shown, max) => ({
    shown,
    max,
    add() {
        if (this.shown >= this.max) return;
        this.shown += 1;
        this.$nextTick(() => document.getElementById(`cert-kind-${this.shown - 1}`)?.focus());
    },
}));

/* Column chart hover: one tooltip that follows the focused or hovered column. */
Alpine.data('columnChart', () => ({
    tip: null,
    show(el) {
        const box = el.getBoundingClientRect();
        const host = this.$root.getBoundingClientRect();
        this.tip = { label: el.dataset.label, value: el.dataset.value, x: box.left - host.left + box.width / 2, y: box.top - host.top };
    },
    hide() { this.tip = null; },
}));

/* Dashboard chrome: density, mobile nav, "/" to search. */
Alpine.data('adminShell', () => ({
    nav: false,
    density: (() => { try { return localStorage.getItem('density') || 'comfortable'; } catch (e) { return 'comfortable'; } })(),
    init() {
        this.$el.dataset.density = this.density;
        window.addEventListener('keydown', (e) => {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName);
            if (e.key === '/' && !typing) {
                const search = document.querySelector('[data-search]');
                if (search) { e.preventDefault(); search.focus(); }
            }
        });
    },
    toggleDensity() {
        this.density = this.density === 'compact' ? 'comfortable' : 'compact';
        this.$el.dataset.density = this.density;
        try { localStorage.setItem('density', this.density); } catch (e) { /* ignore */ }
    },
}));

window.Alpine = Alpine;
Alpine.start();
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startMotion);
else startMotion();
