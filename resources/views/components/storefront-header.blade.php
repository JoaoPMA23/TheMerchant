<header class="tm-header">
    <div class="tm-container tm-topbar">
        <a href="{{ route('home') }}" class="tm-logo" aria-label="TheMerchant, página inicial"><span class="tm-logomark"><x-icon name="store" /></span><span>the<span class="tm-accent">merchant</span>.</span></a>
        {{-- No catálogo a busca fica só no filtro da página, para não ter dois campos iguais na tela. --}}
        @unless(request()->routeIs('listings.index'))
        <form action="{{ route('listings.index') }}" method="GET" class="tm-search" role="search">
            <label for="home-search" class="sr-only">Buscar itens ou serviços</label><x-icon name="search" />
            <input id="home-search" name="busca" type="search" placeholder="Buscar skin, jogo ou coaching" maxlength="150" value="{{ is_string(request('busca')) ? request('busca') : '' }}">
            <button type="submit" aria-label="Buscar"><x-icon /></button>
        </form>
        @endunless
        <div class="tm-account">
            @auth
                {{-- $unreadChatCount vem do App\View\Composers\UnreadChatCountComposer. --}}
                <a class="tm-icon-link" href="{{ route('chat.index') }}" aria-label="Mensagens{{ $unreadChatCount > 0 ? ', '.$unreadChatCount.' não lidas' : '' }}">
                    <x-icon name="message" />
                    @if ($unreadChatCount > 0)
                        <span class="tm-count" aria-hidden="true">{{ $unreadChatCount > 99 ? '99+' : $unreadChatCount }}</span>
                    @endif
                </a>
                <a class="tm-icon-link" href="{{ route('cart.index') }}" aria-label="Meu carrinho"><x-icon name="cart" /></a>

                @php($user = auth()->user())
                <div class="tm-account-menu" x-data="{ open: false }" @keydown.escape.window="if (open) { open = false; $refs.trigger.focus() }" @click.outside="open = false">
                    <button type="button" x-ref="trigger" class="tm-account-trigger" @click="open = !open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="account-menu">
                        <span class="tm-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        <span class="tm-account-name">{{ \Illuminate\Support\Str::of($user->name)->before(' ') }}</span>
                        <span class="sr-only">Abrir menu da conta</span>
                        <x-icon name="chevron" class="tm-account-chevron" />
                    </button>
                    <div id="account-menu" class="tm-account-panel" x-cloak x-show="open" x-transition.opacity.duration.150ms>
                        <div class="tm-account-head">
                            <strong>{{ $user->name }}</strong>
                            <span>{{ $user->email }}</span>
                        </div>
                        <nav aria-label="Minha conta">
                            <a href="{{ route('orders.index') }}" @if(request()->routeIs('orders.*')) aria-current="page" @endif>Meus pedidos</a>
                            <a href="{{ route('chat.index') }}" @if(request()->routeIs('chat.*')) aria-current="page" @endif>
                                Mensagens
                                @if ($unreadChatCount > 0)
                                    <span class="tm-mini-label" aria-label="{{ $unreadChatCount }} mensagens não lidas">{{ $unreadChatCount > 99 ? '99+' : $unreadChatCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('profile.edit') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>Meu perfil</a>
                            @if($user->isSeller())
                                <p class="tm-account-group">Vendas</p>
                                <a href="{{ route('seller.anuncios.index') }}" @if(request()->routeIs('seller.anuncios.*')) aria-current="page" @endif>Meus anúncios</a>
                                <a href="{{ route('seller.sales.index') }}" @if(request()->routeIs('seller.sales.*')) aria-current="page" @endif>Minhas vendas</a>
                            @endif
                            @can('viewAny', \App\Models\User::class)
                                <p class="tm-account-group">Administração</p>
                                <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>Painel</a>
                                <a href="{{ route('admin.usuarios.index') }}" @if(request()->routeIs('admin.usuarios.*')) aria-current="page" @endif>Contas</a>
                                <a href="{{ route('admin.categorias.index') }}" @if(request()->routeIs('admin.categorias.*')) aria-current="page" @endif>Categorias e jogos</a>
                                <a href="{{ route('admin.reports.index') }}" @if(request()->routeIs('admin.reports.*')) aria-current="page" @endif>Denúncias</a>
                            @endcan
                        </nav>
                        <form method="POST" action="{{ route('logout') }}" class="tm-account-foot">@csrf<button class="tm-logout" type="submit">Sair</button></form>
                    </div>
                </div>
            @else
                <a class="tm-icon-link tm-guest-cart" href="{{ route('cart.index') }}" aria-label="Meu carrinho"><x-icon name="cart" /></a>
                <a href="{{ route('login') }}" class="tm-login">Entrar</a><a href="{{ route('register') }}" class="tm-button tm-button-small">Criar conta</a>
            @endauth
        </div>
    </div>
    <nav class="tm-subnav" aria-label="Navegação do marketplace"><div class="tm-container tm-nav-inner">
        <div class="tm-nav-links">
            <a href="{{ route('listings.index') }}" @if(request()->routeIs('listings.index') && !request('tipo')) aria-current="page" @endif><x-icon name="grid" />Explorar catálogo</a>
            <a href="{{ route('listings.index', ['tipo' => 'cosmetic']) }}" @if(request()->routeIs('listings.index') && request('tipo') === 'cosmetic') aria-current="page" @endif>Skins e cosméticos</a>
            <a href="{{ route('listings.index', ['tipo' => 'service']) }}" @if(request()->routeIs('listings.index') && request('tipo') === 'service') aria-current="page" @endif>Coaching</a>
            <a href="{{ route('home') }}#como-funciona">Como funciona</a>
        </div>
        @if(auth()->user()?->isSeller())
            <a class="tm-sell-link" href="{{ route('seller.anuncios.create') }}"><x-icon name="plus" />Anunciar item</a>
        @else
            <a class="tm-sell-link" href="{{ route('seller.application') }}"><x-icon name="store" />Quero vender</a>
        @endif
    </div></nav>
</header>
