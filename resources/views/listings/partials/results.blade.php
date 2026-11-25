<p class="text-sm text-slate-300">{{ $listings->total() }} anúncio(s) encontrado(s)</p>
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
    @forelse($listings as $listing)
        <x-listing-card :listing="$listing" />
    @empty
        <x-empty-state class="col-span-full" title="Nada encontrado com esses filtros" description="Tente mudar a busca ou limpar os filtros para ver todos os anúncios." icon="search" :href="route('listings.index')" action="Explorar todos os anúncios" />
    @endforelse
</div>
{{ $listings->links('vendor.pagination.catalog') }}
