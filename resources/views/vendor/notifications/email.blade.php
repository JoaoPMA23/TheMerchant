<x-mail::message>
{{-- Saudação --}}
@if (! empty($greeting))
# {{ $greeting }}
@else
@if ($level === 'error')
# Ops!
@else
# Olá!
@endif
@endif

{{-- Linhas antes do botão --}}
@foreach ($introLines as $line)
{{ $line }}

@endforeach

{{-- Botão de ação --}}
@isset($actionText)
<?php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
?>
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

{{-- Linhas depois do botão --}}
@foreach ($outroLines as $line)
{{ $line }}

@endforeach

{{-- Despedida --}}
@if (! empty($salutation))
{{ $salutation }}
@else
Até logo,<br>
Equipe {{ config('app.name') }}
@endif

{{-- Link alternativo ao botão --}}
@isset($actionText)
<x-slot:subcopy>
Se o botão "{{ $actionText }}" não funcionar, copie e cole este endereço no navegador:
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
