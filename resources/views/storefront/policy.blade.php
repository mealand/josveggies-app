<x-layouts.store :motion="true" :title="$title">
    <x-page-hero eyebrow="Policies" :title="$title">
        @if ($policy)
            <p class="mt-4 text-sm text-leaf-900/50" data-hero-fade>Version {{ $policy->version }} · updated {{ $policy->created_at->format('j F Y') }}</p>
        @endif
    </x-page-hero>

    <article class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
        @if ($html)
            <div class="prose-policy">{!! $html !!}</div>
        @else
            <p class="text-leaf-900/60">This policy is being finalised and will be published before launch.</p>
        @endif
    </article>
</x-layouts.store>
