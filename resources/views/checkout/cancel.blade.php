@extends('layouts.app')

@section('title', 'Pagamento não concluído')

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="tm-panel sm:!p-8">
        <div class="flex items-start gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-lg border border-rose-800 bg-rose-950 text-rose-300">
                <x-icon name="alert" class="!h-6 !w-6" />
            </span>
            <div class="min-w-0">
                <x-page-heading title="Pagamento não concluído" eyebrow="" />
                <p class="mt-1 text-sm text-slate-300">O status abaixo vem do pedido e das confirmações do processador de pagamento. Esta página não cancela nem altera o pedido.</p>
            </div>
        </div>

        <dl class="mt-6 divide-y divide-slate-800 rounded-md border border-slate-800 bg-slate-950 text-sm">
            <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">Número do pedido</dt><dd class="font-mono font-bold text-brand-300">{{ $order->order_number }}</dd></div>
            <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">Status do pedido</dt><dd class="font-semibold text-slate-200">{{ $order->status_label }}</dd></div>
            <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">Status do pagamento</dt><dd class="text-right font-semibold text-amber-300">{{ match ($order->payment?->status) {
                null => 'Aguardando o processador de pagamento',
                'pending' => 'Aguardando pagamento do PIX',
                'paid' => 'Pagamento confirmado',
                'expired' => 'PIX expirado',
                'cancelled' => 'Pagamento cancelado',
                'failed' => 'Pagamento não concluído',
                'refunded' => 'Pagamento estornado',
                'disputed' => 'Pagamento em contestação',
                default => \App\Support\StatusLabel::for($order->payment->status),
            } }}</dd></div>
            <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">Total</dt><dd class="font-bold text-white">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</dd></div>
        </dl>

        <p class="mt-5 text-sm text-slate-400">Se o pagamento foi aprovado no seu banco, aguarde a atualização automática ou confira o acompanhamento do pedido antes de tentar de novo.</p>
        <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-800 pt-6">
            <a href="{{ route('orders.show', $order) }}" class="tm-button">Ver acompanhamento</a>
            <a href="{{ route('orders.index') }}" class="tm-button-outline">Meus pedidos</a>
        </div>
    </div>
</div>
@endsection
