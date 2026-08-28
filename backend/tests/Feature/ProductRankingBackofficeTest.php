<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignProductRule;
use App\Models\FraudFlag;
use App\Models\PromoWinner;
use App\Models\RegisteredInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRankingBackofficeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Campaign $campaign;
    private CampaignProductRule $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->campaign = Campaign::query()->where('slug', 'malta-vigor-honor')->firstOrFail();
        $this->campaign->forceFill([
            'status' => 'active',
            'participation_mode' => 'product_ranking',
            'rules' => ['winner_slots' => 5, 'minimum_age' => 18],
            'ranking_frozen_at' => null,
        ])->save();
        $this->product = CampaignProductRule::query()->create([
            'campaign_id' => $this->campaign->id,
            'barcode' => '089646132283',
            'presentation' => '355 ml',
            'product_name' => 'Malta Vigor',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_open_the_product_ranking_operations_center_without_a_legacy_key(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.campaigns.product-ranking.operations', $this->campaign))
            ->assertOk()
            ->assertSee('Factura manual auditada')
            ->assertSee('Prevención de fraude')
            ->assertSee('Ranking en tiempo real');
    }

    public function test_admin_can_register_a_manual_invoice_with_multiple_product_lines_and_audit_it(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.campaigns.product-ranking.manual-invoice', $this->campaign),
            [
                'cedula' => '8-999-1000',
                'email' => 'cliente.manual@example.com',
                'full_name' => 'Cliente Manual',
                'invoice_number' => 'SC-MANUAL-100',
                'issued_at' => '2026-09-15 10:30:00',
                'reason' => 'Factura y productos verificados contra evidencia fotográfica.',
                'products' => [
                    ['barcode' => $this->product->barcode, 'quantity' => 2],
                    ['barcode' => $this->product->barcode, 'quantity' => 3],
                ],
            ],
        );

        $response->assertRedirect()->assertSessionHasNoErrors();
        $participant = User::query()->where('cedula', '8-999-1000')->firstOrFail();
        $invoice = RegisteredInvoice::query()->where('invoice_number', 'SC-MANUAL-100')->firstOrFail();

        $this->assertSame($participant->id, $invoice->user_id);
        $this->assertSame(5, $invoice->eligible_units);
        $this->assertSame('approved', $invoice->validation_status);
        $this->assertSame('admin_manual_entry', data_get($invoice->dgi_response_payload, 'source'));
        $this->assertCount(2, $invoice->items);
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'invoice.manual_product_entry',
            'entity_type' => 'registered_invoice',
            'entity_id' => $invoice->id,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_manual_invoice_rejects_conflicting_cedula_and_email_identities(): void
    {
        User::factory()->create(['role' => 'client', 'cedula' => '8-111-1111', 'email' => 'one@example.com']);
        User::factory()->create(['role' => 'client', 'cedula' => '8-222-2222', 'email' => 'two@example.com']);

        $this->actingAs($this->admin)->from(route('admin.campaigns.product-ranking.operations', $this->campaign))->post(
            route('admin.campaigns.product-ranking.manual-invoice', $this->campaign),
            [
                'cedula' => '8-111-1111',
                'email' => 'two@example.com',
                'invoice_number' => 'SC-CONFLICT-1',
                'issued_at' => '2026-09-15 10:30:00',
                'reason' => 'Prueba de identidad conflictiva.',
                'products' => [['barcode' => $this->product->barcode, 'quantity' => 1]],
            ],
        )->assertRedirect()->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('registered_invoices', ['invoice_number' => 'SC-CONFLICT-1']);
    }

    public function test_manual_invoice_rejects_a_purchase_outside_campaign_dates(): void
    {
        $this->actingAs($this->admin)->from(route('admin.campaigns.product-ranking.operations', $this->campaign))->post(
            route('admin.campaigns.product-ranking.manual-invoice', $this->campaign),
            [
                'cedula' => '8-333-3333',
                'email' => 'outside@example.com',
                'full_name' => 'Compra Fuera de Fecha',
                'invoice_number' => 'SC-OUTSIDE-1',
                'issued_at' => '2026-11-01 00:00:00',
                'reason' => 'Validación de límites de vigencia de la campaña.',
                'products' => [['barcode' => $this->product->barcode, 'quantity' => 1]],
            ],
        )->assertRedirect()->assertSessionHasErrors('issued_at');

        $this->assertDatabaseMissing('registered_invoices', ['invoice_number' => 'SC-OUTSIDE-1']);
    }

    public function test_freezing_selects_five_campaign_winners_and_ordered_alternates_without_phase_dependency(): void
    {
        foreach (range(1, 7) as $position) {
            $participant = User::factory()->create([
                'role' => 'client',
                'cedula' => '8-500-'.$position,
                'birthdate' => '1990-01-01',
                'is_employee' => false,
            ]);
            RegisteredInvoice::query()->create([
                'user_id' => $participant->id,
                'campaign_id' => $this->campaign->id,
                'cufe' => 'RANK-'.$position,
                'qr_raw_text' => 'RANK-'.$position,
                'invoice_number' => 'RANK-'.$position,
                'purchase_amount' => 0,
                'status' => 'approved',
                'validation_status' => 'approved',
                'eligible_units' => 20 - $position,
                'product_validation_status' => 'matched',
            ]);
        }

        $this->actingAs($this->admin)
            ->post(route('admin.campaigns.product-ranking.freeze', $this->campaign))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(5, PromoWinner::query()->where('campaign_id', $this->campaign->id)->where('status', 'selected')->count());
        $this->assertSame(2, PromoWinner::query()->where('campaign_id', $this->campaign->id)->where('status', 'alternate')->count());
        $this->assertSame([1, 2], PromoWinner::query()->where('campaign_id', $this->campaign->id)->where('status', 'alternate')->orderBy('alternate_position')->pluck('alternate_position')->all());
        $this->assertNull(PromoWinner::query()->where('campaign_id', $this->campaign->id)->firstOrFail()->phase_id);
        $this->assertNotNull($this->campaign->fresh()->ranking_frozen_at);
        $this->actingAs($this->admin)
            ->get(route('admin.campaigns.product-ranking.operations', $this->campaign))
            ->assertOk()
            ->assertSee('Suplente 1');
    }

    public function test_replacement_promotes_the_first_available_alternate_and_is_audited(): void
    {
        $winnerUser = User::factory()->create(['role' => 'client']);
        $alternateUser = User::factory()->create(['role' => 'client']);
        $winner = PromoWinner::query()->create([
            'campaign_id' => $this->campaign->id,
            'phase_id' => null,
            'user_id' => $winnerUser->id,
            'leaderboard_position' => 1,
            'selection_reason' => 'product_ranking',
            'status' => 'selected',
        ]);
        $alternate = PromoWinner::query()->create([
            'campaign_id' => $this->campaign->id,
            'phase_id' => null,
            'user_id' => $alternateUser->id,
            'leaderboard_position' => 6,
            'alternate_position' => 1,
            'selection_reason' => 'product_ranking_alternate',
            'status' => 'alternate',
        ]);

        $this->actingAs($this->admin)->post(
            route('admin.campaigns.product-ranking.replace-winner', [$this->campaign, $winner]),
            ['reason' => 'No reclamó el premio dentro del plazo establecido.'],
        )->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('disqualified', $winner->fresh()->status);
        $this->assertSame('selected', $alternate->fresh()->status);
        $this->assertSame($winner->id, $alternate->fresh()->replacement_for_winner_id);
        $this->assertTrue(AuditLog::query()->where('event_type', 'campaign.product_ranking.winner_replaced')->exists());
    }

    public function test_admin_can_resolve_a_campaign_fraud_alert_with_an_audit_trail(): void
    {
        $participant = User::factory()->create(['role' => 'client']);
        $invoice = RegisteredInvoice::query()->create([
            'user_id' => $participant->id,
            'campaign_id' => $this->campaign->id,
            'cufe' => 'FRAUD-CHECK-1',
            'qr_raw_text' => 'FRAUD-CHECK-1',
            'invoice_number' => 'FRAUD-CHECK-1',
            'purchase_amount' => 0,
            'status' => 'approved',
            'validation_status' => 'approved',
            'eligible_units' => 12,
            'product_validation_status' => 'matched',
        ]);
        $flag = FraudFlag::query()->create([
            'user_id' => $participant->id,
            'registered_invoice_id' => $invoice->id,
            'flag_type' => 'manual_review',
            'source' => 'system',
            'severity' => 'high',
            'status' => 'open',
            'title' => 'Cantidad inusual',
            'description' => 'Revisar evidencia de compra.',
        ]);

        $this->actingAs($this->admin)->post(
            route('admin.campaigns.product-ranking.resolve-fraud', [$this->campaign, $flag]),
            ['status' => 'resolved', 'resolution_notes' => 'Evidencia revisada y caso documentado por supervisión.'],
        )->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('resolved', $flag->fresh()->status);
        $this->assertSame($this->admin->id, $flag->fresh()->reviewed_by_user_id);
        $this->assertTrue(AuditLog::query()->where('event_type', 'fraud.flag.reviewed')->where('entity_id', $invoice->id)->exists());
    }
}
