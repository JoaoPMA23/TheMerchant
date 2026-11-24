<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Game;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * RF16, RF19 e RF20: denúncia, julgamento motivado, bloqueio e restauração de anúncios.
 */
class ReportModerationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $seller;

    private User $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->seller = User::factory()->seller()->create();
        $this->reporter = User::factory()->create();
    }

    private function listing(string $status = 'publicado'): Listing
    {
        $slug = (string) Str::uuid();
        $game = Game::create(['name' => 'Jogo '.$slug, 'slug' => $slug, 'active' => true]);
        $category = Category::create([
            'game_id' => $game->id,
            'name' => 'Skins',
            'slug' => $slug,
            'type' => 'cosmetic',
            'is_active' => true,
        ]);

        return Listing::create([
            'seller_id' => $this->seller->id,
            'game_id' => $game->id,
            'category_id' => $category->id,
            'title' => 'Skin denunciada '.Str::random(6),
            'slug' => $slug,
            'description' => 'Descrição completa do anúncio denunciado.',
            'price' => 50,
            'status' => $status,
        ]);
    }

    private function report(Listing $listing, string $status = 'aberta'): Report
    {
        return Report::create([
            'reporter_id' => $this->reporter->id,
            'listing_id' => $listing->id,
            'reason' => 'fraude',
            'details' => 'O vendedor pediu pagamento fora da plataforma.',
            'status' => $status,
        ]);
    }

    private function judge(Report $report, string $status, bool $block = false, string $notes = 'Conversa comprova pedido de pagamento externo.')
    {
        return $this->actingAs($this->admin)->patch(route('admin.reports.moderate', $report), array_filter([
            'status' => $status,
            'resolution_notes' => $notes,
            'block_listing' => $block ? '1' : null,
        ]));
    }

    private function blockedByModeration(string $previousStatus = 'publicado'): array
    {
        $listing = $this->listing($previousStatus);
        $report = $this->report($listing);
        $this->judge($report, 'procedente', true)->assertRedirect();
        $this->assertSame('bloqueado', $listing->fresh()->status);

        return [$listing, $report];
    }

    private function restore(Report $report, ?User $as = null, string $notes = 'Vendedor apresentou comprovantes e o item foi verificado.')
    {
        return $this->actingAs($as ?? $this->admin)->patch(route('admin.reports.restore', $report), [
            'resolution_notes' => $notes,
        ]);
    }

    public function test_admin_sees_the_queue_with_open_reports_first_and_can_filter(): void
    {
        $closed = $this->report($this->listing(), 'improcedente');
        $open = $this->report($this->listing());

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSeeInOrder([$open->listing->title, $closed->listing->title]);

        $this->get(route('admin.reports.index', ['status' => 'improcedente']))
            ->assertOk()
            ->assertSee($closed->listing->title)
            ->assertDontSee($open->listing->title);
    }

    public function test_only_admins_can_see_or_moderate_reports(): void
    {
        $report = $this->report($this->listing());

        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));

        foreach ([$this->reporter, $this->seller] as $user) {
            $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
            $this->patch(route('admin.reports.moderate', $report), [
                'status' => 'procedente',
                'resolution_notes' => 'Tentativa de julgar sem ser administrador.',
                'block_listing' => '1',
            ])->assertForbidden();
        }

        $this->assertSame('aberta', $report->fresh()->status);
        $this->assertSame('publicado', $report->listing->fresh()->status);
        $this->assertDatabaseCount('moderation_audits', 0);
    }

    public function test_upheld_report_with_block_removes_listing_from_catalog_and_is_audited(): void
    {
        $listing = $this->listing();
        $report = $this->report($listing);

        $this->get(route('listings.index'))->assertSee($listing->title);

        $this->judge($report, 'procedente', true)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'procedente',
            'moderator_id' => $this->admin->id,
            'resolution_notes' => 'Conversa comprova pedido de pagamento externo.',
        ]);
        $this->assertSame('bloqueado', $listing->fresh()->status);
        $this->assertDatabaseHas('moderation_audits', [
            'report_id' => $report->id,
            'moderator_id' => $this->admin->id,
            'listing_id' => $listing->id,
            'action' => 'denuncia_julgada_anuncio_bloqueado',
            'previous_report_status' => 'aberta',
            'new_report_status' => 'procedente',
            'previous_listing_status' => 'publicado',
            'new_listing_status' => 'bloqueado',
        ]);

        $this->get(route('listings.index'))->assertDontSee($listing->title);
        $this->get(route('listings.show', $listing->slug))->assertNotFound();
        $this->actingAs($this->seller)->patch(route('seller.anuncios.status', $listing))->assertForbidden();
    }

    public function test_listing_is_blocked_only_when_report_is_upheld_and_block_is_requested(): void
    {
        $withoutCheckbox = $this->listing();
        $this->judge($this->report($withoutCheckbox), 'procedente')->assertRedirect();
        $this->assertSame('publicado', $withoutCheckbox->fresh()->status);

        $dismissed = $this->listing();
        $this->judge($this->report($dismissed), 'improcedente', true)->assertRedirect();
        $this->assertSame('publicado', $dismissed->fresh()->status);

        $underReview = $this->listing();
        $this->judge($this->report($underReview), 'em_analise', true)->assertRedirect();
        $this->assertSame('publicado', $underReview->fresh()->status);

        $this->assertDatabaseMissing('moderation_audits', ['action' => 'denuncia_julgada_anuncio_bloqueado']);
        $this->assertDatabaseCount('moderation_audits', 3);
    }

    public function test_report_under_review_can_still_be_judged_but_closed_report_cannot(): void
    {
        $listing = $this->listing();
        $report = $this->report($listing);

        $this->judge($report, 'em_analise')->assertRedirect();
        $this->judge($report, 'improcedente')->assertRedirect();
        $this->assertSame('improcedente', $report->fresh()->status);

        $this->judge($report, 'procedente', true)->assertForbidden();
        $this->assertSame('improcedente', $report->fresh()->status);
        $this->assertSame('publicado', $listing->fresh()->status);
        $this->assertDatabaseCount('moderation_audits', 2);
    }

    public function test_judgment_requires_valid_decision_and_motivated_opinion(): void
    {
        $report = $this->report($this->listing());

        $this->judge($report, 'procedente', true, 'curto')->assertSessionHasErrors('resolution_notes');
        $this->judge($report, 'aberta')->assertSessionHasErrors('status');
        $this->judge($report, 'procedente', true, str_repeat('a', 1001))->assertSessionHasErrors('resolution_notes');

        $this->assertSame('aberta', $report->fresh()->status);
        $this->assertDatabaseCount('moderation_audits', 0);
    }

    public function test_admin_restores_blocked_listing_as_paused_and_seller_can_republish(): void
    {
        [$listing, $report] = $this->blockedByModeration();

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(route('admin.reports.restore', $report), false)
            ->assertSee('Restaurar anúncio');

        $this->restore($report)->assertRedirect()->assertSessionHas('success');

        $this->assertSame('pausado', $listing->fresh()->status);
        $this->assertSame('procedente', $report->fresh()->status);
        $this->assertDatabaseHas('moderation_audits', [
            'report_id' => $report->id,
            'moderator_id' => $this->admin->id,
            'listing_id' => $listing->id,
            'action' => 'anuncio_restaurado',
            'previous_report_status' => 'procedente',
            'new_report_status' => 'procedente',
            'previous_listing_status' => 'bloqueado',
            'new_listing_status' => 'pausado',
            'resolution_notes' => 'Vendedor apresentou comprovantes e o item foi verificado.',
        ]);

        $this->get(route('listings.index'))->assertDontSee($listing->title);
        $this->actingAs($this->seller)->patch(route('seller.anuncios.status', $listing))->assertRedirect();
        $this->assertSame('publicado', $listing->fresh()->status);
        $this->get(route('listings.index'))->assertSee($listing->title);

        $this->actingAs($this->admin)->get(route('admin.reports.index'))
            ->assertDontSee(route('admin.reports.restore', $report), false)
            ->assertSee('restaurou o anúncio');
    }

    public function test_listing_blocked_while_draft_returns_to_draft(): void
    {
        [$listing, $report] = $this->blockedByModeration('rascunho');

        $this->restore($report)->assertRedirect();

        $this->assertSame('rascunho', $listing->fresh()->status);
    }

    public function test_restoration_is_restricted_to_admins_and_blocked_listings(): void
    {
        [$listing, $report] = $this->blockedByModeration();

        $this->restore($report, $this->seller)->assertForbidden();
        $this->restore($report, $this->reporter)->assertForbidden();
        $this->assertSame('bloqueado', $listing->fresh()->status);

        $this->restore($report, null, 'curto')->assertSessionHasErrors('resolution_notes');
        $this->assertSame('bloqueado', $listing->fresh()->status);

        $this->restore($report)->assertRedirect();
        $this->restore($report)->assertForbidden();
        $this->assertSame(1, $report->audits()->where('action', 'anuncio_restaurado')->count());

        $open = $this->report($this->listing());
        $this->restore($open)->assertForbidden();

        $dismissed = $this->listing();
        $dismissedReport = $this->report($dismissed);
        $this->judge($dismissedReport, 'improcedente')->assertRedirect();
        $this->restore($dismissedReport)->assertForbidden();
        $this->assertSame('publicado', $dismissed->fresh()->status);
    }

    public function test_archived_blocked_listing_cannot_be_restored(): void
    {
        [$listing, $report] = $this->blockedByModeration();
        $listing->update(['archived_at' => now()]);

        $this->restore($report)->assertForbidden();

        $this->assertSame('bloqueado', $listing->fresh()->status);
    }
}
