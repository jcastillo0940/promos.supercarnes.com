<?php

namespace App\Support;

class CufeParser
{
    private const CUFE_SHORT_PREFIX = 'FE01200000032812-2-249262-';

    public function extract(string $rawText): ?string
    {
        if (strlen($rawText) > 2048) {
            return null;
        }

        $decoded = urldecode($rawText);
        $bare = strtoupper(trim($decoded));

        if (preg_match('/^\d{60}$/', $bare)) {
            return self::CUFE_SHORT_PREFIX.$bare;
        }

        if (str_contains($bare, '-') && preg_match('/^[A-Z0-9][A-Z0-9\-]{15,254}$/', $bare)) {
            return $bare;
        }

        $patterns = [
            '/[?&]chFE=([A-Z0-9\-]{16,255})(?:&|$)/i',
            '/[?&](?:cufe|CUFE)=([A-Z0-9\-]{16,255})/i',
            '/(?:cufe|CUFE)[=:\s]+([A-Z0-9\-]{16,255})/i',
            '/(?:codigoGeneracion|codigo-generacion|claveAcceso)[=:\s]+([A-Z0-9\-]{16,255})/i',
            '/https?:\/\/\S*?[?&](?:cufe|codigoGeneracion|claveAcceso)=([A-Z0-9\-]{16,255})/i',
            '/([A-F0-9]{32,128})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $decoded, $matches)) {
                return strtoupper(trim($matches[1]));
            }
        }

        return null;
    }
}
