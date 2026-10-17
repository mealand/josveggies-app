<x-layouts.store :motion="true" title="Contact">
    <x-page-hero eyebrow="Contact" title="Talk to us." lead="Support runs on WhatsApp. For anything about an order, include your order number (e.g. JV-ACD34F)." />

    <section class="mx-auto grid max-w-4xl gap-6 px-4 py-16 sm:grid-cols-2 sm:px-6">
        <a href="https://wa.me/{{ config('josveggies.brand.support_whatsapp') }}" rel="noopener" target="_blank" class="card group p-8 transition hover:-translate-y-1" data-reveal>
            <p class="eyebrow">WhatsApp</p>
            <p class="mt-3 font-display text-2xl font-semibold text-leaf-900">Chat with support</p>
            <p class="mt-2 text-sm text-leaf-900/60">Fastest way to reach us during a Run.</p>
            <span class="mt-6 inline-block font-semibold text-leaf-700 transition group-hover:translate-x-1">Open WhatsApp →</span>
        </a>
        <a href="mailto:{{ config('josveggies.brand.support_email') }}" class="card group p-8 transition hover:-translate-y-1" data-reveal>
            <p class="eyebrow">Email</p>
            <p class="mt-3 font-display text-2xl font-semibold text-leaf-900">{{ config('josveggies.brand.support_email') }}</p>
            <p class="mt-2 text-sm text-leaf-900/60">For receipts, data requests and partnerships.</p>
            <span class="mt-6 inline-block font-semibold text-leaf-700 transition group-hover:translate-x-1">Send an email →</span>
        </a>
        @if ($hub)
            <div class="card p-8 sm:col-span-2" data-reveal>
                <p class="eyebrow">Pickup hub</p>
                <p class="mt-3 font-display text-2xl font-semibold text-leaf-900">{{ $hub->name }}</p>
                <p class="mt-2 text-leaf-900/70">{{ $hub->address }}</p>
                @if ($hub->directions)
                    <p class="mt-2 text-sm text-leaf-900/55">{{ $hub->directions }}</p>
                @endif
            </div>
        @endif
    </section>
</x-layouts.store>
