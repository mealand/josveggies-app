@props(['question', 'answer'])

<div x-data="toggle" {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    <button type="button" x-on:click="toggle" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left font-semibold text-leaf-900" x-bind:aria-expanded="open">
        <span>{{ $question }}</span>
        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-leaf-100 text-leaf-700 transition duration-300" x-bind:class="iconClass">+</span>
    </button>
    <div x-show="open" x-collapse.duration.400ms x-cloak>
        <p class="px-6 pb-6 text-sm leading-relaxed text-leaf-900/70">{{ $answer }}</p>
    </div>
</div>
