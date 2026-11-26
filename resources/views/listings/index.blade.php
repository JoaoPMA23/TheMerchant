@extends('layouts.app')

@section('title', 'Catálogo de Anúncios')

@push('styles')
<script src="{{ asset('js/catalog-filters.js') }}"></script>
@endpush

@php
    $activeFilters = collect($filters ?? [])->except('ordem')->filter(fn ($value) => $value !== null && $value !== '')->count();
@endphp

@section('content')
<div x-data="catalogFilters" @popstate.window="restore()" class="tm-catalog">
        <div class="tm-catalog-head">
            <x-page-heading title="Catálogo" eyebrow="Explorar" description="Skins, cosméticos e coaching anunciados por outros jogadores." />
        </div>

        {{-- Filtros: barra lateral no desktop, painel que abre no celular. O form funciona sem JavaScript. --}}
        <form x-ref="form" id="catalog-filters" method="GET" action="{{ route('listings.index') }}"
              @submit.prevent="search()" @input="changed($event)" @change="changed($event)"
              class="tm-filter-panel" :data-collapsed="!filtersOpen"
              @keydown.escape="filtersOpen = false; $refs.filtersToggle.focus()">
            <div class="tm-filter-field">
                <label for="catalog-search">Buscar</label>
                <input id="catalog-search" type="search" name="busca" value="{{ $filters['busca'] ?? '' }}" maxlength="150"
                       placeholder="Karambit, Vandal, coaching..." class="tm-input">
            </div>
            <div class="tm-filter-field">
                <label for="catalog-type">Tipo de anúncio</label>
                <select id="catalog-type" name="tipo" class="tm-input">
                    <option value="">Todos os anúncios</option>
                    <option value="cosmetic" @selected(($filters['tipo'] ?? '') === 'cosmetic')>Skins e cosméticos</option>
                    <option value="service" @selected(($filters['tipo'] ?? '') === 'service')>Coaching e serviços</option>
                </select>
            </div>
            <div class="tm-filter-field">
                <label for="catalog-game">Jogo</label>
                <select id="catalog-game" name="jogo" class="tm-input">
                    <option value="">Todos os jogos</option>
                    @foreach($games as $game)
                        <option value="{{ $game->id }}" @selected(($filters['jogo'] ?? '') == $game->id)>{{ $game->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="tm-filter-field">
                <label for="catalog-category">Categoria</label>
                <select id="catalog-category" name="categoria" class="tm-input">
                    <option value="">Todas as categorias</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['categoria'] ?? '') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <fieldset class="tm-filter-field">
                <legend>Preço (R$)</legend>
                <div class="tm-price-range">
                    @foreach(['min' => 'mínimo', 'max' => 'máximo'] as $bound => $label)
                        <div>
                            <label for="price-{{ $bound }}" class="sr-only">Preço {{ $label }} (R$)</label>
                            <input id="price-{{ $bound }}" name="preco_{{ $bound }}" type="number" min="0" max="99999999.99" step="0.01"
                                   value="{{ $filters['preco_'.$bound] ?? '' }}" placeholder="{{ $bound === 'min' ? 'Mínimo' : 'Máximo' }}" class="tm-input">
                            <input type="range" min="0" :max="rangeMax" step="0.01" value="{{ $filters['preco_'.$bound] ?? ($bound === 'min' ? 0 : 1000) }}"
                                   data-price="{{ $bound }}" aria-label="Ajustar preço {{ $label }}" class="tm-range">
                        </div>
                    @endforeach
                </div>
            </fieldset>
            <div class="tm-filter-field">
                <label for="catalog-rating">Nota mínima do vendedor</label>
                <select id="catalog-rating" name="nota_min" class="tm-input">
                    <option value="">Todas, incluindo sem avaliações</option>
                    @foreach([1, 2, 3, 4, 5] as $rating)
                        <option value="{{ $rating }}" @selected(($filters['nota_min'] ?? '') == $rating)>{{ $rating }} estrela(s) ou mais</option>
                    @endforeach
                </select>
            </div>
            <div class="tm-filter-actions">
                <button type="submit" class="tm-button">Filtrar</button>
                <a href="{{ route('listings.index') }}" @click.prevent="clear()" class="tm-button-outline">Limpar</a>
            </div>
        </form>

        <div class="tm-catalog-results">
            <div class="tm-catalog-toolbar">
                <button type="button" x-ref="filtersToggle" class="tm-button-outline tm-filters-toggle"
                        @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen.toString()" aria-controls="catalog-filters">
                    <x-icon name="grid" /> Filtros
                    <span class="tm-filter-count" x-show="tags.filter(tag => tag.name !== 'ordem').length" x-text="tags.filter(tag => tag.name !== 'ordem').length">{{ $activeFilters ?: '' }}</span>
                </button>
                <div class="tm-sort">
                    <label for="catalog-sort">Ordenar por</label>
                    {{-- Fica fora do <form>, mas pertence a ele pelo atributo form; o change é tratado aqui. --}}
                    <select id="catalog-sort" name="ordem" form="catalog-filters" @change="changed($event)" class="tm-input">
                        @foreach(['recentes' => 'Mais recentes', 'menor_preco' => 'Menor preço', 'maior_preco' => 'Maior preço', 'reputacao' => 'Reputação do vendedor'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['ordem'] ?? 'recentes') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="tm-active-filters" aria-label="Filtros ativos" x-show="tags.length" x-cloak>
                <template x-for="tag in tags" :key="tag.name">
                    <button type="button" @click="remove(tag.name)" :aria-label="'Remover filtro ' + tag.label">
                        <span x-text="tag.label"></span><span aria-hidden="true">×</span>
                    </button>
                </template>
            </div>

            <p x-cloak x-show="loading" role="status" class="tm-catalog-status">Carregando resultados…</p>
            <div x-cloak x-show="error" role="alert" class="mb-3 flex flex-wrap items-center gap-2 rounded-md border border-rose-700 px-4 text-sm text-rose-300">
                <span x-text="error"></span>
                <button type="button" @click="search()" class="underline">Tentar novamente</button>
            </div>
            <section x-ref="results" id="catalog-results" aria-live="polite" :aria-busy="loading"
                     @click="paginate($event)" class="tm-results">
                @include('listings.partials.results')
            </section>
        </div>
</div>
@endsection
