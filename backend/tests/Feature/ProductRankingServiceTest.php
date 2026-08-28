<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignProductRule;
use App\Support\ProductRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRankingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_counts_only_units_matching_an_active_campaign_barcode(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $campaign->forceFill([
            'name' => 'Malta Vigor + Super Carnes',
            'status' => 'active',
            'participation_mode' => 'product_ranking',
            'is_listed' => true,
            'starts_at' => '2026-09-01 00:00:00',
            'ends_at' => '2026-10-30 23:59:59',
        ])->save();

        CampaignProductRule::query()->create([
            'campaign_id' => $campaign->id,
            'barcode' => '089646132283',
            'presentation' => '355 ml',
            'product_name' => 'Malta Vigor',
            'is_active' => true,
        ]);

        $result = app(ProductRankingService::class)->evaluate($campaign, [
            'payload' => [
                'datos' => [
                    'items' => [
                        ['barcode' => '089646132283', 'description' => 'Malta Vigor', 'quantity' => 3],
                        ['barcode' => '000000000000', 'description' => 'Otro producto', 'quantity' => 8],
                    ],
                ],
            ],
        ]);

        $this->assertSame('matched', $result['status']);
        $this->assertSame(3, $result['eligible_units']);
        $this->assertCount(1, $result['matched_products']);
        $this->assertSame('089646132283', $result['matched_products'][0]['barcode']);
    }

    public function test_it_returns_zero_when_invoice_lines_have_no_participating_barcode(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $campaign->forceFill([
            'name' => 'Malta Vigor + Super Carnes',
            'status' => 'active',
            'participation_mode' => 'product_ranking',
            'is_listed' => true,
            'starts_at' => '2026-09-01 00:00:00',
            'ends_at' => '2026-10-30 23:59:59',
        ])->save();

        CampaignProductRule::query()->create([
            'campaign_id' => $campaign->id,
            'barcode' => '089646132283',
            'product_name' => 'Malta Vigor',
            'is_active' => true,
        ]);

        $result = app(ProductRankingService::class)->evaluate($campaign, [
            'payload' => [
                'datos' => [
                    'items' => [
                        ['barcode' => '000000000000', 'description' => 'Otro producto', 'quantity' => 2],
                    ],
                ],
            ],
        ]);

        $this->assertSame('matched', $result['status']);
        $this->assertSame(0, $result['eligible_units']);
        $this->assertSame([], $result['matched_products']);
    }

    public function test_it_reads_dgi_accented_product_fields(): void
    {
        $campaign = Campaign::query()->where('slug', 'malta-vigor')->firstOrFail();
        $campaign->forceFill([
            'name' => 'Malta Vigor + Super Carnes',
            'status' => 'active',
            'participation_mode' => 'product_ranking',
            'is_listed' => true,
        ])->save();

        CampaignProductRule::query()->create([
            'campaign_id' => $campaign->id,
            'barcode' => '743337001516',
            'presentation' => '1.4 L',
            'product_name' => 'Malta Vigor',
            'is_active' => true,
        ]);

        $result = app(ProductRankingService::class)->evaluate($campaign, [
            'payload' => [
                'datos' => [
                    'productos' => [[
                        'código' => '743337001516',
                        'descripción' => 'MALTA VIGOR 1.4 L',
                        'cantidad' => '1.000000',
                        'precio' => '1.660000',
                    ]],
                ],
            ],
        ]);

        $this->assertSame('matched', $result['status']);
        $this->assertSame(1, $result['eligible_units']);
        $this->assertSame('743337001516', $result['matched_products'][0]['barcode']);
        $this->assertSame('MALTA VIGOR 1.4 L', $result['matched_products'][0]['description']);
    }
}
