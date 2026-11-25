<?php

namespace Tests\Unit;

use App\Models\Listing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Report;
use App\Support\StatusLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StatusLabelTest extends TestCase
{
    public static function knownStatuses(): array
    {
        return [
            ['pendente', 'Pendente'],
            ['em_andamento', 'Em andamento'],
            ['concluido', 'Concluído'],
            ['aguardando_pagamento', 'Aguardando pagamento'],
            ['em_entrega', 'Em entrega'],
            ['publicado', 'Publicado'],
            ['em_analise', 'Em análise'],
        ];
    }

    #[DataProvider('knownStatuses')]
    public function test_known_status_gets_portuguese_label(string $status, string $label): void
    {
        $this->assertSame($label, StatusLabel::for($status));
    }

    public function test_unknown_status_falls_back_to_readable_text(): void
    {
        $this->assertSame('Payment created', StatusLabel::for('payment.created'));
        $this->assertSame('Novo status', StatusLabel::for('novo_status'));
        $this->assertSame('', StatusLabel::for(null));
    }

    public function test_models_expose_status_labels(): void
    {
        $this->assertSame('Concluído', (new Order(['status' => 'concluido']))->status_label);
        $this->assertSame('Em entrega', (new OrderItem(['delivery_status' => 'em_entrega']))->delivery_status_label);
        $this->assertSame('Vendido', (new Listing(['status' => 'vendido']))->status_label);
        $this->assertSame('Em análise', (new Report(['status' => 'em_analise']))->status_label);
    }
}
