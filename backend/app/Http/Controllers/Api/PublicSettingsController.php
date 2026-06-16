<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;

class PublicSettingsController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $officialTerms = $this->officialTerms();

            return response()->json([
                'auth_bg_youtube_id' => SiteSetting::get('auth_bg_youtube_id', ''),
                'auth_logo_url' => SiteSetting::get('auth_logo_url', ''),
                'header_logo_url' => SiteSetting::get('header_logo_url', ''),
                'participant_brands' => SiteSetting::get('participant_brands', ''),
                'hero_video_url' => SiteSetting::get('hero_video_url', ''),
                'seo_site_title' => SiteSetting::get('seo_site_title', ''),
                'seo_meta_description' => SiteSetting::get('seo_meta_description', ''),
                'seo_meta_keywords' => SiteSetting::get('seo_meta_keywords', ''),
                'seo_og_title' => SiteSetting::get('seo_og_title', ''),
                'seo_og_description' => SiteSetting::get('seo_og_description', ''),
                'seo_og_image' => SiteSetting::get('seo_og_image', ''),
                'terms_and_conditions' => SiteSetting::get('terms_and_conditions', '') ?: $officialTerms,
                'recaptcha_site_key'  => config('contest.recaptcha_site_key', ''),
                'allow_google_auth'   => (bool) config('contest.allow_google_auth', false),
                'google_client_id'    => config('services.google.client_id', ''),
                'registration_deadline'  => config('contest.registration_deadline', '2026-06-10 23:59:59'),
                'invoice_age_policy'     => SiteSetting::get('invoice_age_policy', 'none'),
                'theme_background'       => SiteSetting::get('theme_background', ''),
                'theme_surface_low'      => SiteSetting::get('theme_surface_low', ''),
                'theme_surface'          => SiteSetting::get('theme_surface', ''),
                'theme_surface_high'     => SiteSetting::get('theme_surface_high', ''),
                'theme_primary'          => SiteSetting::get('theme_primary', ''),
                'theme_secondary'        => SiteSetting::get('theme_secondary', ''),
                'theme_text_main'        => SiteSetting::get('theme_text_main', ''),
                'theme_outline_variant'  => SiteSetting::get('theme_outline_variant', ''),
                'show_scanner_debug'  => SiteSetting::get('show_scanner_debug', '0') === '1',
                'show_auth_ticker'    => SiteSetting::get('show_auth_ticker', '1') !== '0',
                'contact_email'       => SiteSetting::get('contact_email', ''),
                'contact_phone'       => SiteSetting::get('contact_phone', ''),
                'contact_address'     => SiteSetting::get('contact_address', ''),
                'contact_hours'       => SiteSetting::get('contact_hours', ''),
            ]);
        } catch (Throwable) {
            return response()->json([
                'auth_bg_youtube_id'  => env('AUTH_BG_YOUTUBE_ID', 'O9diw9_5pys'),
                'auth_logo_url'       => env('AUTH_LOGO_URL', ''),
                'header_logo_url'     => env('HEADER_LOGO_URL', ''),
                'participant_brands'  => env('PARTICIPANT_BRANDS', ''),
                'hero_video_url'      => '',
                'seo_site_title'      => '',
                'seo_meta_description' => '',
                'seo_meta_keywords'   => '',
                'seo_og_title'        => '',
                'seo_og_description'  => '',
                'seo_og_image'        => '',
                'terms_and_conditions' => $this->officialTerms(),
                'recaptcha_site_key'  => '',
                'allow_google_auth'   => false,
                'google_client_id'    => '',
                'registration_deadline'  => config('contest.registration_deadline', '2026-06-10 23:59:59'),
                'invoice_age_policy'     => 'none',
                'theme_background'       => '',
                'theme_surface_low'      => '',
                'theme_surface'          => '',
                'theme_surface_high'     => '',
                'theme_primary'          => '',
                'theme_secondary'        => '',
                'theme_text_main'        => '',
                'theme_outline_variant'  => '',
                'show_scanner_debug'  => false,
                'show_auth_ticker'    => true,
                'contact_email'       => '',
                'contact_phone'       => '',
                'contact_address'     => '',
                'contact_hours'       => '',
            ]);
        }
    }

    public function branches(): JsonResponse
    {
        return response()->json([
            'data' => Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function updateYoutubeId(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'youtube_id' => ['required', 'string', 'max:20'],
        ]);

        SiteSetting::set('auth_bg_youtube_id', $validated['youtube_id']);

        return response()->json(['message' => 'Video actualizado.']);
    }

    private function officialTerms(): string
    {
        return <<<'TERMS'
TERMINOS Y CONDICIONES: PROMO DE FACTURAS SUPER CARNES

1. La promocion aplica a clientes que escaneen una factura valida de Super Carnes y completen el formulario de registro.
2. Cada cedula solo puede participar una vez.
3. La factura debe superar el monto minimo configurado y pasar la validacion del sistema.
4. Los premios y el alcance de la promocion se informaran en los medios oficiales de Super Carnes.
5. Super Carnes puede rechazar registros fraudulentos, duplicados o incompletos.
6. Al participar, el usuario acepta el uso de sus datos para fines de validacion y entrega de premios.
TERMS;
    }
}
