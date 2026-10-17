<div>
    @if ($joined)
        <p class="inline-flex items-center gap-2 rounded-full bg-leaf-100 px-4 py-2.5 text-sm font-semibold text-leaf-800">
            <span class="grid size-5 place-items-center rounded-full bg-leaf-600 text-[11px] text-white">✓</span>
            You're on the waitlist — we'll text you when the next Run opens.
        </p>
    @else
        <button type="button" wire:click="join" class="btn-accent" data-magnetic>
            Join the waitlist
        </button>
    @endif
</div>
