@props(['current' => 1])

@php
    $steps = [1 => 'Carrinho', 2 => 'Revisão', 3 => 'Pagamento'];
@endphp

<nav aria-label="Etapas da compra" {{ $attributes->merge(['class' => 'mb-6']) }}>
    <ol class="flex items-center gap-2 text-xs font-semibold">
        @foreach ($steps as $number => $label)
            @php($state = $number < $current ? 'done' : ($number === $current ? 'current' : 'next'))
            <li class="flex items-center gap-2" @if ($state === 'current') aria-current="step" @endif>
                <span class="grid h-6 w-6 place-items-center rounded-full border {{ match ($state) {
                    'done' => 'border-brand-600 bg-brand-600 text-slate-950',
                    'current' => 'border-brand-400 text-brand-300',
                    default => 'border-slate-700 text-slate-400',
                } }}" aria-hidden="true">{{ $state === 'done' ? '✓' : $number }}</span>
                <span class="{{ $state === 'next' ? 'text-slate-400' : 'text-slate-200' }}">
                    {{ $label }}@if ($state === 'done')<span class="sr-only"> (concluída)</span>@endif
                </span>
                @unless ($loop->last)
                    <span class="h-px w-6 bg-slate-700 sm:w-10" aria-hidden="true"></span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
