@extends('layouts.app')

@php
    $payment = $order->payment;
    $paymentStatus = $payment?->status;
    $isPaid = $order->isPaid();
    $isCancelled = $order->status === 'cancelado';
    $isAwaitingPix = ! $isPaid && ! $isCancelled && $paymentStatus === 'pending' && $order->reservation_expires_at?->isFuture();
    $paymentLabels = [
        'pending' => 'Aguardando pagamento do PIX',
        'paid' => 'Pagamento confirmado',
        'expired' => 'PIX expirado',
        'cancelled' => 'Pagamento cancelado',
        'failed' => 'Pagamento não concluído',
        'refunded' => 'Pagamento estornado',
        'disputed' => 'Pagamento em contestação',
    ];
    $statusLabel = $isPaid
        ? 'Pagamento confirmado'
        : ($payment?->refund_status !== null
            ? 'Pago após o fim da reserva; estorno '.($payment->refund_status === 'refunded' ? 'concluído' : 'em processamento')
            : ($isCancelled
                ? ($paymentLabels[$paymentStatus] ?? 'Pedido cancelado')
                : ($payment === null ? 'Cobrança PIX ainda não gerada' : ($paymentLabels[$paymentStatus] ?? 'Aguardando confirmação do pagamento'))));
@endphp

@section('title', 'Status do Pedido')

@section('content')
<div class="mx-auto max-w-2xl">
    @unless($isPaid || $isCancelled)
        <x-checkout-steps :current="3" />
    @endunless

    <div class="tm-panel sm:!p-8">
        <div class="flex items-start gap-4">
            <span @class([
                'grid h-12 w-12 shrink-0 place-items-center rounded-lg border',
                'border-emerald-700 bg-emerald-950 text-emerald-400' => $isPaid,
                'border-rose-800 bg-rose-950 text-rose-300' => $isCancelled && ! $isPaid,
                'border-amber-700 bg-amber-950 text-amber-300' => ! $isPaid && ! $isCancelled,
            ])>
                <x-icon :name="$isPaid ? 'check' : ($isCancelled ? 'alert' : 'clock')" class="!h-6 !w-6" />
            </span>
            <div class="min-w-0">
                <x-page-heading :title="$isPaid ? 'Pagamento confirmado' : ($isCancelled ? 'Pedido cancelado' : 'Pedido criado')" eyebrow="" />
                <p class="mt-1 text-sm text-slate-300">
                    {{ $isPaid ? 'O pagamento foi confirmado. Os vendedores já podem iniciar as entregas.' : ($isCancelled ? 'Este pedido foi cancelado e a reserva dos itens foi liberada.' : 'Seus itens estão reservados. A confirmação chega automaticamente quando o PIX for pago; abrir esta página não confirma o pagamento.') }}
                </p>
            </div>
        </div>

        <dl class="mt-6 divide-y divide-slate-800 rounded-md border border-slate-800 bg-slate-950 text-sm">
            <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">Número do pedido</dt><dd class="font-mono font-bold text-brand-300">{{ $order->order_number }}</dd></div>
            <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">Pedido</dt><dd class="font-semibold text-slate-200">{{ $order->status_label }}</dd></div>
            <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">Pagamento</dt><dd class="text-right font-semibold {{ $isPaid ? 'text-emerald-400' : 'text-amber-300' }}">{{ $statusLabel }}</dd></div>
            <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">Total</dt><dd class="font-bold text-white">R$ {{ number_format($order->total_amount, 2, ',', '.') }}</dd></div>
            @if($order->reservation_expires_at && ! $isPaid && ! $isCancelled)
                <div class="flex justify-between gap-4 px-4 py-3"><dt class="text-slate-400">{{ $order->reservation_expires_at->isPast() ? 'Reserva expirou em' : 'Reserva válida até' }}</dt><dd class="text-right font-semibold text-slate-200">{{ $order->reservation_expires_at->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</dd></div>
            @endif
        </dl>

        @if($isAwaitingPix && auth()->user()->can('pay', $order))
            <section class="mt-6 rounded-md border border-brand-700/60 p-5" aria-labelledby="pix-title" x-data="{ copied: false }">
                <h2 id="pix-title" class="text-base font-bold text-white">Pague com PIX</h2>
                <p class="mt-1 text-sm text-slate-400">Escaneie o QR Code ou use o código copia e cola no app do seu banco. O PIX vence às {{ ($payment->expires_at ?? $order->reservation_expires_at)->format('H:i') }}.</p>
                @if($payment->br_code_base64 && str_starts_with($payment->br_code_base64, 'data:image/png;base64,'))
                    <img src="{{ $payment->br_code_base64 }}" alt="QR Code PIX do pedido {{ $order->order_number }}" width="208" height="208" fetchpriority="high" class="mx-auto mt-5 h-52 w-52 rounded-md bg-white p-2">
                @endif
                @if($payment->br_code)
                    <label for="pix-code" class="mb-1 mt-5 block text-sm font-semibold text-slate-300">PIX copia e cola</label>
                    <div class="flex gap-2">
                        <input id="pix-code" x-ref="code" type="text" readonly value="{{ $payment->br_code }}" class="tm-input min-w-0 flex-1 font-mono !text-xs">
                        <button type="button" @click="navigator.clipboard.writeText($refs.code.value).then(() => copied = true)" class="tm-button shrink-0" x-text="copied ? 'Copiado' : 'Copiar'">Copiar</button>
                    </div>
                @endif
                <div class="mt-5 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('checkout.payment.refresh', $order) }}">
                        @csrf
                        <button type="submit" class="tm-button-outline"><x-icon name="refresh" /> Já paguei, atualizar status</button>
                    </form>
                    @if(config('services.abacatepay.dev_mode') && $payment->dev_mode)
                        <form method="POST" action="{{ route('checkout.payment.simulate', $order) }}">
                            @csrf
                            <button type="submit" class="rounded-md border border-amber-700 px-4 text-sm font-semibold text-amber-200 hover:bg-amber-950">Simular pagamento (modo dev)</button>
                        </form>
                    @endif
                </div>
            </section>
        @elseif($payment === null && ! $isPaid && ! $isCancelled && $order->reservation_expires_at?->isFuture() && auth()->user()->can('pay', $order))
            <div class="mt-6 rounded-md border border-amber-700/50 p-5 text-sm">
                <p class="text-amber-200">O processador de pagamento não respondeu ao gerar o PIX. Sua reserva continua válida.</p>
                <form method="POST" action="{{ route('checkout.payment.retry', $order) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="tm-button">Gerar PIX novamente</button>
                </form>
            </div>
        @endif

        @if($isCancelled && ! $isPaid)
            <p class="mt-5 text-sm text-rose-300">O pagamento não foi concluído dentro do prazo da reserva. Monte o carrinho novamente para tentar outra vez.</p>
        @endif

        <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-800 pt-6">
            <a href="{{ route('orders.show', $order) }}" class="tm-button">Acompanhar pedido</a>
            @if(! $isPaid && ! $isCancelled)
                <a href="{{ route('checkout.cancel', $order) }}" class="tm-button-outline">Precisa de ajuda?</a>
            @endif
            <a href="{{ route('listings.index') }}" class="tm-button-outline">Continuar navegando</a>
        </div>
    </div>
</div>
@endsection
