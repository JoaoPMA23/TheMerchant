<x-mail::layout>
    {{-- Cabeçalho --}}
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            {{ config('app.name') }}
        </x-mail::header>
    </x-slot:header>

    {{-- Corpo --}}
    {{ $slot }}

    {{-- Texto auxiliar --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Rodapé --}}
    <x-slot:footer>
        <x-mail::footer>
            Você recebeu este e-mail porque existe uma conta no {{ config('app.name') }} com este endereço.
            © {{ date('Y') }} {{ config('app.name') }}
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
