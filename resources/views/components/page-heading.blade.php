@props(['title', 'eyebrow' => 'TheMerchant', 'description' => null])

<div {{ $attributes->class(['tm-page-heading']) }}>
    @if ($eyebrow)
        <span class="tm-eyebrow">{{ $eyebrow }}</span>
    @endif
    <h1>{{ $title }}</h1>
    @if ($description)
        <p>{{ $description }}</p>
    @endif
</div>
