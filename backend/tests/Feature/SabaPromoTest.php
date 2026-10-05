<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\SabaEntry;
use App\Models\User;
use App\Support\ContestInvoiceVerifier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Tests\TestCase;

class SabaPromoTest extends TestCase
{
    use RefreshDatabase;

    private Campaign $campaign;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-05 15:00:00', 'America/Panama'));
        Mail::fake();
        Cache::flush();
        config(['contest.official_issuer_names' => ['IMPORTADORA VIRZI']]);

        $this->branch = Branch::query()->where('store_number', 5)->firstOrFail();

        $this->campaign = Campaign::query()->where('slug', 'saba')->firstOrFail();
        $this->campaign->forceFill(['status' => 'active'])->save();
        $this->campaign->productRules()->create(['barcode' => '7501019006210', 'product_name' => 'Saba Buenas Noches', 'is_active' => true]);
    }

    public function test_public_page_payload_is_available_without_login(): void
    {
        $this->getJson('/api/public/saba')
            ->assertOk()
            ->assertJsonPath('data.is_open', true)
            ->assertJsonPath('data.winner_slots', 50)
            ->assertJsonPath('data.invoice_issued_from', '2026-09-28');
    }

    public function test_invoice_with_a_saba_product_registers_one_participation(): void
    {
        $this->fakeInvoice('FE-TEST-SABA-1-0000000000', '2026-10-02 10:00:00', [['codigo' => '7501019006210', 'cantidad' => 2, 'descripcion' => 'SABA BUENAS NOCHES']]);

        $this->postJson('/api/public/saba/resolve', ['qr_raw_text' => 'FE-TEST-SABA-1-0000000000'])
            ->assertOk()
            ->assertJsonPath('data.eligible_units', 2)
            ->assertJsonPath('data.product_check_status', 'matched');

        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-1-0000000000'))
            ->assertCreated()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.first_name', 'Maria');

        $entry = SabaEntry::query()->sole();
        $this->assertSame('8-123-4567', $entry->cedula);
        $this->assertSame('61234567', $entry->phone);
        $this->assertSame($this->branch->id, $entry->branch_id);
        $this->assertStringContainsString('mi negocio', $entry->story);
    }

    public function test_the_same_cedula_cannot_participate_twice_even_with_another_invoice(): void
    {
        $this->fakeInvoice('FE-TEST-SABA-1-0000000000', '2026-10-02 10:00:00', [['codigo' => '7501019006210', 'cantidad' => 1]]);
        $this->fakeInvoice('FE-TEST-SABA-2-0000000000', '2026-10-03 10:00:00', [['codigo' => '7501019006210', 'cantidad' => 1]]);

        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-1-0000000000'))->assertCreated();
        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-2-0000000000', ['cedula' => '08-0123-04567']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cedula');

        $this->assertSame(1, SabaEntry::query()->count());
    }

    public function test_rejected_participation_frees_the_cedula(): void
    {
        $this->fakeInvoice('FE-TEST-SABA-1-0000000000', '2026-10-02 10:00:00', [['codigo' => '7501019006210', 'cantidad' => 1]]);
        $this->fakeInvoice('FE-TEST-SABA-2-0000000000', '2026-10-03 10:00:00', [['codigo' => '7501019006210', 'cantidad' => 1]]);
        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-1-0000000000'))->assertCreated();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->post(route('admin.saba.reject', SabaEntry::query()->sole()), ['reason' => 'Prueba'])
            ->assertRedirect();

        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-2-0000000000'))->assertCreated();
        $this->assertSame(2, SabaEntry::query()->count());
    }

    public function test_invoice_without_saba_products_is_rejected(): void
    {
        $this->fakeInvoice('FE-TEST-OTRO-0000000000', '2026-10-02 10:00:00', [['codigo' => '089646132283', 'cantidad' => 3]]);

        $this->postJson('/api/public/saba/resolve', ['qr_raw_text' => 'FE-TEST-OTRO-0000000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_raw_text');
    }

    public function test_invoice_issued_before_the_promo_is_rejected(): void
    {
        $this->fakeInvoice('FE-TEST-VIEJA-0000000000', '2026-09-27 18:00:00', [['codigo' => '7501019006210', 'cantidad' => 1]]);

        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-VIEJA-0000000000'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('qr_raw_text');
    }

    public function test_minors_and_missing_eligibility_declaration_are_rejected(): void
    {
        $this->fakeInvoice('FE-TEST-SABA-1-0000000000', '2026-10-02 10:00:00', [['codigo' => '7501019006210', 'cantidad' => 1]]);

        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-1-0000000000', ['birthdate' => '2010-01-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('birthdate');

        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-1-0000000000', ['declares_eligibility' => false]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('declares_eligibility');
    }

    public function test_unreadable_product_detail_goes_to_manual_review(): void
    {
        $this->fakeInvoice('FE-TEST-SIN-DETALLE-0000000000', '2026-10-02 10:00:00', null);

        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SIN-DETALLE-0000000000'))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending_review');
    }

    public function test_registration_is_closed_while_the_campaign_is_paused(): void
    {
        $this->campaign->forceFill(['status' => 'paused'])->save();
        $this->fakeInvoice('FE-TEST-SABA-1-0000000000', '2026-10-02 10:00:00', [['codigo' => '7501019006210', 'cantidad' => 1]]);

        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-1-0000000000'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('campaign');
    }

    public function test_admin_can_load_skus_one_per_line_or_comma_separated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.saba.products.update'), [
                'mode' => 'replace',
                'products_text' => "111111, 222222\n333333\n444444|Saba Nocturna",
            ])
            ->assertRedirect(route('admin.saba.settings'));

        $this->assertEqualsCanonicalizing(
            ['111111', '222222', '333333', '444444'],
            $this->campaign->productRules()->pluck('barcode')->all(),
        );
        $this->assertSame('Saba Nocturna', $this->campaign->productRules()->where('barcode', '444444')->value('product_name'));
    }

    public function test_admin_pages_render(): void
    {
        $this->fakeInvoice('FE-TEST-SABA-1-0000000000', '2026-10-02 10:00:00', [['codigo' => '7501019006210', 'cantidad' => 1]]);
        $this->postJson('/api/public/saba/register', $this->participant('FE-TEST-SABA-1-0000000000'))->assertCreated();
        $admin = User::factory()->create(['role' => 'admin']);
        $entry = SabaEntry::query()->sole();

        $this->actingAs($admin)->get(route('admin.saba'))->assertOk()->assertSee('Maria Perez');
        $this->actingAs($admin)->get(route('admin.saba.settings'))->assertOk()->assertSee('7501019006210');
        $this->actingAs($admin)->get(route('admin.saba.show', $entry))->assertOk()->assertSee('mi negocio');
        $this->actingAs($admin)->get(route('admin.saba.export'))->assertOk();

        $this->actingAs($admin)->post(route('admin.saba.winner.select', $entry), ['winner_position' => 1])->assertRedirect();
        $this->assertSame(1, $entry->fresh()->winner_position);
    }

    private function participant(string $cufe, array $overrides = []): array
    {
        return array_merge([
            'qr_raw_text' => $cufe,
            'full_name' => 'maria perez',
            'cedula' => '8-123-4567',
            'phone' => '6123-4567',
            'email' => 'Maria@example.com',
            'birthdate' => '1990-05-10',
            'story' => 'Quiero ganar porque mi negocio de comida en la esquina necesita una tolda para atender a mis clientes cuando llueve.',
            'declares_eligibility' => true,
            'accepted_terms' => true,
            'terms_version' => null,
        ], $overrides);
    }

    /** @param  array<int, array<string, mixed>>|null  $items */
    private function fakeInvoice(string $cufe, string $issuedAt, ?array $items): void
    {
        static $invoices = [];
        $invoices[$cufe] = [
            'cufe' => $cufe,
            'invoice_number' => $cufe,
            'purchase_amount' => 12.50,
            'issued_at' => CarbonImmutable::parse($issuedAt, 'America/Panama'),
            'issuer_ruc' => '123',
            'issuer_name' => 'IMPORTADORA VIRZI S.A.',
            'issuer_address' => null,
            'issuer_phone' => null,
            'issuer_branch_number' => 5,
            'payload' => $items === null ? ['datos' => []] : ['datos' => ['items' => $items]],
        ];

        $this->mock(ContestInvoiceVerifier::class, function (MockInterface $mock) use (&$invoices): void {
            $mock->shouldReceive('resolve')->andReturnUsing(fn (string $cufe) => $invoices[$cufe]);
            $mock->shouldReceive('verify')->andReturnUsing(fn ($user, array $payload) => [
                'status' => 'approved',
                'notes' => 'ok',
                'canonical_cufe' => $payload['cufe'],
                'payload' => [],
            ]);
        });
    }
}
