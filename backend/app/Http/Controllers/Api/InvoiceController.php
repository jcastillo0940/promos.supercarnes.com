<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegisteredInvoice;
use App\Support\ContestInvoiceRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly ContestInvoiceRegistrationService $registrationService,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr_raw_text' => ['required', 'string', 'max:2048'],
            'purchase_amount' => ['required', 'numeric', 'min:0.01'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'],
            'document_type' => ['required', 'in:cedula,passport,residente'],
            'document_number' => ['required', 'string', 'max:40'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'full_name' => ['required', 'string', 'max:150'],
            'cedula' => ['required', 'string', 'max:40'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150'],
            'branch_id' => ['nullable', 'integer'],
            'dad_reason' => ['nullable', 'string', 'max:300'],
        ]);

        $result = $this->registrationService->registerGuest($data, $request);

        return response()->json([
            'message' => $result['message'],
            'invoice' => $result['invoice'],
        ], 201);
    }

    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr_raw_text' => ['required', 'string', 'max:2048'],
        ]);

        $result = $this->registrationService->resolveInvoiceData($data['qr_raw_text']);

        return response()->json([
            'data' => $result,
        ]);
    }
}
