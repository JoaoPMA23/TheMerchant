<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Rótulos em português para os status gravados no banco.
 *
 * As telas exibem estes rótulos em vez do valor cru (ex.: "em_entrega").
 */
final class StatusLabel
{
    private const LABELS = [
        // Pedido
        'pendente' => 'Pendente',
        'pago' => 'Pago',
        'em_andamento' => 'Em andamento',
        'concluido' => 'Concluído',
        'cancelado' => 'Cancelado',

        // Entrega do item
        'aguardando_pagamento' => 'Aguardando pagamento',
        'em_entrega' => 'Em entrega',
        'entregue' => 'Entregue',

        // Anúncio
        'rascunho' => 'Rascunho',
        'publicado' => 'Publicado',
        'pausado' => 'Pausado',
        'vendido' => 'Vendido',
        'bloqueado' => 'Bloqueado',

        // Denúncia
        'nova' => 'Nova',
        'aberta' => 'Aberta',
        'em_analise' => 'Em análise',
        'procedente' => 'Procedente',
        'improcedente' => 'Improcedente',
    ];

    public static function for(?string $status): string
    {
        if ($status === null || $status === '') {
            return '';
        }

        return self::LABELS[$status]
            ?? Str::ucfirst(str_replace(['_', '.'], ' ', $status));
    }
}
