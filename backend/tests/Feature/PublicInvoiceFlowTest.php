<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicInvoiceFlowTest extends TestCase
{
    public function test_invoice_resolution_is_available_without_authentication(): void
    {
        $response = $this->postJson('/api/invoices/resolve', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['qr_raw_text']);
    }

    public function test_invoice_registration_is_available_without_authentication_and_requires_participant_data(): void
    {
        $response = $this->postJson('/api/invoices/scan', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'qr_raw_text',
                'document_number',
                'first_name',
                'last_name',
                'phone',
                'email',
            ]);
    }
}
