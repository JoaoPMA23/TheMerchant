@props(['title', 'eyebrow' => 'TheMerchant', 'description' => null])

<div {{ $attributes->class(['tm-page-heading']) }}>
    <span class="tm-eyebrow">{{ $eyebrow }}</span>
    <h1>{{ $title }}</h1>
    @if ($description)
        <p>{{ $description }}</p>
    @endif
</div>
