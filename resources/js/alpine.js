/**
 * Alpine components. Livewire runs Alpine in CSP-safe mode, so markup only references
 * these by name (x-data="countdown") and reads properties / calls methods.
 * Configuration is passed through data-* attributes.
 */
export function registerAlpineComponents(Alpine, { prefersReducedMotion }) {
    /**
     * Live countdown to a timestamp: <div x-data="countdown" data-until="2026-10-20T20:00:00+01:00">
     */
    Alpine.data('countdown', () => ({
        label: '',
        days: '00',
        hours: '00',
        minutes: '00',
        seconds: '00',
        done: false,
        timer: null,

        init() {
            this.until = new Date(this.$el.dataset.until).getTime();
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },

        destroy() {
            clearInterval(this.timer);
        },

        tick() {
            const diff = Math.max(0, this.until - Date.now());
            const pad = (n) => String(n).padStart(2, '0');
            this.days = pad(Math.floor(diff / 86400000));
            this.hours = pad(Math.floor((diff % 86400000) / 3600000));
            this.minutes = pad(Math.floor((diff % 3600000) / 60000));
            this.seconds = pad(Math.floor((diff % 60000) / 1000));
            this.done = diff === 0;
            this.label = this.done ? 'Closed' : `${this.days}d ${this.hours}h ${this.minutes}m`;
            if (this.done) {
                clearInterval(this.timer);
            }
        },
    }));

    /**
     * Simple open/close toggle for menus, drawers and FAQ items.
     */
    Alpine.data('toggle', () => ({
        open: false,

        init() {
            this.open = this.$el.dataset.open === 'true';
        },

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        },

        get closed() {
            return !this.open;
        },

        get iconClass() {
            return this.open ? 'rotate-45 bg-leaf-600 text-white' : '';
        },
    }));

    /**
     * Cart drawer: opens on the "cart:open" window event, closes on escape/backdrop.
     */
    Alpine.data('cartDrawer', () => ({
        open: false,

        init() {
            window.addEventListener('cart:open', () => this.show());
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.hide();
                }
            });
        },

        show() {
            this.open = true;
            document.documentElement.classList.add('overflow-hidden');
        },

        hide() {
            this.open = false;
            document.documentElement.classList.remove('overflow-hidden');
        },
    }));

    /**
     * Button that opens the cart drawer.
     */
    Alpine.data('cartOpener', () => ({
        openCart() {
            window.dispatchEvent(new CustomEvent('cart:open'));
        },
    }));

    /**
     * Add-to-cart button: plays the fly-to-cart animation; Livewire handles the server call.
     */
    Alpine.data('addToCart', () => ({
        added: false,

        get buttonLabel() {
            return this.added ? 'Added ✓' : 'Add to order';
        },

        async fly() {
            this.added = true;
            setTimeout(() => (this.added = false), 1400);

            if (prefersReducedMotion) {
                return;
            }

            const { flyToCart } = await import('./motion');
            flyToCart(this.$el);
        },
    }));

    /**
     * Copy text from data-copy to the clipboard.
     */
    Alpine.data('copyText', () => ({
        copied: false,

        async copy() {
            try {
                await navigator.clipboard.writeText(this.$el.dataset.copy || '');
                this.copied = true;
                setTimeout(() => (this.copied = false), 1600);
            } catch {
                this.copied = false;
            }
        },
    }));

    /**
     * Auto-dismissing flash message.
     */
    Alpine.data('flash', () => ({
        visible: true,

        init() {
            setTimeout(() => (this.visible = false), parseInt(this.$el.dataset.timeout || '6000', 10));
        },

        dismiss() {
            this.visible = false;
        },
    }));

    /**
     * Print the current page (packing lists, labels).
     */
    Alpine.data('printPage', () => ({
        print() {
            window.print();
        },
    }));

    /**
     * Ask for confirmation before submitting a form: <form x-data="confirmSubmit" data-message="..." x-on:submit="check">
     */
    Alpine.data('confirmSubmit', () => ({
        check(event) {
            if (!window.confirm(this.$el.dataset.message || 'Are you sure?')) {
                event.preventDefault();
            }
        },
    }));
}
