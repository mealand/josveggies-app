@php
    $messages = array_filter([
        'success' => session('success') ?? session('status'),
        'error' => session('error'),
    ]);
@endphp

@if ($messages || $errors->any())
    <div class="pointer-events-none fixed inset-x-0 top-20 z-50 flex flex-col items-center gap-2 px-4 no-print" aria-live="polite">
        @foreach ($messages as $type => $message)
            <div x-data="flash" x-show="visible" x-transition.opacity.duration.300ms data-timeout="7000"
                 class="pointer-events-auto flex w-full max-w-md items-start gap-3 rounded-2xl px-4 py-3 text-sm font-medium shadow-xl {{ $type === 'error' ? 'bg-tomato-600 text-white' : 'bg-leaf-800 text-white' }}"
                 role="{{ $type === 'error' ? 'alert' : 'status' }}">
                <span class="mt-0.5">{{ $type === 'error' ? '⚠' : '✓' }}</span>
                <p class="flex-1">{{ $message }}</p>
                <button type="button" x-on:click="dismiss" class="opacity-70 hover:opacity-100" aria-label="Dismiss">✕</button>
            </div>
        @endforeach
        @if ($errors->any() && ! $messages)
            <div x-data="flash" x-show="visible" data-timeout="9000"
                 class="pointer-events-auto w-full max-w-md rounded-2xl bg-tomato-600 px-4 py-3 text-sm text-white shadow-xl" role="alert">
                <p class="font-semibold">Please check the form:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
