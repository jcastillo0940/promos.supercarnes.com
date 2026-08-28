<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignParticipantConsent;
use App\Models\RegisteredInvoice;
use App\Models\User;
use App\Support\ContestInvoiceRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class MaltaParticipantLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_participant_with_current_consent_can_register_with_only_their_cedula(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $campaign->forceFill(['terms_version' => 'v1'])->save();
        $user = User::factory()->create([
            'name' => 'Jeremy Castillo',
            'full_name' => 'Jeremy Castillo',
            'cedula' => '8-864-1164',
            'email' => 'jeremy@example.com',
            'phone' => '6000-0000',
            'birthdate' => '1990-01-01',
            'role' => 'client',
        ]);
        CampaignParticipantConsent::query()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'terms_version' => 'v1',
            'accepted_at' => now(),
        ]);

        $this->postJson('/api/public/malta/participant', ['cedula' => '8-864-1164'])
            ->assertOk()
            ->assertJsonPath('data.registered', true)
            ->assertJsonPath('data.display_name', 'Jeremy');
    }

    public function test_incomplete_participant_is_sent_through_the_one_time_registration_form(): void
    {
        User::factory()->create([
            'name' => 'Jeremy Castillo',
            'full_name' => 'Jeremy Castillo',
            'cedula' => '8-864-1164',
            'email' => 'jeremy@example.com',
            'phone' => '6000-0000',
            'birthdate' => '1990-01-01',
            'role' => 'client',
        ]);

        $this->postJson('/api/public/malta/participant', ['cedula' => '8-864-1164'])
            ->assertOk()
            ->assertJsonPath('data.registered', false)
            ->assertJsonPath('data.display_name', null);
    }

    public function test_progress_requires_matching_cedula_and_email(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $campaign->forceFill(['terms_version' => 'v1'])->save();
        User::factory()->create([
            'cedula' => '8-864-1164',
            'email' => 'jeremy@example.com',
            'role' => 'client',
        ]);

        $this->postJson('/api/public/malta/progress', [
            'cedula' => '8-864-1164',
            'email' => 'otro@example.com',
        ])->assertUnprocessable();
    }

    public function test_generic_campaign_progress_does_not_disclose_malta_totals_by_cedula_only(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $user = User::factory()->create([
            'cedula' => '8-864-1164',
            'email' => 'jeremy@example.com',
            'role' => 'client',
        ]);
        RegisteredInvoice::query()->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'cufe' => 'TEST-GENERIC-PROGRESS',
            'qr_raw_text' => 'TEST-GENERIC-PROGRESS',
            'purchase_amount' => 100,
            'status' => 'accepted',
            'validation_status' => 'approved',
            'eligible_units' => 10,
            'product_validation_status' => 'matched',
        ]);

        $this->getJson('/api/campaigns/malta-vigor/progress?document_number=8-864-1164')
            ->assertForbidden();
    }

    public function test_generic_progress_remains_blocked_for_a_legacy_malta_slug_during_migration(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $campaign->forceFill(['slug' => 'malta-vigor-honor'])->save();

        $this->getJson('/api/campaigns/malta-vigor-honor/progress?document_number=8-864-1164')
            ->assertForbidden();
    }

    public function test_authenticated_generic_progress_keeps_the_legitimate_self_lookup(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $user = User::factory()->create([
            'cedula' => '8-864-1164',
            'email' => 'jeremy@example.com',
            'role' => 'client',
        ]);
        RegisteredInvoice::query()->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'cufe' => 'TEST-AUTH-PROGRESS',
            'qr_raw_text' => 'TEST-AUTH-PROGRESS',
            'purchase_amount' => 100,
            'status' => 'accepted',
            'validation_status' => 'approved',
            'eligible_units' => 10,
            'product_validation_status' => 'matched',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/campaigns/malta-vigor/progress?document_number=8-000-0000')
            ->assertOk()
            ->assertJsonPath('data.campaign_units_total', 10);
    }

    public function test_participant_can_consult_their_accumulated_bottles_with_cedula_and_email(): void
    {
        $user = User::factory()->create([
            'name' => 'Jeremy Castillo',
            'full_name' => 'Jeremy Castillo',
            'cedula' => '8-864-1164',
            'email' => 'jeremy@example.com',
            'role' => 'client',
        ]);
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        RegisteredInvoice::query()->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'cufe' => 'TEST-CUFE-APPROVED',
            'qr_raw_text' => 'TEST-CUFE-APPROVED',
            'purchase_amount' => 10,
            'status' => 'accepted',
            'validation_status' => 'approved',
            'eligible_units' => 5,
            'product_validation_status' => 'matched',
        ]);
        RegisteredInvoice::query()->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'cufe' => 'TEST-CUFE-PENDING',
            'qr_raw_text' => 'TEST-CUFE-PENDING',
            'purchase_amount' => 10,
            'status' => 'pending_validation',
            'validation_status' => 'pending',
            'eligible_units' => 4,
            'product_validation_status' => 'undetermined',
        ]);
        $this->postJson('/api/public/malta/progress', [
            'cedula' => $user->cedula,
            'email' => strtoupper($user->email),
        ])
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Jeremy')
            ->assertJsonPath('data.campaign_units_total', 5)
            ->assertJsonPath('data.invoice_count', 2);
    }

    public function test_returning_participant_can_submit_an_invoice_with_only_their_cedula(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $campaign->forceFill(['terms_version' => 'v1'])->save();
        $user = User::factory()->create([
            'name' => 'Jeremy Castillo',
            'full_name' => 'Jeremy Castillo',
            'cedula' => '8-864-1164',
            'email' => 'jeremy@example.com',
            'phone' => '6000-0000',
            'birthdate' => '1990-01-01',
            'role' => 'client',
        ]);
        CampaignParticipantConsent::query()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'terms_version' => 'v1',
            'accepted_at' => now(),
        ]);
        $this->mock(ContestInvoiceRegistrationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('registerGuest')->once()->andReturn([
                'message' => 'Factura registrada. Se sumaron 2 unidades Malta Vigor.',
                'invoice' => ['id' => 10],
                'campaign_total' => 40,
                'campaign_units_total' => 5,
                'campaign_threshold' => 0,
                'campaign_qualified' => true,
                'eligible_units' => 2,
                'matched_products' => [],
                'product_validation_status' => 'matched',
            ]);
        });

        $this->postJson('/api/invoices/scan', [
            'qr_raw_text' => 'FE01200000032812-2-249262-1234567890',
            'purchase_amount' => 10,
            'document_type' => 'cedula',
            'document_number' => '8-864-1164',
            'cedula' => '8-864-1164',
            'campaign_slug' => 'malta-vigor',
        ])
            ->assertCreated()
            ->assertJsonPath('campaign_units_total', 5)
            ->assertJsonPath('eligible_units', 2);
    }

    public function test_new_participant_must_complete_all_registration_fields(): void
    {
        $this->postJson('/api/invoices/scan', [
            'qr_raw_text' => 'FE01200000032812-2-249262-1234567890',
            'purchase_amount' => 10,
            'document_type' => 'cedula',
            'document_number' => '8-999-9999',
            'cedula' => '8-999-9999',
            'campaign_slug' => 'malta-vigor',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['first_name', 'last_name', 'full_name', 'phone', 'email', 'birthdate']);
    }
}
