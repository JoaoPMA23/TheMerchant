@props(['listing', 'showRating' => false])
<a class="tm-product" href="{{ route('listings.show', $listing->slug) }}">
    <div class="tm-product-art {{ $listing->category?->type === 'service' ? 'tm-product-service' : '' }}">
        @if ($listing->primaryImage?->url)
            <img src="{{ $listing->primaryImage->url }}" alt="{{ $listing->title }}" width="400" height="240" loading="lazy">
        @else
            <x-icon :name="$listing->category?->type === 'service' ? 'game' : 'gem'" class="tm-product-symbol" /><span class="tm-art-caption">{{ $listing->category?->type === 'service' ? 'Coaching' : 'Cosmético' }}</span>
        @endif
        <span class="tm-product-tag">{{ $listing->game?->name }}</span>
    </div>
    <div class="tm-product-body"><span class="tm-product-category">{{ $listing->category?->name }}</span><h3>{{ $listing->title }}</h3>@if ($showRating)@php($profile = $listing->seller?->sellerProfile)<p class="-mt-3 mb-4 text-xs text-slate-400">@if (($profile?->total_reviews ?? 0) > 0)Vendedor: {{ number_format($profile->reputation_score, 2, ',', '.') }} / 5 ({{ $profile->total_reviews }} {{ $profile->total_reviews === 1 ? 'avaliação' : 'avaliações' }})@else Vendedor sem avaliações @endif</p>@endif<div class="tm-product-bottom"><div><span class="tm-price-label">Preço do anúncio</span><strong>R$ {{ number_format($listing->price, 2, ',', '.') }}</strong></div><span class="tm-product-arrow"><x-icon /></span></div></div>
</a>
