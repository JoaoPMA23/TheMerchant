@extends('layouts.app')

@section('title', 'Meu Carrinho')

@section('content')
<div class="mx-auto max-w-6xl">
    <x-checkout-steps :current="1" />
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <x-page-heading title="Seu carrinho" eyebrow="" description="Confira os anúncios antes de seguir para o checkout." />
        @if($cart && $cart->items->isNotEmpty())
            <div x-data="{ confirmClear: false }" class="shrink-0">
                <button type="button" @click="confirmClear = true" class="rounded-md px-3 text-sm font-semibold text-slate-400 hover:text-rose-300">
                    Esvaziar carrinho
                </button>
                <div x-cloak x-show="confirmClear" x-transition.opacity x-trap.inert.noscroll="confirmClear" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4" @keydown.escape.window="confirmClear = false" @click.self="confirmClear = false" role="dialog" aria-modal="true" aria-labelledby="clear-cart-title">
                    <div class="w-full max-w-sm rounded-lg border border-slate-700 bg-slate-900 p-6">
                        <h2 id="clear-cart-title" class="text-lg font-bold text-white">Esvaziar seu carrinho?</h2>
                        <p class="mt-2 text-sm text-slate-400">Todos os anúncios serão removidos. Você pode adicioná-los novamente depois.</p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="confirmClear = false" class="tm-button-outline">Voltar</button>
                            <form action="{{ route('cart.clear') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md bg-rose-600 px-4 text-sm font-bold text-white hover:bg-rose-500">Esvaziar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if($errors->any())
        <div role="alert" class="mb-6 rounded-md border border-rose-500/40 bg-rose-950/80 p-4 text-sm text-rose-300">
            <ul class="list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$cart || $cart->items->isEmpty())
        <x-empty-state icon="cart" title="Seu carrinho está vazio" description="Explore os anúncios e adicione cosméticos ou serviços para continuar." :href="route('listings.index')" action="Explorar anúncios" class="rounded-lg border border-slate-800" />
    @else
        @php($hasUnavailableItems = $cart->hasUnavailableItems())
        @if($hasUnavailableItems)
            <div role="alert" class="mb-6 rounded-md border border-amber-500/40 bg-amber-950/80 p-4 text-sm text-amber-300">
                Alguns anúncios do seu carrinho mudaram de disponibilidade ou de capacidade. Ajuste ou remova os itens sinalizados antes de seguir para o checkout.
            </div>
        @endif

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <section aria-labelledby="cart-items-title" class="tm-panel !p-0">
                <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
                    <h2 id="cart-items-title" class="text-base font-bold text-white">Anúncios</h2>
                    <span class="text-sm text-slate-400">{{ $cart->items->count() }} {{ $cart->items->count() === 1 ? 'anúncio' : 'anúncios' }}</span>
                </div>
                <ul class="divide-y divide-slate-800">
                    @foreach($cart->items as $item)
                        @php($listing = $item->listing)
                        @php($unavailableReason = $item->unavailableReason())
                        <li @class(['flex gap-4 p-5', 'bg-amber-950/20' => $unavailableReason])>
                            <div class="h-20 w-20 shrink-0 overflow-hidden rounded-md bg-slate-950 sm:h-24 sm:w-24">
                                @if($listing && $listing->primaryImage)
                                    <img src="{{ $listing->primaryImage->url }}" alt="Imagem de {{ $listing->title }}" width="96" height="96" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-slate-500"><x-icon :name="$listing?->category?->type === 'service' ? 'game' : 'gem'" class="!h-7 !w-7" /></div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs text-slate-400">{{ $listing?->game?->name }} · {{ $listing?->category?->name }}</p>
                                        <h3 class="mt-1 truncate text-sm font-bold text-white sm:text-base">
                                            @if($listing && $listing->isAvailable())
                                                <a href="{{ route('listings.show', $listing->slug) }}" class="hover:text-brand-300">{{ $listing->title }}</a>
                                            @else
                                                {{ $listing?->title ?? 'Anúncio indisponível' }}
                                            @endif
                                        </h3>
                                        <p class="mt-1 text-xs text-slate-400">Vendido por <span class="text-slate-300">{{ $listing?->seller?->name }}</span></p>
                                    </div>
                                    <p class="shrink-0 text-right">
                                        <span class="block text-base font-extrabold text-brand-300">R$ {{ number_format($item->subtotal(), 2, ',', '.') }}</span>
                                        @if($item->quantity > 1)
                                            <span class="block text-xs text-slate-400">R$ {{ number_format((float) $item->unit_price, 2, ',', '.') }} por sessão</span>
                                        @endif
                                    </p>
                                </div>

                                @if($unavailableReason)
                                    <div role="alert" class="mt-3 rounded-md border border-amber-500/30 bg-amber-950/60 px-3 py-2 text-xs text-amber-300">
                                        {{ $unavailableReason }}
                                    </div>
                                @elseif($item->hasPriceChanged())
                                    <div role="status" class="mt-3 rounded-md border border-sky-500/30 bg-sky-950/60 px-3 py-2 text-xs text-sky-300">
                                        O preço atual deste anúncio no catálogo é R$ {{ number_format((float) $listing->price, 2, ',', '.') }}.
                                    </div>
                                @endif

                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                    @if($listing?->category?->type === 'service')
                                        <form action="{{ route('cart.update', $item) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <label for="quantity-{{ $item->id }}" class="text-sm text-slate-400">Sessões</label>
                                            <select id="quantity-{{ $item->id }}" name="quantity" onchange="this.form.submit()" @disabled(!$listing->isAvailable()) class="tm-input !w-20 disabled:cursor-not-allowed disabled:opacity-50">
                                                @for($quantity = 1; $quantity <= max(1, (int) $listing->capacity); $quantity++)
                                                    <option value="{{ $quantity }}" @selected($item->quantity === $quantity)>{{ $quantity }}</option>
                                                @endfor
                                            </select>
                                            <span class="text-xs text-slate-400">{{ $listing->session_duration_minutes }} min cada · {{ $listing->capacity }} {{ $listing->capacity === 1 ? 'disponível' : 'disponíveis' }}</span>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-400">Quantidade 1 (item único)</span>
                                    @endif
                                    <form action="{{ route('cart.remove', $item) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Remover {{ $listing?->title }} do carrinho" class="px-2 text-sm font-semibold text-slate-400 hover:text-rose-300">Remover</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-slate-800 px-5 py-2">
                    <a href="{{ route('listings.index') }}" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-slate-400 hover:text-brand-300"><x-icon name="chevron" class="!h-4 !w-4 rotate-180" /> Continuar explorando</a>
                </div>
            </section>

            <aside class="lg:sticky lg:top-4" aria-labelledby="cart-summary-title">
                <div class="tm-panel">
                    <h2 id="cart-summary-title" class="text-base font-bold text-white">Resumo</h2>
                    <dl class="mt-4 space-y-2 text-sm">
                        @foreach($cart->items as $item)
                            <div class="flex justify-between gap-4">
                                <dt class="min-w-0 truncate text-slate-400">{{ $item->listing?->title }}{{ $item->quantity > 1 ? ' × '.$item->quantity : '' }}</dt>
                                <dd class="shrink-0 text-slate-200">R$ {{ number_format($item->subtotal(), 2, ',', '.') }}</dd>
                            </div>
                        @endforeach
                        <div class="flex items-baseline justify-between border-t border-slate-800 pt-4 font-bold text-white">
                            <dt>Total</dt>
                            <dd class="text-xl text-brand-300">R$ {{ number_format($cart->total(), 2, ',', '.') }}</dd>
                        </div>
                    </dl>
                    @if($hasUnavailableItems)
                        <button type="button" disabled aria-disabled="true" class="mt-5 w-full rounded-md bg-slate-800 px-4 text-sm font-bold text-slate-400">
                            Ajuste os itens indisponíveis
                        </button>
                    @else
                        <a href="{{ route('checkout.index') }}" class="tm-button mt-5 w-full">Ir para o checkout</a>
                    @endif
                    <p class="mt-3 text-xs leading-relaxed text-slate-400">Pagamento por PIX. No checkout os itens ficam reservados por {{ \App\Models\Reservation::TTL_MINUTES }} minutos enquanto você paga, e o total é conferido de novo.</p>
                </div>
            </aside>
        </div>
    @endif
</div>
@endsection
