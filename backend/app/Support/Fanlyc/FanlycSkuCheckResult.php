<?php

namespace App\Support\Fanlyc;

final class FanlycSkuCheckResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?array $payload = null,
        public readonly ?string $checkedSku = null,
    ) {
    }
}
