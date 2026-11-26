@extends('layouts.app')

@section('title', $listing->title)

@section('content')
@php
    $isAvailable = $listing->isAvailable();
    $isOwnListing = auth()->check() && auth()->id() === $listing->seller_id;
    $isService = $listing->category->type === 'service';
    $sellerProfile = $listing->seller->sellerProfile;
    $rating = (float) ($sellerProfile?->reputation_score ?? 0);
    $reviews = (int) ($sellerProfile?->total_reviews ?? 0);
    $reportModalHasErrors = $errors->has('reason') || $errors->has('details');
@endphp

<div x-data="{ reportOpen: @js($reportModalHasErrors) }" @keydown.escape.window="reportOpen = false">
    <nav aria-label="Trilha de navegação" class="tm-breadcrumb">
        <a href="{{ route('listings.index') }}">Catálogo</a>
        <x-icon name="chevron" />
        <a href="{{ route('listings.index', ['jogo' => $listing->game->id]) }}">{{ $listing->game->name }}</a>
        <x-icon name="chevron" />
        <a href="{{ route('listings.index', ['categoria' => $listing->category->id]) }}">{{ $listing->category->name }}</a>
    </nav>

    <div class="tm-detail">
        <section class="tm-detail-gallery" aria-label="Fotos do anúncio"
                 x-data="{ images: @js($galleryImages), current: 0, transitioning: false, changeImage(index) { this.transitioning = true; setTimeout(() => { this.current = index; this.transitioning = false }, 120) } }">
            <div @class(['tm-gallery-stage', 'tm-detail-art' => $galleryImages->isEmpty()])>
                <template x-if="images.length > 0">
                    <img :src="images[current]" :class="transitioning ? 'opacity-40' : 'opacity-100'" alt="{{ $listing->title }}" width="1200" height="800" fetchpriority="high" class="max-h-[70vh] w-full object-contain transition-opacity duration-150">
                </template>
                @if($galleryImages->isEmpty())
                    <x-icon :name="$isService ? 'game' : 'gem'" />
                    <p class="mt-3 text-sm text-slate-400">Este anúncio ainda não tem fotos.</p>
                @endif

                <button type="button" x-cloak x-show="images.length > 1" @click="changeImage((current - 1 + images.length) % images.length)" aria-label="Foto anterior" class="tm-gallery-nav left-3"><x-icon name="chevron" class="rotate-180" /></button>
                <button type="button" x-cloak x-show="images.length > 1" @click="changeImage((current + 1) % images.length)" aria-label="Próxima foto" class="tm-gallery-nav right-3"><x-icon name="chevron" /></button>
                <span x-cloak x-show="images.length > 1" x-text="`${current + 1} / ${images.length}`" class="absolute bottom-3 right-3 rounded bg-slate-950/85 px-2 py-1 text-xs text-white" aria-live="polite"></span>
            </div>

            @if($galleryImages->count() > 1)
                <div class="flex gap-2 overflow-x-auto pt-3" aria-label="Selecionar foto">
                    @foreach($galleryImages as $index => $imageUrl)
                        <button type="button" @click="changeImage({{ $index }})" :aria-current="current === {{ $index }} ? 'true' : 'false'" aria-label="Ver foto {{ $index + 1 }}" class="shrink-0 rounded-md border-2" :class="current === {{ $index }} ? 'border-brand-400' : 'border-transparent'">
                            <img src="{{ $imageUrl }}" alt="" width="96" height="72" loading="lazy" class="h-[72px] w-24 rounded object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="tm-detail-side">
            <section class="tm-panel tm-buy-box" aria-label="Compra do anúncio">
                <div class="flex flex-wrap gap-2">
                    <span class="tm-tag">{{ $listing->game->name }}</span>
                    <span class="tm-tag">{{ $isService ? 'Serviço' : 'Item digital' }}</span>
                </div>
                <h1>{{ $listing->title }}</h1>
                <p class="text-sm text-slate-400">{{ $listing->category->name }}</p>

                <div class="tm-buy-price">
                    <span>Preço{{ $isService && $listing->session_duration_minutes ? ' por sessão' : '' }}</span>
                    <strong>R$ {{ number_format($listing->price, 2, ',', '.') }}</strong>
                </div>

                @if($isAvailable && ! $isOwnListing)
                    <form action="{{ route('cart.add', $listing) }}" method="POST"
                          x-data="{ enviando: false }" @submit="enviando = true" @pageshow.window="enviando = false" :aria-busy="enviando">
                        @csrf
                        <button type="submit" :disabled="enviando" class="tm-button w-full disabled:opacity-60 disabled:cursor-wait">
                            <x-icon name="cart" />
                            <span x-text="enviando ? 'Adicionando...' : 'Adicionar ao carrinho'">Adicionar ao carrinho</span>
                        </button>
                    </form>
                @elseif($isOwnListing)
                    <div class="flex min-h-11 items-center justify-center rounded-md bg-slate-800 text-sm font-semibold text-slate-300">Este é seu anúncio</div>
                @else
                    <button type="button" disabled aria-disabled="true" class="w-full rounded-md bg-slate-800 text-sm font-bold text-slate-400">
                        Indisponível · {{ $listing->status_label }}
                    </button>
                @endif

                @guest
                    <a href="{{ route('login') }}" class="tm-button-outline mt-2 w-full">Entrar para falar com o vendedor</a>
                @else
                    @if(! $isOwnListing)
                        <form action="{{ route('chat.start') }}" method="POST" class="mt-2">
                            @csrf
                            <input type="hidden" name="listing_id" value="{{ $listing->id }}">
                            <button type="submit" class="tm-button-outline w-full"><x-icon name="message" /> Falar com o vendedor</button>
                        </form>
                    @endif
                @endguest

                <ul class="tm-buy-notes">
                    <li><x-icon name="check" /> Pagamento por Pix no checkout.</li>
                    <li><x-icon name="message" /> {{ $isService ? 'Horário e detalhes da sessão combinados pelo chat.' : 'Entrega combinada com o vendedor pelo chat.' }}</li>
                    <li><x-icon name="clock" /> O pedido fica na sua conta, do pagamento até a entrega.</li>
                </ul>
                <p class="text-xs text-slate-500">A disponibilidade é conferida de novo ao adicionar ao carrinho.</p>
            </section>

            <section class="tm-panel" aria-labelledby="seller-card-title">
                <h2 id="seller-card-title" class="tm-panel-title">Vendedor</h2>
                <div class="flex items-center gap-3">
                    <span class="tm-avatar tm-avatar-lg" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($listing->seller->name, 0, 1)) }}</span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-white">{{ $listing->seller->name }}</p>
                        <p class="tm-seller-rating" aria-label="{{ $reviews ? 'Avaliação média de '.number_format($rating, 2, ',', '.').' em 5, baseada em '.$reviews.($reviews === 1 ? ' avaliação' : ' avaliações') : 'Vendedor sem avaliações' }}">
                            @if($reviews > 0)
                                <x-icon name="star" /> <strong>{{ number_format($rating, 2, ',', '.') }}/5</strong>
                                <span>({{ $reviews }} {{ $reviews === 1 ? 'avaliação' : 'avaliações' }})</span>
                            @else
                                <span>Sem avaliações</span>
                            @endif
                        </p>
                    </div>
                </div>
                <dl class="tm-seller-stats">
                    <div><dt>Vendas concluídas</dt><dd>{{ number_format($sellerProfile?->total_sales ?? 0, 0, ',', '.') }}</dd></div>
                    <div><dt>Avaliações</dt><dd>{{ $reviews }}</dd></div>
                </dl>
                @if($sellerProfile?->bio)
                    <p class="mt-4 text-sm leading-relaxed text-slate-400">{{ $sellerProfile->bio }}</p>
                @endif

                @auth
                    @if(! $isOwnListing)
                        <button type="button" @click="reportOpen = true" class="mt-4 inline-flex items-center gap-2 text-sm text-rose-400 hover:text-rose-300"><x-icon name="flag" class="!h-4 !w-4" /> Denunciar este anúncio</button>
                    @endif
                @endauth
            </section>
        </div>

        <section class="tm-panel tm-detail-description" aria-labelledby="description-title">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 id="description-title" class="text-lg font-bold text-white">Descrição</h2>
                <span class="text-xs text-slate-500" aria-label="{{ number_format($listing->views_count, 0, ',', '.') }} visualizações">{{ number_format($listing->views_count, 0, ',', '.') }} visualizações</span>
            </div>
            <div class="whitespace-pre-line text-sm leading-relaxed text-slate-300">{{ $listing->description }}</div>
            @if($listing->delivery_conditions)
                <h3 class="mb-2 mt-6 font-bold text-white">Condições de entrega</h3>
                <p class="whitespace-pre-line text-sm text-slate-300">{{ $listing->delivery_conditions }}</p>
            @endif
            @if($isService && $listing->session_duration_minutes)
                <p class="mt-4 text-sm text-brand-300">Preço por sessão de {{ $listing->session_duration_minutes }} minutos. {{ $listing->capacity }} sessão(ões) disponíveis.</p>
                <p class="mt-2 text-xs text-slate-400">Horário a combinar pelo chat · Fuso: {{ $listing->timezone }}</p>
            @endif
            <dl class="tm-detail-facts">
                <div><dt>Jogo</dt><dd>{{ $listing->game->name }}</dd></div>
                <div><dt>Categoria</dt><dd>{{ $listing->category->name }}</dd></div>
                <div><dt>Tipo</dt><dd>{{ $isService ? 'Serviço' : 'Item digital' }}</dd></div>
            </dl>
        </section>
    </div>

    @auth
        @if(! $isOwnListing)
            <div x-cloak x-show="reportOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4" role="presentation" @click.self="reportOpen = false">
                <section x-show="reportOpen" x-trap.inert.noscroll="reportOpen" class="w-full max-w-md rounded-lg border border-slate-800 bg-slate-900 p-6" role="dialog" aria-modal="true" aria-labelledby="report-modal-title" @keydown.escape.stop="reportOpen = false">
                    <h3 id="report-modal-title" class="mb-2 text-base font-bold text-white">Denunciar anúncio</h3>
                    <p class="mb-4 text-sm text-slate-400">Informe o motivo e descreva a situação para a equipe de moderação.</p>
                    <form action="{{ route('listings.report', $listing) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="report-reason" class="mb-1 block text-sm font-semibold text-slate-300">Motivo</label>
                            <select id="report-reason" name="reason" required @error('reason') aria-invalid="true" aria-describedby="report-reason-error" @enderror class="tm-input">
                                <option value="fraude" @selected(old('reason') === 'fraude')>Item inexistente ou tentativa de golpe</option>
                                <option value="termos" @selected(old('reason') === 'termos')>Violação dos termos do jogo</option>
                                <option value="preco" @selected(old('reason') === 'preco')>Preço abusivo ou anúncio duplicado</option>
                                <option value="Outro" @selected(old('reason') === 'Outro')>Outro motivo</option>
                            </select>
                            <x-field-error name="reason" id="report-reason-error" />
                        </div>
                        <div>
                            <label for="report-details" class="mb-1 block text-sm font-semibold text-slate-300">Detalhes e evidências</label>
                            <textarea id="report-details" name="details" @error('details') aria-invalid="true" aria-describedby="report-details-error" @enderror rows="4" minlength="10" maxlength="1000" required placeholder="Descreva o que aconteceu" class="tm-input">{{ old('details') }}</textarea>
                            <x-field-error name="details" id="report-details-error" />
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="reportOpen = false" class="px-3 text-sm font-semibold text-slate-400 hover:text-white">Cancelar</button>
                            <button type="submit" class="rounded-md bg-rose-600 px-4 text-sm font-bold text-white hover:bg-rose-500">Enviar denúncia</button>
                        </div>
                    </form>
                </section>
            </div>
        @endif
    @endauth
</div>
@endsection
