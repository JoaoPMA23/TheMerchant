@extends('layouts.app')
@section('title', 'Skins e coaching entre jogadores')
@section('body-class', 'tm-home')
@push('styles')
    <meta name="description" content="Explore skins, cosméticos e coaching para seus jogos favoritos. Compre e venda no TheMerchant, o marketplace feito para quem joga.">
@endpush
@section('content')
<section class="tm-intro" aria-labelledby="intro-title">
    <div>
        <h1 id="intro-title">Skins e coaching vendidos por outros jogadores</h1>
        <p>Você paga por Pix, combina a entrega com o vendedor pelo chat e acompanha tudo pelo pedido.</p>
    </div>
    <div class="tm-intro-actions">
        <a class="tm-button" href="{{ route('listings.index') }}">Ver catálogo</a>
        <a class="tm-button-outline" href="{{ route('seller.application') }}">Anunciar um item</a>
    </div>
</section>

<section class="tm-section" id="jogos" aria-labelledby="games-title">
    <div class="tm-section-heading">
        <h2 id="games-title">Jogos</h2>
        <a href="{{ route('listings.index') }}" class="tm-section-link">Ver todos <x-icon name="chevron" /></a>
    </div>
    <div class="tm-game-grid">
        @forelse ($games as $game)
            @php
                $bundledCover = file_exists(public_path('images/marketplace/' . $game->slug . '.jpg')) ? asset('images/marketplace/' . $game->slug . '.jpg') : null;
                $cover = $game->cover_image_url ?? $bundledCover;
            @endphp
            <a href="{{ route('listings.index', ['jogo' => $game->id]) }}" class="tm-game-card">
                <span class="tm-game-cover">
                    @if ($cover)
                        {{-- A capa é decorativa (o nome já está no card). Se falhar, tenta a capa local e depois as iniciais. --}}
                        <img src="{{ $cover }}" alt="" loading="lazy" width="320" height="150"
                             @if ($bundledCover && $bundledCover !== $cover) data-fallback="{{ $bundledCover }}" @endif
                             onerror="if (this.dataset.fallback) { this.src = this.dataset.fallback; delete this.dataset.fallback; } else { this.nextElementSibling.hidden = false; this.remove(); }">
                    @endif
                    <span class="tm-game-monogram" aria-hidden="true" @if ($cover) hidden @endif>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($game->name, 0, 2)) }}</span>
                </span>
                <span class="tm-game-info">
                    <strong>{{ $game->name }}</strong>
                    <small>{{ $game->listings_count }} {{ $game->listings_count === 1 ? 'anúncio disponível' : 'anúncios disponíveis' }}</small>
                </span>
            </a>
        @empty
            <p class="tm-empty">Nenhum jogo cadastrado ainda. Enquanto isso, dá uma olhada no catálogo.</p>
        @endforelse
    </div>
</section>

<section class="tm-section" aria-labelledby="listings-title">
    <div class="tm-section-heading">
        <h2 id="listings-title">Anúncios recentes</h2>
        <a href="{{ route('listings.index') }}" class="tm-section-link">Ver todos <x-icon name="chevron" /></a>
    </div>
    <nav class="tm-filter-links" aria-label="Categorias de anúncios">
        <a class="tm-filter-active" href="{{ route('listings.index') }}">Todos</a>
        <a href="{{ route('listings.index', ['tipo' => 'cosmetic']) }}">Skins e cosméticos</a>
        <a href="{{ route('listings.index', ['tipo' => 'service']) }}">Coaching</a>
    </nav>
    <div class="tm-product-grid">
        @forelse ($listings as $listing)
            <x-storefront-listing :listing="$listing" />
        @empty
            <div class="tm-empty">
                <h3>Ainda não tem nenhum anúncio por aqui</h3>
                <p>Que tal ser a primeira pessoa a vender?</p>
                <a class="tm-button" href="{{ route('seller.application') }}">Criar meu anúncio</a>
            </div>
        @endforelse
    </div>
</section>

<section class="tm-sell-strip" aria-labelledby="sell-title">
    <div>
        <h2 id="sell-title">Tem item parado no inventário?</h2>
        <p>Crie um anúncio de skin, cosmético ou coaching e converse direto com quem quer comprar.</p>
    </div>
    <a class="tm-button" href="{{ route('seller.application') }}">Começar a vender</a>
</section>

<section class="tm-section tm-how" id="como-funciona" aria-labelledby="how-title">
    <div class="tm-section-heading">
        <h2 id="how-title">Como funciona</h2>
    </div>
    <ol class="tm-steps">
        <li><h3>Encontre o que procura</h3><p>Filtre os anúncios por jogo, tipo, preço e nota do vendedor.</p></li>
        <li><h3>Pague por Pix</h3><p>Confira a descrição, adicione ao carrinho e finalize o pagamento.</p></li>
        <li><h3>Receba pelo chat</h3><p>Combine a entrega com o vendedor e acompanhe o status no pedido.</p></li>
    </ol>
</section>
@endsection
