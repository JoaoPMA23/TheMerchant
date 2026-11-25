{{--
    Botão que abre um modal de confirmação antes de enviar uma ação destrutiva.
    Mesmo padrão do "Esvaziar carrinho": x-trap mantém o foco no modal, Esc e clique fora fecham.
--}}
@props([
    'action',
    'title',
    'message',
    'confirmLabel' => 'Confirmar',
    'method' => 'DELETE',
    'triggerClass' => '',
    'triggerTitle' => null,
])

@php($dialogId = 'confirm-'.\Illuminate\Support\Str::random(8))

<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'inline']) }}>
    <button type="button" @click="open = true" class="{{ $triggerClass }}" @if ($triggerTitle) title="{{ $triggerTitle }}" @endif>
        {{ $slot }}
    </button>
    <div x-cloak x-show="open" x-transition.opacity x-trap.inert.noscroll="open"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4"
         @keydown.escape.window="open = false" @click.self="open = false"
         role="dialog" aria-modal="true" aria-labelledby="{{ $dialogId }}-title" aria-describedby="{{ $dialogId }}-message">
        <div class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-900 p-6 text-left shadow-2xl">
            <h2 id="{{ $dialogId }}-title" class="text-lg font-bold text-white">{{ $title }}</h2>
            <p id="{{ $dialogId }}-message" class="mt-2 text-sm text-slate-400">{{ $message }}</p>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-200 hover:bg-slate-800">Voltar</button>
                <form action="{{ $action }}" method="POST">
                    @csrf
                    @method($method)
                    <button type="submit" class="rounded-xl bg-rose-500 px-4 py-2 text-sm font-bold text-white hover:bg-rose-400">{{ $confirmLabel }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
