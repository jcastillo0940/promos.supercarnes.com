<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegisteredInvoice;
use App\Models\Campaign;
use App\Models\CampaignParticipantConsent;
use App\Models\User;
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
        $authUser = $request->user('sanctum');
        $isMaltaCampaign = $request->string('campaign_slug')->toString() === 'malta-vigor';
        $requestedCedula = preg_replace('/[^0-9-]/', '', $request->string('document_number')->toString()) ?? '';
        $existingMaltaParticipant = $isMaltaCampaign && $requestedCedula !== ''
            ? User::query()->where('cedula', $requestedCedula)->where('role', 'client')->first()
            : null;
        $maltaCampaign = $isMaltaCampaign
            ? Campaign::query()->where('slug', 'malta-vigor')->first()
            : null;
        $hasCurrentMaltaConsent = $existingMaltaParticipant && $maltaCampaign?->terms_version
            ? CampaignParticipantConsent::query()
                ->where('campaign_id', $maltaCampaign->id)
                ->where('user_id', $existingMaltaParticipant->id)
                ->where('terms_version', $maltaCampaign->terms_version)
                ->exists()
            : false;
        $canReuseMaltaProfile = $existingMaltaParticipant
            && $existingMaltaParticipant->email
            && $existingMaltaParticipant->phone
            && $existingMaltaParticipant->birthdate
            && $hasCurrentMaltaConsent;
        $participantFieldPresence = $canReuseMaltaProfile ? 'nullable' : 'required';

        $data = $request->validate([
            'qr_raw_text' => ['required', 'string', 'max:2048'],
            'purchase_amount' => ['required', 'numeric', 'min:0.01'],
            'invoice_number' => ['nullable', 'string', 'max:80'],
            'issued_at' => ['nullable', 'date'],
            'document_type' => ['required', 'in:cedula,passport,residente'],
            'document_number' => ['required', 'string', 'max:40'],
            'first_name' => [$participantFieldPresence, 'string', 'max:80'],
            'last_name' => [$participantFieldPresence, 'string', 'max:80'],
            'full_name' => [$participantFieldPresence, 'string', 'max:150'],
            'cedula' => ['required', 'string', 'max:40'],
            'phone' => [$participantFieldPresence, 'string', 'max:20'],
            'email' => [$participantFieldPresence, 'email', 'max:150'],
            'birthdate' => [$participantFieldPresence, 'date', 'before:today'],
            'accepted_terms' => ['nullable', 'boolean'],
            'terms_version' => ['nullable', 'string', 'max:80'],
            'branch_id' => ['nullable', 'integer'],
            'dad_reason' => ['nullable', 'string', 'max:300'],
            'campaign_slug' => ['nullable', 'string', 'max:120'],
            'entrepreneur_name' => ['nullable', 'string', 'max:180'],
            'entrepreneur_province' => ['nullable', 'string', 'max:120'],
            'nearest_branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'entrepreneur_type' => ['nullable', 'string', 'max:120'],
            'entrepreneur_story' => ['nullable', 'string'],
            'entrepreneur_reason' => ['nullable', 'string'],
        ]);

        $participantUser = $authUser ?? ($canReuseMaltaProfile ? $existingMaltaParticipant : null);
        if ($participantUser) {
            $nameParts = preg_split('/\s+/', trim((string) ($participantUser->full_name ?? $participantUser->name)), 2) ?: [];
            $data['document_type'] = $participantUser->document_type ?? 'cedula';
            $data['document_number'] = $participantUser->cedula;
            $data['cedula'] = $participantUser->cedula;
            $data['first_name'] = $nameParts[0] ?? $participantUser->name;
            $data['last_name'] = $nameParts[1] ?? '.';
            $data['full_name'] = $participantUser->full_name ?? $participantUser->name;
            $data['phone'] = $participantUser->phone;
            $data['email'] = $participantUser->email;
            $data['birthdate'] = $data['birthdate'] ?? $participantUser->birthdate?->toDateString();
        }

        $result = $this->registrationService->registerGuest($data, $request);

        return response()->json([
            'message' => $result['message'],
            'invoice' => $result['invoice'],
            'campaign_total' => $result['campaign_total'] ?? null,
            'campaign_units_total' => $result['campaign_units_total'] ?? null,
            'campaign_threshold' => $result['campaign_threshold'] ?? null,
            'campaign_qualified' => $result['campaign_qualified'] ?? null,
            'eligible_units' => $result['eligible_units'] ?? 0,
            'matched_products' => $result['matched_products'] ?? [],
            'product_validation_status' => $result['product_validation_status'] ?? 'not_applicable',
        ], 201);
    }

    public function resolve(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr_raw_text' => ['required', 'string', 'max:2048'],
            'campaign_slug' => ['nullable', 'string', 'max:120'],
        ]);

        $result = $this->registrationService->resolveInvoiceData($data['qr_raw_text'], $data['campaign_slug'] ?? null);

        return response()->json([
            'data' => $result,
        ]);
    }
}
