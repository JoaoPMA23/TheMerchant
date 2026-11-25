<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Catálogo de demonstração com preços reais (acima de R$ 0,00, para o PIX ser gerado no sandbox).
 *
 * Cosméticos ficam com o vendedor (Igor) e coaching com a vendedora (Marina), para a demo
 * mostrar um pedido com dois vendedores. Rodar de novo não duplica: atualiza pelo slug.
 * Os textos evitam travessão, que a AbacatePay recusa na descrição da cobrança.
 */
class DemoCatalogSeeder extends Seeder
{
    private const COSMETICS_SELLER = 'vendedor@themerchant.local';

    private const SERVICES_SELLER = 'vendedora2@themerchant.local';

    private const COSMETICS = [
        'skins-rifles' => [
            ['AK-47 | Redline (Field-Tested)', 'Skin clássica da AK-47 com acabamento em fibra de carbono e faixas vermelhas. Float 0,21.', 89.90],
            ['M4A1-S | Printstream (Minimal Wear)', 'Acabamento perolado em preto e branco, float baixo e sem stickers.', 349.00],
            ['AWP | Asiimov (Battle-Scarred)', 'Visual futurista em laranja e branco. Desgaste visível na ponta do cano.', 219.50],
            ['AK-47 | Neon Rider (Minimal Wear)', 'Cores neon em rosa e azul, ótima para quem gosta de visual chamativo.', 154.90],
        ],
        'facas-e-luvas' => [
            ['Karambit | Fade (Factory New)', 'Degradê de rosa, roxo e amarelo com porcentagem de fade alta.', 1890.00],
            ['Sport Gloves | Vice (Minimal Wear)', 'Luvas esportivas em rosa e azul neon, padrão bem distribuído.', 1240.00],
            ['Bayonet | Tiger Tooth (Factory New)', 'Baioneta com padrão listrado dourado e brilho metálico.', 980.00],
        ],
    ];

    private const SERVICES = [
        'coaching-valorant' => [
            ['Coaching de mira e crosshair placement', 'Sessão individual com rotina de aquecimento, posicionamento de mira e treino guiado no range.', 45.00, 60, 4],
            ['Análise de VOD ranqueada', 'Revisão de uma partida sua com apontamentos de decisão, utilitário e comunicação.', 60.00, 90, 3],
        ],
        'mentoria-lol' => [
            ['Mentoria de rota superior', 'Trocas, controle de wave e uso do teleporte para subir de elo jogando no topo.', 50.00, 60, 4],
            ['Macroplay para caçadores', 'Rotas de jungle, controle de objetivos e leitura de mapa nos primeiros 15 minutos.', 55.00, 90, 3],
        ],
    ];

    public function run(): void
    {
        $cosmeticsSeller = $this->seller(self::COSMETICS_SELLER);
        $servicesSeller = $this->seller(self::SERVICES_SELLER);

        foreach (self::COSMETICS as $categorySlug => $items) {
            $category = $this->category($categorySlug);

            foreach ($items as [$title, $description, $price]) {
                $this->upsert($cosmeticsSeller, $category, $title, $description, $price, [
                    'capacity' => 1,
                    'session_duration_minutes' => null,
                    'timezone' => null,
                    'delivery_conditions' => 'Entrega pela Trade URL em até 15 minutos depois da confirmação do pagamento.',
                ]);
            }
        }

        foreach (self::SERVICES as $categorySlug => $items) {
            $category = $this->category($categorySlug);

            foreach ($items as [$title, $description, $price, $minutes, $sessions]) {
                $this->upsert($servicesSeller, $category, $title, $description, $price, [
                    'capacity' => $sessions,
                    'session_duration_minutes' => $minutes,
                    'timezone' => 'America/Sao_Paulo',
                    'delivery_conditions' => 'Horário combinado pelo chat depois da confirmação do pagamento. Sessões pelo Discord.',
                ]);
            }
        }
    }

    private function seller(string $email): User
    {
        $seller = User::where('email', $email)->first();

        if ($seller === null || ! $seller->isSeller()) {
            throw new RuntimeException("Vendedor de demonstração {$email} não encontrado. Rode \"php artisan db:seed\" antes deste seeder.");
        }

        return $seller;
    }

    private function category(string $slug): Category
    {
        return Category::where('slug', $slug)->first()
            ?? throw new RuntimeException("Categoria \"{$slug}\" não encontrada. Rode \"php artisan db:seed\" antes deste seeder.");
    }

    private function upsert(User $seller, Category $category, string $title, string $description, float $price, array $offer): void
    {
        Listing::updateOrCreate(
            ['slug' => 'demo-'.Str::slug($title)],
            [
                'seller_id' => $seller->id,
                'game_id' => $category->game_id,
                'category_id' => $category->id,
                'title' => $title,
                'description' => $description,
                'price' => $price,
                'status' => 'publicado',
                'archived_at' => null,
                ...$offer,
            ]
        );
    }
}
