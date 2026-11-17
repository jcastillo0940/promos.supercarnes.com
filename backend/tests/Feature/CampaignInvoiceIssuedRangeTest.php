<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignInvoiceIssuedRangeTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Campaign $campaign, array $extra = []): array
    {
        return ['campaigns' => [$campaign->id => array_merge([
            'id' => $campaign->id,
            'name' => $campaign->name,
            'slug' => $campaign->slug,
            'status' => 'paused',
            'participation_mode' => $campaign->participation_mode ?? 'points',
            'invoice_scan_enabled' => '1',
        ], $extra)]];
    }

    public function test_edit_screen_shows_and_saves_invoice_issued_range_for_nivea(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $campaign = Campaign::query()->where('slug', 'nivea')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.invoice-backoffice.campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('Facturas emitidas hasta');

        $this->actingAs($admin)
            ->post(route('admin.invoice-backoffice.campaigns.update'), $this->payload($campaign, ['invoice_issued_to' => '2026-11-30']))
            ->assertSessionHasNoErrors();

        $rules = $campaign->fresh()->rules;
        $this->assertSame('2026-11-30', $rules['invoice_issued_to']);
        $this->assertSame('2026-10-05', $rules['invoice_issued_from']);
        $this->assertArrayHasKey('winner_slots', $rules);
    }

    public function test_rejects_end_before_start(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $campaign = Campaign::query()->where('slug', 'nivea')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.invoice-backoffice.campaigns.update'), $this->payload($campaign, ['invoice_issued_to' => '2026-10-01']))
            ->assertSessionHasErrors("campaigns.{$campaign->id}.invoice_issued_to");

        $this->assertSame('2026-11-14', $campaign->fresh()->rules['invoice_issued_to']);
    }
}
