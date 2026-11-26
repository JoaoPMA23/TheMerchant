@props(['listing'])
@php
    $isService = $listing->category?->type === 'service';
    $seller = $listing->relationLoaded('seller') ? $listing->seller : null;
    $profile = $seller?->sellerProfile;
    $reviews = (int) ($profile?->total_reviews ?? 0);
    $sales = (int) ($profile?->total_sales ?? 0);
@endphp
<a class="tm-product" href="{{ route('listings.show', $listing->slug) }}">
    <div class="tm-product-art">
        @if ($listing->primaryImage?->url)
            <img src="{{ $listing->primaryImage->url }}" alt="{{ $listing->title }}" width="400" height="300" loading="lazy">
        @else
            <x-icon :name="$isService ? 'game' : 'gem'" class="tm-product-symbol" />
            <span class="sr-only">{{ $isService ? 'Coaching' : 'Cosmético' }} sem foto</span>
        @endif
        @if ($listing->game)
            <span class="tm-product-tag">{{ $listing->game->name }}</span>
        @endif
    </div>
    <div class="tm-product-body">
        <h3>{{ $listing->title }}</h3>
        <span class="tm-product-category">{{ $listing->category?->name }}</span>
        <strong class="tm-product-price">R$ {{ number_format($listing->price, 2, ',', '.') }}</strong>
        @if ($seller)
            <span class="tm-product-seller">
                <span class="tm-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($seller->name, 0, 1)) }}</span>
                <span class="tm-product-seller-text">
                    <span class="tm-product-seller-name">{{ $seller->name }}</span>
                    @if ($reviews > 0)
                        <span class="tm-product-rating"><x-icon name="star" /> {{ number_format($profile->reputation_score, 2, ',', '.') }} ({{ $reviews }} {{ $reviews === 1 ? 'avaliação' : 'avaliações' }})@if ($sales > 0)<span class="tm-product-sales"> · {{ $sales }} {{ $sales === 1 ? 'venda' : 'vendas' }}</span>@endif</span>
                    @else
                        <span class="tm-product-rating">Vendedor sem avaliações</span>
                    @endif
                </span>
            </span>
        @endif
    </div>
</a>
