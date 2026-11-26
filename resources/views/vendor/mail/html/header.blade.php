@props(['url'])
{{-- Mesma marca do cabeçalho do site: ícone da loja em verde e "themerchant." com o "merchant" em destaque. --}}
<tr>
<td class="header">
<a href="{{ $url }}" target="_blank" rel="noopener">
<img src="{{ rtrim(config('app.url'), '/') }}/apple-touch-icon.png" class="logo-mark" width="32" height="32" alt="">
<span class="logo-word">the<span class="logo-accent">merchant</span>.</span>
</a>
</td>
</tr>
