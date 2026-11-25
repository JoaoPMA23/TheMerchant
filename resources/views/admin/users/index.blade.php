@extends('layouts.app')
@section('title', 'Contas e vendedores')
@section('content')
<div class="space-y-6">
    <x-page-heading title="Contas e vendedores" eyebrow="Administração" description="Gerencie contas, solicitações de vendedores e permissões de acesso." />

    <form method="GET" class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-800 bg-slate-900 p-4">
        <div class="w-full min-w-0 sm:w-auto sm:flex-none">
            <label for="filtro-busca" class="mb-1 block text-xs font-semibold text-slate-300">Buscar</label>
            <input id="filtro-busca" type="search" name="busca" value="{{ request('busca') }}" placeholder="Nome ou e-mail" class="w-full sm:w-64 min-h-11 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
        </div>
        <div class="w-full min-w-0 sm:w-auto sm:flex-none">
            <label for="filtro-acesso" class="mb-1 block text-xs font-semibold text-slate-300">Tipo de conta</label>
            <select id="filtro-acesso" name="access" class="w-full min-h-11 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-brand-500">
                @foreach (['' => 'Todas as contas', 'pending' => 'Solicitações pendentes', 'approved' => 'Vendedores aprovados', 'suspended' => 'Vendas suspensas', 'admin' => 'Administradores'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('access', '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="inline-flex min-h-11 items-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-semibold text-slate-100 transition hover:bg-slate-700">Filtrar</button>
        @if (request()->hasAny(['busca', 'access']))
            <a href="{{ route('admin.usuarios.index') }}" class="inline-flex min-h-11 items-center px-2 text-sm text-slate-400 transition hover:text-white">Limpar filtros</a>
        @endif
    </form>

    @if ($users->isEmpty())
        <x-empty-state icon="user" title="Nenhuma conta encontrada." description="Revise a busca ou o tipo de conta para ver outros resultados." />
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] text-left text-sm">
                    <caption class="sr-only">Contas cadastradas, com situação, perfil de vendedor e acesso administrativo</caption>
                    <thead class="border-b border-slate-800 bg-slate-950 text-xs text-slate-400">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Conta</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Perfil de vendedor</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Situação da conta</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Acesso administrativo</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 text-slate-300">
                        @foreach ($users as $account)
                            @php
                                // Só a linha da conta que foi enviada mostra os erros de validação.
                                $failed = (string) old('account_id') === (string) $account->id;
                                $formId = "account-{$account->id}-form";
                                $fieldId = fn (string $field) => "account-{$account->id}-{$field}";
                                $errorId = fn (string $field) => "account-{$account->id}-{$field}-error";
                                $invalid = fn (string $field) => $failed && $errors->has($field);
                            @endphp
                            <tr class="align-top">
                                <th scope="row" class="px-4 py-3 text-left font-normal">
                                    <span class="block font-semibold text-white">{{ $account->name }}</span>
                                    <span class="block break-all text-xs text-slate-400">{{ $account->email }}</span>
                                    @if ($account->sellerProfile?->bio)
                                        <details class="mt-1 max-w-xs text-xs">
                                            <summary class="inline-flex min-h-11 cursor-pointer items-center text-brand-400 hover:text-brand-300">Ver apresentação</summary>
                                            <p class="mt-1 whitespace-pre-line text-slate-300">{{ $account->sellerProfile->bio }}</p>
                                        </details>
                                    @endif
                                </th>
                                <td class="px-4 py-3">
                                    @if ($account->sellerProfile)
                                        <label for="{{ $fieldId('seller_status') }}" class="sr-only">Perfil de vendedor de {{ $account->name }}</label>
                                        <select id="{{ $fieldId('seller_status') }}" form="{{ $formId }}" name="seller_status" class="min-h-11 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-brand-500" @if($invalid('seller_status')) aria-invalid="true" aria-describedby="{{ $errorId('seller_status') }}" @endif>
                                            @foreach (['pending' => 'Solicitação pendente', 'approved' => 'Vendas aprovadas', 'suspended' => 'Vendas suspensas'] as $value => $label)
                                                <option value="{{ $value }}" @selected($account->sellerProfile->status === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @if ($failed)<x-field-error name="seller_status" :id="$errorId('seller_status')" />@endif
                                    @else
                                        <span class="inline-flex min-h-11 items-center text-xs text-slate-400">Sem solicitação</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <label for="{{ $fieldId('status') }}" class="sr-only">Situação da conta de {{ $account->name }}</label>
                                    <select id="{{ $fieldId('status') }}" form="{{ $formId }}" name="status" class="min-h-11 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-brand-500" @if($invalid('status')) aria-invalid="true" aria-describedby="{{ $errorId('status') }}" @endif>
                                        <option value="active" @selected($account->status === 'active')>Ativa</option>
                                        <option value="suspended" @selected($account->status === 'suspended')>Suspensa</option>
                                    </select>
                                    @if ($failed)<x-field-error name="status" :id="$errorId('status')" />@endif
                                </td>
                                <td class="px-4 py-3">
                                    {{-- O hidden vem antes do checkbox para que o valor marcado prevaleça no envio. --}}
                                    <input type="hidden" form="{{ $formId }}" name="is_admin" value="0">
                                    <label for="{{ $fieldId('is_admin') }}" class="inline-flex min-h-11 cursor-pointer items-center gap-2 text-sm">
                                        <input type="checkbox" id="{{ $fieldId('is_admin') }}" form="{{ $formId }}" name="is_admin" value="1" @checked($account->is_admin) class="h-5 w-5 rounded border-slate-700 bg-slate-950 text-brand-500 focus:ring-brand-500" @if($invalid('is_admin')) aria-invalid="true" aria-describedby="{{ $errorId('is_admin') }}" @endif>
                                        <span>Administrador<span class="sr-only">: {{ $account->name }}</span></span>
                                    </label>
                                    @if ($failed)<x-field-error name="is_admin" :id="$errorId('is_admin')" />@endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @can('update', $account)
                                        <form id="{{ $formId }}" action="{{ route('admin.usuarios.update', $account) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="account_id" value="{{ $account->id }}">
                                            <button type="submit" class="inline-flex min-h-11 items-center rounded-xl border border-slate-700 bg-slate-800 px-4 text-sm font-semibold text-slate-100 transition hover:bg-slate-700">
                                                Salvar<span class="sr-only"> permissões de {{ $account->name }}</span>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{ $users->links() }}
</div>
@endsection
