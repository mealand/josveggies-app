import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/**
 * Storefront motion system. Every effect is declared in markup with data attributes:
 *
 *  data-reveal                 fade/slide in when scrolled into view (batched, staggered)
 *  data-hero-line              hero headline lines rise in sequence
 *  data-parallax="0.2"         element drifts at a fraction of scroll speed
 *  data-meter="64"             bar grows to the given percent
 *  data-count-to="12"          number counts up from 0
 *  data-tilt                   card tilts towards the pointer (fine pointers only)
 *  data-magnetic               button is gently pulled towards the pointer
 *  data-draw                   SVG path draws itself on scroll
 *  data-timeline-progress="40" vertical progress line fills to percent
 */
export function initMotion() {
    const ctx = gsap.context(() => {
        heroIntro();
        reveals();
        parallax();
        meters();
        counters();
        drawPaths();
        timelines();
    });

    if (window.matchMedia('(pointer: fine)').matches) {
        tilt();
        magnetic();
    }

    document.addEventListener('livewire:navigating', () => ctx.revert(), { once: true });
}

function heroIntro() {
    const lines = gsap.utils.toArray('[data-hero-line]');
    if (!lines.length) {
        return;
    }

    gsap.timeline({ defaults: { ease: 'expo.out' } })
        .from(lines, { yPercent: 110, opacity: 0, duration: 1.1, stagger: 0.12 })
        .from('[data-hero-fade]', { y: 24, opacity: 0, duration: 0.9, stagger: 0.08 }, '-=0.7')
        .from('[data-hero-veg]', { scale: 0.4, opacity: 0, rotate: -25, duration: 1.2, stagger: 0.07, ease: 'back.out(1.7)' }, '-=1');
}

function reveals() {
    const items = gsap.utils.toArray('[data-reveal]');
    if (!items.length) {
        return;
    }

    ScrollTrigger.batch(items, {
        start: 'top 88%',
        once: true,
        onEnter: (batch) =>
            gsap.to(batch, { opacity: 1, y: 0, duration: 0.9, ease: 'expo.out', stagger: 0.09, overwrite: true }),
    });
}

function parallax() {
    gsap.utils.toArray('[data-parallax]').forEach((el) => {
        const speed = parseFloat(el.dataset.parallax || '0.2');
        gsap.to(el, {
            yPercent: -100 * speed,
            ease: 'none',
            scrollTrigger: { trigger: el.closest('section') || el, start: 'top bottom', end: 'bottom top', scrub: 0.6 },
        });
    });
}

function meters() {
    gsap.utils.toArray('[data-meter]').forEach((el) => {
        const value = Math.min(100, Math.max(0, parseFloat(el.dataset.meter || '0')));
        gsap.fromTo(
            el,
            { width: '0%' },
            { width: `${value}%`, duration: 1.6, ease: 'expo.out', delay: 0.3, scrollTrigger: { trigger: el, start: 'top 95%', once: true } },
        );
    });
}

function counters() {
    gsap.utils.toArray('[data-count-to]').forEach((el) => {
        const target = parseInt(el.dataset.countTo || '0', 10);
        const state = { value: 0 };
        gsap.to(state, {
            value: target,
            duration: 1.4,
            ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 95%', once: true },
            onUpdate: () => {
                el.textContent = Math.round(state.value).toLocaleString();
            },
        });
    });
}

function drawPaths() {
    gsap.utils.toArray('[data-draw]').forEach((path) => {
        const length = path.getTotalLength();
        gsap.fromTo(
            path,
            { strokeDasharray: length, strokeDashoffset: length },
            { strokeDashoffset: 0, ease: 'none', scrollTrigger: { trigger: path.closest('section') || path, start: 'top 70%', end: 'bottom 60%', scrub: 0.8 } },
        );
    });
}

function timelines() {
    gsap.utils.toArray('[data-timeline-progress]').forEach((el) => {
        const value = parseFloat(el.dataset.timelineProgress || '0');
        gsap.fromTo(el, { height: '0%' }, { height: `${value}%`, duration: 1.6, ease: 'expo.inOut', delay: 0.2 });
    });

    const steps = gsap.utils.toArray('[data-timeline-step]');
    if (steps.length) {
        gsap.from(steps, { x: -16, opacity: 0, duration: 0.7, ease: 'expo.out', stagger: 0.08 });
    }
}

function tilt() {
    gsap.utils.toArray('[data-tilt]').forEach((card) => {
        const rotateX = gsap.quickTo(card, 'rotationX', { duration: 0.6, ease: 'power3' });
        const rotateY = gsap.quickTo(card, 'rotationY', { duration: 0.6, ease: 'power3' });
        gsap.set(card, { transformPerspective: 900 });

        card.addEventListener('pointermove', (event) => {
            const rect = card.getBoundingClientRect();
            const x = (event.clientX - rect.left) / rect.width - 0.5;
            const y = (event.clientY - rect.top) / rect.height - 0.5;
            rotateY(x * 7);
            rotateX(-y * 7);
        });

        card.addEventListener('pointerleave', () => {
            rotateX(0);
            rotateY(0);
        });
    });
}

function magnetic() {
    gsap.utils.toArray('[data-magnetic]').forEach((button) => {
        const moveX = gsap.quickTo(button, 'x', { duration: 0.5, ease: 'elastic.out(1, 0.4)' });
        const moveY = gsap.quickTo(button, 'y', { duration: 0.5, ease: 'elastic.out(1, 0.4)' });

        button.addEventListener('pointermove', (event) => {
            const rect = button.getBoundingClientRect();
            moveX((event.clientX - rect.left - rect.width / 2) * 0.25);
            moveY((event.clientY - rect.top - rect.height / 2) * 0.35);
        });

        button.addEventListener('pointerleave', () => {
            moveX(0);
            moveY(0);
        });
    });
}

/**
 * "Fly to cart": a small dot travels from the clicked button to the cart icon.
 */
export function flyToCart(source) {
    const target = document.querySelector('[data-cart-target]');
    if (!source || !target) {
        return;
    }

    const from = source.getBoundingClientRect();
    const to = target.getBoundingClientRect();
    const dot = document.createElement('span');
    dot.className = 'pointer-events-none fixed z-[60] size-4 rounded-full bg-tomato-500 shadow-lg shadow-tomato-600/40';
    dot.style.left = `${from.left + from.width / 2 - 8}px`;
    dot.style.top = `${from.top + from.height / 2 - 8}px`;
    document.body.appendChild(dot);

    gsap.timeline({ onComplete: () => dot.remove() })
        .to(dot, { x: to.left + to.width / 2 - (from.left + from.width / 2), duration: 0.75, ease: 'power1.inOut' }, 0)
        .to(dot, { y: to.top + to.height / 2 - (from.top + from.height / 2), duration: 0.75, ease: 'back.in(1.4)' }, 0)
        .to(dot, { scale: 0.3, opacity: 0.4, duration: 0.2 }, 0.6)
        .fromTo(target, { scale: 1 }, { scale: 1.25, duration: 0.18, yoyo: true, repeat: 1, ease: 'power2.out' }, 0.7);
}
