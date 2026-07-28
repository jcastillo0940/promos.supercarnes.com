<?php

namespace App\Support\Fanlyc;

final class FanlycEligibilityResult
{
    public function __construct(
        public readonly string $outcome,
        public readonly ?string $cufe = null,
        public readonly ?array $resolvedInvoice = null,
        public readonly ?int $branchId = null,
        public readonly ?int $fanlycZoneId = null,
        public readonly string $skuCheckStatus = 'undetermined',
        public readonly ?array $skuCheckPayload = null,
        public readonly ?string $notes = null,
    ) {
    }

    public function isApproved(): bool
    {
        return $this->outcome === 'approved';
    }
}
