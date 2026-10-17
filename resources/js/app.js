/**
 * JosVeggies front-end entry.
 *
 * Livewire 4 bundles Alpine in CSP-safe mode, so Alpine expressions in Blade stay simple
 * (property reads and method calls). All behaviour lives in the components registered here.
 */
import { registerAlpineComponents } from './alpine';

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

document.addEventListener('alpine:init', () => {
    registerAlpineComponents(window.Alpine, { prefersReducedMotion });
});

/**
 * Motion is heavy on the storefront, so GSAP is lazy-loaded only on pages that ask for it.
 */
async function bootMotion() {
    const root = document.documentElement;

    if (prefersReducedMotion || !document.querySelector('[data-motion]')) {
        root.classList.remove('js-motion');
        return;
    }

    try {
        const { initMotion } = await import('./motion');
        initMotion();
    } finally {
        root.classList.add('motion-ready');
    }
}

async function bootScanner() {
    const el = document.querySelector('[data-qr-scanner]');
    if (!el) {
        return;
    }

    const { initScanner } = await import('./scanner');
    initScanner(el);
}

function boot() {
    bootMotion();
    bootScanner();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

document.addEventListener('livewire:navigated', boot);
