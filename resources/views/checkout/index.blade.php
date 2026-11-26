@extends('layouts.app')

@section('title', 'Finalizar Compra')

@section('content')
<div class="mx-auto max-w-6xl">
    <x-checkout-steps :current="2" />
    <x-page-heading title="Revisão do pedido" eyebrow="" description="Confira os itens, deixe as instruções de entrega e gere o PIX." class="mb-6" />

    <form id="checkout-form" action="{{ route('checkout.process') }}" method="POST"
          x-data="{ enviando: false }" @submit="enviando = true" @pageshow.window="enviando = false" :aria-busy="enviando"
          class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
        @csrf
        {{-- Chave idempotente: reenvio do mesmo formulário devolve o mesmo pedido. --}}
        <input type="hidden" name="checkout_key" value="{{ old('checkout_key', (string) \Illuminate\Support\Str::uuid()) }}">

        <div class="space-y-6">
            <section class="tm-panel !p-0" aria-labelledby="checkout-items-title">
                <h2 id="checkout-items-title" class="border-b border-slate-800 px-5 py-4 text-base font-bold text-white">Itens e instruções de entrega</h2>
                <ul class="divide-y divide-slate-800">
                    @foreach($cart->items as $item)
                        @php($isService = $item->listing->category?->type === 'service')
                        <li class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-400">{{ $item->listing->game?->name }} · {{ $item->listing->category?->name }}</p>
                                    <p class="mt-1 text-sm font-bold text-white">{{ $item->listing->title }} <span class="font-normal text-slate-400">× {{ $item->quantity }}</span></p>
                                    <p class="mt-1 text-xs text-slate-400">Vendido por {{ $item->listing->seller?->name }}</p>
                                </div>
                                <span class="shrink-0 font-bold text-brand-300">R$ {{ number_format($item->unit_price * $item->quantity, 2, ',', '.') }}</span>
                            </div>
                            <label for="item-instructions-{{ $item->id }}" class="mb-1 mt-4 block text-sm text-slate-300">
                                {{ $isService ? 'Preferência de horário, fuso horário e detalhes para o coaching' : 'Instruções de entrega ou Trade URL' }} <span class="text-slate-400">(opcional)</span>
                            </label>
                            <textarea id="item-instructions-{{ $item->id }}" name="item_instructions[{{ $item->id }}]" rows="2" maxlength="1000" @error('item_instructions.'.$item->id) aria-invalid="true" aria-describedby="item-instructions-{{ $item->id }}-error" @enderror class="tm-input">{{ old('item_instructions.'.$item->id) }}</textarea>
                            <x-field-error :name="'item_instructions.'.$item->id" :id="'item-instructions-'.$item->id.'-error'" />
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="tm-panel" aria-labelledby="checkout-notes-title">
                <h2 id="checkout-notes-title" class="mb-3 text-base font-bold text-white">Observações gerais</h2>
                <label for="checkout-notes" class="mb-1 block text-sm text-slate-300">Recado para os vendedores <span class="text-slate-400">(opcional)</span></label>
                <textarea id="checkout-notes" name="notes" rows="2" maxlength="500" placeholder="Algo que vale para o pedido inteiro" @error('notes') aria-invalid="true" aria-describedby="checkout-notes-error" @enderror class="tm-input">{{ old('notes') }}</textarea>
                <x-field-error name="notes" id="checkout-notes-error" />
            </section>
        </div>

        <aside class="lg:sticky lg:top-4" aria-labelledby="checkout-summary-title">
            <div class="tm-panel">
                <h2 id="checkout-summary-title" class="text-base font-bold text-white">Pagamento</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-400">{{ $cart->items->count() }} {{ $cart->items->count() === 1 ? 'anúncio' : 'anúncios' }}</dt>
                        <dd class="text-slate-200">R$ {{ number_format($cart->total(), 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-400">Forma de pagamento</dt>
                        <dd class="text-slate-200">PIX</dd>
                    </div>
                    <div class="flex items-baseline justify-between border-t border-slate-800 pt-4 font-bold text-white">
                        <dt>Total a pagar</dt>
                        <dd class="text-2xl text-brand-300">R$ {{ number_format($cart->total(), 2, ',', '.') }}</dd>
                    </div>
                </dl>

                <div class="mt-5 flex items-start gap-3">
                    <input type="checkbox" name="terms_agreed" id="terms" value="1" required @checked(old('terms_agreed')) @error('terms_agreed') aria-invalid="true" aria-describedby="terms-error" @enderror class="mt-1 h-4 w-4 shrink-0 rounded border-slate-700 bg-slate-900">
                    <label for="terms" class="text-xs leading-relaxed text-slate-300">
                        Declaro estar ciente de que as negociações devem seguir as políticas das plataformas de jogos e que meus dados de pagamento serão processados pela AbacatePay, o processador de pagamento credenciado.
                    </label>
                </div>
                <x-field-error name="terms_agreed" id="terms-error" />
                @if($errors->any())
                    <div role="alert" class="mt-4 rounded-md border border-rose-800 bg-rose-950/50 p-3 text-sm text-rose-300">Revise os campos destacados antes de continuar.</div>
                @endif

                <button type="submit" :disabled="enviando" class="tm-button mt-5 w-full disabled:cursor-wait disabled:opacity-60">
                    <x-icon name="lock" />
                    <span x-text="enviando ? 'Gerando o PIX...' : 'Confirmar pedido e gerar PIX'">Confirmar pedido e gerar PIX</span>
                </button>
                <p class="mt-3 text-xs leading-relaxed text-slate-400">Os itens ficam reservados por {{ \App\Models\Reservation::TTL_MINUTES }} minutos enquanto o PIX é pago.</p>
                <a href="{{ route('cart.index') }}" class="mt-2 inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-slate-400 hover:text-brand-300"><x-icon name="chevron" class="!h-4 !w-4 rotate-180" /> Voltar ao carrinho</a>
            </div>
        </aside>
    </form>
</div>
@endsection
