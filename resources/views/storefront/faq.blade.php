<x-layouts.store :motion="true" title="FAQ">
    <x-page-hero eyebrow="FAQ" title="Questions, answered." lead="Can't find what you need? Message us on WhatsApp — a real person replies." />

    <section class="mx-auto max-w-3xl space-y-3 px-4 py-16 sm:px-6">
        @foreach ($faqs as $faq)
            <x-faq-item :question="$faq['q']" :answer="$faq['a']" data-reveal />
        @endforeach
    </section>
</x-layouts.store>
