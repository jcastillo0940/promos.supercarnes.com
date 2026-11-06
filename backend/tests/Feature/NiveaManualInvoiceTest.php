<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Campaign;
use App\Models\NiveaEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NiveaManualInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_register_an_invoice_without_cufe_once(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-06 15:00:00', 'America/Panama'));
        Mail::fake();

        $branch = Branch::query()->where('store_number', 5)->firstOrFail();
        $rule = Campaign::query()->where('slug', 'nivea')->firstOrFail()
            ->productRules()->create(['barcode' => '4005808', 'product_name' => 'NIVEA Creme', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = [
            'invoice_type' => 'series',
            'invoice_series' => 'bcht-1111',
            'invoice_date' => '2026-11-02',
            'branch_id' => $branch->id,
            'purchase_amount' => '18.75',
            'product_rule_id' => $rule->id,
            'product_quantity' => 1,
            'full_name' => 'ana gomez',
            'cedula' => '8-123-4567',
            'phone' => '6123-4567',
            'email' => 'ana@example.com',
            'birthdate' => '1990-05-10',
            'nominee_name' => 'rosa gomez',
            'story' => 'Mi mamá merece ser empollerada porque crió a sus cuatro hijos sola y siempre estuvo presente para toda la familia.',
            'confirmed_eligibility' => '1',
        ];

        $this->actingAs($admin)->post(route('admin.nivea.manual-register'), $payload)->assertRedirect();

        $entry = NiveaEntry::query()->sole();
        $this->assertSame('BCHT-1111', $entry->invoice_number);
        $this->assertSame('pending_review', $entry->status);
        $this->assertSame('manual_invoice', $entry->source);
        $this->assertSame('Rosa Gomez', $entry->nominee_name);

        $this->actingAs($admin)->get(route('admin.nivea.show', $entry))->assertOk()->assertSee('BCHT-1111');

        $this->actingAs($admin)
            ->post(route('admin.nivea.manual-register'), [...$payload, 'cedula' => '8-999-1111'])
            ->assertSessionHasErrors('invoice_series');
        $this->assertSame(1, NiveaEntry::query()->count());
    }
}
