<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\BlacklistController;
use App\Http\Controllers\Admin\FanlycController as AdminFanlycController;
use App\Http\Controllers\Admin\FanlycRedemptionController;
use App\Http\Controllers\Admin\FanlycStaffController;
use App\Http\Controllers\Admin\FanlycZoneController;
use App\Http\Controllers\Admin\FondaChallengeController as AdminFondaChallengeController;
use App\Http\Controllers\Admin\FondaJuryController;
use App\Http\Controllers\Admin\FondaMediaController;
use App\Http\Controllers\Admin\FondaResultsController;
use App\Http\Controllers\Admin\InvoiceBackofficeController;
use App\Http\Controllers\Admin\JurorController;
use App\Http\Controllers\Admin\NiveaController;
use App\Http\Controllers\Admin\OperatorController;
use App\Http\Controllers\Admin\SabaController;
use App\Http\Controllers\FanlycController;
use App\Http\Controllers\FondaChallengeController;

$frontendDist = realpath(base_path('../frontend/dist'));

$serveFrontendFile = function (string $filePath) {
    abort_unless(File::exists($filePath), 404);

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'js' => 'application/javascript; charset=UTF-8',
        'css' => 'text/css; charset=UTF-8',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'html' => 'text/html; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'otf'   => 'font/otf',
        'ttf'   => 'font/ttf',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
    ];

    return response()->file($filePath, [
        'Content-Type' => $mimeTypes[$extension] ?? 'application/octet-stream',
        'Cache-Control' => $extension === 'html' ? 'no-cache' : 'public, max-age=31536000, immutable',
    ]);
};

Route::get('/assets/{path}', function (string $path) use ($frontendDist, $serveFrontendFile) {
    abort_unless($frontendDist, 404);

    return $serveFrontendFile($frontendDist.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$path);
})->where('path', '.*');

Route::get('/{file}', function (string $file) use ($frontendDist, $serveFrontendFile) {
    abort_unless($frontendDist, 404);

    return $serveFrontendFile($frontendDist.DIRECTORY_SEPARATOR.$file);
})->where('file', '.+\.(svg|ico|webp|png|jpg|jpeg|gif|woff|woff2|ttf|otf|eot)');

// ─── Admin auth ───────────────────────────────────────────────────────────────
Route::get('/admin/login', [AdminLoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

// ─── Admin · super-admin only ─────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/adminrepus1car/dashboard', [InvoiceBackofficeController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/adminrepus1car', [InvoiceBackofficeController::class, 'index'])->name('admin.invoice-backoffice');
    Route::post('/adminrepus1car', [InvoiceBackofficeController::class, 'update'])->name('admin.invoice-backoffice.update');
    Route::post('/adminrepus1car/promociones', [InvoiceBackofficeController::class, 'storeCampaign'])->name('admin.invoice-backoffice.campaigns.store');
    Route::get('/adminrepus1car/promociones/{campaign}/editar', [InvoiceBackofficeController::class, 'editCampaign'])->name('admin.invoice-backoffice.campaigns.edit');
    Route::post('/adminrepus1car/promociones/{campaign}/estado', [InvoiceBackofficeController::class, 'toggleCampaignStatus'])->name('admin.invoice-backoffice.campaigns.toggle-status');
    Route::post('/adminrepus1car/campaigns', [InvoiceBackofficeController::class, 'updateCampaigns'])->name('admin.invoice-backoffice.campaigns.update');
    Route::get('/adminrepus1car/clientes/{user}', [InvoiceBackofficeController::class, 'customerHistory'])->name('admin.customers.history');
    Route::post('/adminrepus1car/clientes/{user}/ganador', [InvoiceBackofficeController::class, 'markCustomerAsWinner'])->name('admin.customers.mark-winner');
    Route::delete('/adminrepus1car/clientes/{user}/ganador', [InvoiceBackofficeController::class, 'unmarkCustomerAsWinner'])->name('admin.customers.unmark-winner');
    Route::post('/adminrepus1car/entrega-premio/{winner}/reabrir', [InvoiceBackofficeController::class, 'prizeDeliveryOverride'])->name('admin.prize-delivery.override');
    Route::get('/adminrepus1car/media/{path}', [InvoiceBackofficeController::class, 'media'])->where('path', '.*')->name('admin.media');

    // Operators management
    Route::get('/adminrepus1car/operadores', [OperatorController::class, 'index'])->name('admin.operators');
    Route::post('/adminrepus1car/operadores', [OperatorController::class, 'store'])->name('admin.operators.store');
    Route::post('/adminrepus1car/operadores/{operator}/modulos', [OperatorController::class, 'updateModules'])->name('admin.operators.modules');
    Route::post('/adminrepus1car/operadores/{operator}/estado', [OperatorController::class, 'toggleStatus'])->name('admin.operators.toggle-status');
});

// ─── Prize delivery (admin + supervisor + manager) ────────────────────────────
Route::middleware(['auth', 'role:admin,supervisor,manager'])->group(function () {
    Route::get('/adminrepus1car/entrega-premio', [InvoiceBackofficeController::class, 'prizeDeliveryIndex'])->name('admin.prize-delivery');
    Route::post('/adminrepus1car/entrega-premio', [InvoiceBackofficeController::class, 'prizeDeliveryLookup'])->name('admin.prize-delivery.lookup');
    Route::post('/adminrepus1car/entrega-premio/lookup', [InvoiceBackofficeController::class, 'prizeDeliveryFind'])->name('admin.prize-delivery.find');
    Route::post('/adminrepus1car/entrega-premio/{winner}', [InvoiceBackofficeController::class, 'prizeDeliveryStore'])->name('admin.prize-delivery.store');
});

// ─── Module: saba ─────────────────────────────────────────────────────────────
Route::middleware(['auth', 'module:saba'])->group(function () {
    Route::get('/adminrepus1car/saba', [SabaController::class, 'index'])->name('admin.saba');
    Route::get('/adminrepus1car/saba/configuracion', [SabaController::class, 'settings'])->name('admin.saba.settings');
    Route::post('/adminrepus1car/saba/configuracion', [SabaController::class, 'updateSettings'])->name('admin.saba.settings.update');
    Route::post('/adminrepus1car/saba/productos', [SabaController::class, 'updateProducts'])->name('admin.saba.products.update');
    Route::post('/adminrepus1car/saba/productos/{rule}/eliminar', [SabaController::class, 'destroyProduct'])->whereNumber('rule')->name('admin.saba.products.destroy');
    Route::post('/adminrepus1car/saba/archivos', [SabaController::class, 'updateAssets'])->name('admin.saba.assets.update');
    Route::post('/adminrepus1car/saba/estado', [SabaController::class, 'toggleStatus'])->name('admin.saba.status');
    Route::get('/adminrepus1car/saba/exportar', [SabaController::class, 'export'])->name('admin.saba.export');
    Route::post('/adminrepus1car/saba/registro-manual', [SabaController::class, 'manualRegister'])->name('admin.saba.manual-register');
    Route::get('/adminrepus1car/saba/participantes/{entry}', [SabaController::class, 'show'])->name('admin.saba.show');
    Route::post('/adminrepus1car/saba/participantes/{entry}/aprobar', [SabaController::class, 'approve'])->name('admin.saba.approve');
    Route::post('/adminrepus1car/saba/participantes/{entry}/rechazar', [SabaController::class, 'reject'])->name('admin.saba.reject');
    Route::post('/adminrepus1car/saba/participantes/{entry}/ganadora', [SabaController::class, 'selectWinner'])->name('admin.saba.winner.select');
    Route::post('/adminrepus1car/saba/participantes/{entry}/ganadora/quitar', [SabaController::class, 'removeWinner'])->name('admin.saba.winner.remove');
});

// ─── Module: nivea ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'module:nivea'])->group(function () {
    Route::get('/adminrepus1car/nivea', [NiveaController::class, 'index'])->name('admin.nivea');
    Route::get('/adminrepus1car/nivea/configuracion', [NiveaController::class, 'settings'])->name('admin.nivea.settings');
    Route::post('/adminrepus1car/nivea/configuracion', [NiveaController::class, 'updateSettings'])->name('admin.nivea.settings.update');
    Route::post('/adminrepus1car/nivea/productos', [NiveaController::class, 'updateProducts'])->name('admin.nivea.products.update');
    Route::post('/adminrepus1car/nivea/productos/{rule}/eliminar', [NiveaController::class, 'destroyProduct'])->whereNumber('rule')->name('admin.nivea.products.destroy');
    Route::post('/adminrepus1car/nivea/archivos', [NiveaController::class, 'updateAssets'])->name('admin.nivea.assets.update');
    Route::post('/adminrepus1car/nivea/estado', [NiveaController::class, 'toggleStatus'])->name('admin.nivea.status');
    Route::get('/adminrepus1car/nivea/exportar', [NiveaController::class, 'export'])->name('admin.nivea.export');
    Route::post('/adminrepus1car/nivea/registro-manual', [NiveaController::class, 'manualRegister'])->name('admin.nivea.manual-register');
    Route::get('/adminrepus1car/nivea/participantes/{entry}', [NiveaController::class, 'show'])->name('admin.nivea.show');
    Route::post('/adminrepus1car/nivea/participantes/{entry}/aprobar', [NiveaController::class, 'approve'])->name('admin.nivea.approve');
    Route::post('/adminrepus1car/nivea/participantes/{entry}/rechazar', [NiveaController::class, 'reject'])->name('admin.nivea.reject');
    Route::post('/adminrepus1car/nivea/participantes/{entry}/ganadora', [NiveaController::class, 'selectWinner'])->name('admin.nivea.winner.select');
    Route::post('/adminrepus1car/nivea/participantes/{entry}/ganadora/quitar', [NiveaController::class, 'removeWinner'])->name('admin.nivea.winner.remove');
});

// ─── Module: malta ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'module:malta'])->group(function () {
    Route::get('/adminrepus1car/promociones/{campaign}/ranking', [InvoiceBackofficeController::class, 'productRanking'])->name('admin.campaigns.product-ranking');
    Route::post('/adminrepus1car/promociones/{campaign}/ranking/freeze', [InvoiceBackofficeController::class, 'freezeProductRanking'])->name('admin.campaigns.product-ranking.freeze');
    Route::get('/adminrepus1car/promociones/{campaign}/operacion/exportar', [InvoiceBackofficeController::class, 'exportProductRanking'])->name('admin.campaigns.product-ranking.export');
    Route::get('/adminrepus1car/promociones/{campaign}/operacion', [InvoiceBackofficeController::class, 'productRankingOperations'])->name('admin.campaigns.product-ranking.operations');
    Route::post('/adminrepus1car/promociones/{campaign}/facturas-manuales', [InvoiceBackofficeController::class, 'storeManualProductInvoice'])->name('admin.campaigns.product-ranking.manual-invoice');
    Route::post('/adminrepus1car/promociones/{campaign}/ganadores/{winner}/reemplazar', [InvoiceBackofficeController::class, 'replaceProductRankingWinner'])->name('admin.campaigns.product-ranking.replace-winner');
    Route::post('/adminrepus1car/promociones/{campaign}/fraude/{flag}/resolver', [InvoiceBackofficeController::class, 'resolveProductRankingFraudFlag'])->name('admin.campaigns.product-ranking.resolve-fraud');
});

// ─── Module: fanlyc ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'module:fanlyc'])->group(function () {
    Route::get('/adminrepus1car/fanlyc', [AdminFanlycController::class, 'index'])->name('admin.fanlyc');
    Route::post('/adminrepus1car/fanlyc/registrar-manual', [AdminFanlycController::class, 'manualRegister'])->name('admin.fanlyc.manual-register');
    Route::get('/adminrepus1car/fanlyc/{invoice}', [AdminFanlycController::class, 'show'])->name('admin.fanlyc.show');
    Route::post('/adminrepus1car/fanlyc/{invoice}/aprobar', [AdminFanlycController::class, 'approve'])->name('admin.fanlyc.approve');
    Route::post('/adminrepus1car/fanlyc/{invoice}/rechazar', [AdminFanlycController::class, 'reject'])->name('admin.fanlyc.reject');
    Route::post('/adminrepus1car/fanlyc/cupones/{coupon}/anular', [AdminFanlycController::class, 'voidCoupon'])->name('admin.fanlyc.void-coupon');
    Route::get('/adminrepus1car/fanlyc-zonas', [FanlycZoneController::class, 'index'])->name('admin.fanlyc.zones');
    Route::post('/adminrepus1car/fanlyc-zonas/asignar', [FanlycZoneController::class, 'assignBranch'])->name('admin.fanlyc.zones.assign');
    Route::post('/adminrepus1car/fanlyc-zonas/{mapping}/quitar', [FanlycZoneController::class, 'unassignBranch'])->name('admin.fanlyc.zones.unassign');
    Route::get('/adminrepus1car/fanlyc-staff', [FanlycStaffController::class, 'index'])->name('admin.fanlyc-staff');
    Route::post('/adminrepus1car/fanlyc-staff', [FanlycStaffController::class, 'store'])->name('admin.fanlyc-staff.store');
    Route::post('/adminrepus1car/fanlyc-staff/{staff}/estado', [FanlycStaffController::class, 'toggleStatus'])->name('admin.fanlyc-staff.toggle-status');
});

// ─── Module: fonda ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'module:fonda'])->group(function () {
    Route::get('/adminrepus1car/fonda-challenge', [AdminFondaChallengeController::class, 'index'])->name('admin.fonda-challenge');
    Route::post('/adminrepus1car/fonda-challenge/{registration}/status', [AdminFondaChallengeController::class, 'updateStatus'])->name('admin.fonda-challenge.status');
    Route::post('/adminrepus1car/fonda-challenge/{registration}/approve', [AdminFondaChallengeController::class, 'approve'])->name('admin.fonda-challenge.approve');
    Route::post('/adminrepus1car/fonda-challenge/{registration}/request-correction', [AdminFondaChallengeController::class, 'requestCorrection'])->name('admin.fonda-challenge.request-correction');
    Route::post('/adminrepus1car/fonda-challenge/{registration}/reject', [AdminFondaChallengeController::class, 'reject'])->name('admin.fonda-challenge.reject');
    Route::post('/adminrepus1car/fonda-challenge/{registration}/check-in', [AdminFondaChallengeController::class, 'checkIn'])->name('admin.fonda-challenge.check-in');
    Route::get('/adminrepus1car/fonda-challenge/ranking', [AdminFondaChallengeController::class, 'ranking'])->name('admin.fonda-challenge.ranking');
    Route::get('/adminrepus1car/fonda-challenge/{registration}/editar', [AdminFondaChallengeController::class, 'edit'])->name('admin.fonda-challenge.edit');
    Route::post('/adminrepus1car/fonda-challenge/{registration}/editar', [AdminFondaChallengeController::class, 'update'])->name('admin.fonda-challenge.update');
    Route::post('/adminrepus1car/fonda-jury/{registration}/assign', [FondaJuryController::class, 'assign'])->name('admin.fonda-jury.assign');
    Route::post('/adminrepus1car/fonda-media/{registration}', [FondaMediaController::class, 'create'])->name('admin.fonda-media.create');
    Route::post('/adminrepus1car/fonda-results/freeze', [FondaResultsController::class, 'freeze'])->name('admin.fonda-results.freeze');
    Route::post('/adminrepus1car/fonda-results/publish', [FondaResultsController::class, 'publish'])->name('admin.fonda-results.publish');
    Route::get('/adminrepus1car/jurados', [JurorController::class, 'index'])->name('admin.jurors');
    Route::post('/adminrepus1car/jurados', [JurorController::class, 'store'])->name('admin.jurors.store');
    Route::post('/adminrepus1car/jurados/{juror}/estado', [JurorController::class, 'toggleStatus'])->name('admin.jurors.toggle-status');
});

// ─── Module: entrepreneurs ────────────────────────────────────────────────────
Route::middleware(['auth', 'module:entrepreneurs'])->group(function () {
    Route::get('/adminrepus1car/emprendedores', [InvoiceBackofficeController::class, 'entrepreneurs'])->name('admin.entrepreneurs');
    Route::get('/adminrepus1car/emprendedores/exportar', [InvoiceBackofficeController::class, 'entrepreneursExport'])->name('admin.entrepreneurs.export');
    Route::get('/adminrepus1car/emprendedores/{user}', [InvoiceBackofficeController::class, 'entrepreneurEdit'])->name('admin.entrepreneurs.edit');
    Route::post('/adminrepus1car/emprendedores/{user}', [InvoiceBackofficeController::class, 'entrepreneurUpdate'])->name('admin.entrepreneurs.update');
    Route::post('/adminrepus1car/emprendedores/{user}/facturas', [InvoiceBackofficeController::class, 'entrepreneurInvoiceStore'])->name('admin.entrepreneurs.invoices.store');
});

// ─── Module: facturas ─────────────────────────────────────────────────────────
Route::middleware(['auth', 'module:facturas'])->group(function () {
    Route::get('/adminrepus1car/facturas', [InvoiceBackofficeController::class, 'invoices'])->name('admin.invoices');
    Route::get('/adminrepus1car/ganadores', [InvoiceBackofficeController::class, 'winners'])->name('admin.winners');
    Route::post('/adminrepus1car/ganadores/{invoice}', [InvoiceBackofficeController::class, 'selectWinner'])->name('admin.winners.select');
    Route::delete('/adminrepus1car/ganadores/{winner}', [InvoiceBackofficeController::class, 'removeWinner'])->name('admin.winners.remove');
});

// ─── Module: blacklist ────────────────────────────────────────────────────────
Route::middleware(['auth', 'module:blacklist'])->group(function () {
    Route::get('/adminrepus1car/blacklist', [BlacklistController::class, 'index'])->name('admin.blacklist');
    Route::post('/adminrepus1car/blacklist', [BlacklistController::class, 'store'])->name('admin.blacklist.store');
    Route::post('/adminrepus1car/blacklist/{entry}/quitar', [BlacklistController::class, 'destroy'])->name('admin.blacklist.remove');
});

// ─── Module: audit ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'module:audit'])->group(function () {
    Route::get('/adminrepus1car/auditoria', [InvoiceBackofficeController::class, 'audit'])->name('admin.audit');
});

// ─── Fanlyc staff · redemption terminals ─────────────────────────────────────
Route::middleware(['auth', 'role:admin,staff_fanlyc'])->group(function () {
    Route::get('/adminrepus1car/fanlyc-canje/{zoneCode}', [FanlycRedemptionController::class, 'index'])->name('admin.fanlyc.redeem');
    Route::post('/adminrepus1car/fanlyc-canje/{zoneCode}', [FanlycRedemptionController::class, 'lookup'])->name('admin.fanlyc.redeem.lookup');
    Route::post('/adminrepus1car/fanlyc-canje/{zoneCode}/buscar', [FanlycRedemptionController::class, 'findAjax'])->name('admin.fanlyc.redeem.find');
    Route::post('/adminrepus1car/fanlyc-canje/{zoneCode}/cupones/{coupon}', [FanlycRedemptionController::class, 'store'])->name('admin.fanlyc.redeem.store');
});

// ─── Jury · Fonda evaluation ──────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,jurado'])->group(function () {
    Route::get('/adminrepus1car/fonda-jury', [FondaJuryController::class, 'index'])->name('admin.fonda-jury');
    Route::post('/adminrepus1car/fonda-jury/evaluations/{assignment}', [FondaJuryController::class, 'evaluate'])->name('admin.fonda-jury.evaluate');
});

// ─── Frontend ─────────────────────────────────────────────────────────────────

// /nivea — OG tags propios para Empollerando a Mamá.
Route::get('/nivea', function () use ($frontendDist) {
    $indexPath = $frontendDist ? $frontendDist.DIRECTORY_SEPARATOR.'index.html' : null;
    abort_unless($indexPath && File::exists($indexPath), 404, 'Frontend no compilado. Ejecuta npm run build en frontend.');

    $rules = (array) (\App\Models\Campaign::query()->where('slug', 'nivea')->first()?->rules ?? []);
    $origin = request()->getSchemeAndHttpHost();
    $title = 'Empollerando a Mamá con NIVEA y Super Carnes';
    $description = 'Compra 1 producto NIVEA en Super Carnes, registra tu factura y cuéntanos por qué tu mamá merece ser empollerada. ¡10 ganadoras!';
    $image = $origin.($rules['hero_url'] ?? $rules['logo_url'] ?? '/logo_web.jpg');
    $url = $origin.'/nivea';
    $e = fn (string $value) => e($value);

    $html = File::get($indexPath);
    $html = preg_replace('/<title>.*?<\/title>/s', '<title>'.$e($title).'</title>', $html);
    $html = preg_replace('/<script type="application\/ld\+json" id="seo-jsonld">.*?<\/script>/s', '', $html);
    $replacements = [
        '/(<meta name="description" content=")[^"]*/' => $description,
        '/(<meta name="theme-color" content=")[^"]*/' => '#003087',
        '/(<meta property="og:title" content=")[^"]*/' => $title,
        '/(<meta property="og:description" content=")[^"]*/' => $description,
        '/(<meta property="og:url" content=")[^"]*/' => $url,
        '/(<meta property="og:image" content=")[^"]*/' => $image,
        '/(<meta name="twitter:title" content=")[^"]*/' => $title,
        '/(<meta name="twitter:description" content=")[^"]*/' => $description,
        '/(<meta name="twitter:image" content=")[^"]*/' => $image,
        '/(<link rel="canonical" href=")[^"]*/' => $url,
    ];
    foreach ($replacements as $pattern => $value) {
        $html = preg_replace_callback($pattern, fn ($m) => $m[1].$e($value), $html);
    }

    return response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Cache-Control' => 'no-cache',
    ]);
});

// /saba usa el mismo index.html, pero con titulo y vista previa social propios para
// que los enlaces compartidos por WhatsApp/Facebook no muestren los datos de Malta.
Route::get('/saba', function () use ($frontendDist) {
    $indexPath = $frontendDist ? $frontendDist.DIRECTORY_SEPARATOR.'index.html' : null;
    abort_unless($indexPath && File::exists($indexPath), 404, 'Frontend no compilado. Ejecuta npm run build en frontend.');

    $rules = (array) (\App\Models\Campaign::query()->where('slug', 'saba')->first()?->rules ?? []);
    $origin = request()->getSchemeAndHttpHost();
    $title = 'Emprende tu Sueño con Saba y Super Carnes';
    $description = 'Compra 1 producto Saba en Super Carnes, registra tu factura y cuéntanos por qué deberías ganar. ¡50 ganadoras!';
    $image = $origin.($rules['hero_url'] ?? $rules['logo_url'] ?? '/logo_web.jpg');
    $url = $origin.'/saba';
    $e = fn (string $value) => e($value);

    $html = File::get($indexPath);
    $html = preg_replace('/<title>.*?<\/title>/s', '<title>'.$e($title).'</title>', $html);
    $html = preg_replace('/<script type="application\/ld\+json" id="seo-jsonld">.*?<\/script>/s', '', $html);
    $replacements = [
        '/(<meta name="description" content=")[^"]*/' => $description,
        '/(<meta name="theme-color" content=")[^"]*/' => '#14041f',
        '/(<meta property="og:title" content=")[^"]*/' => $title,
        '/(<meta property="og:description" content=")[^"]*/' => $description,
        '/(<meta property="og:url" content=")[^"]*/' => $url,
        '/(<meta property="og:image" content=")[^"]*/' => $image,
        '/(<meta name="twitter:title" content=")[^"]*/' => $title,
        '/(<meta name="twitter:description" content=")[^"]*/' => $description,
        '/(<meta name="twitter:image" content=")[^"]*/' => $image,
        '/(<link rel="canonical" href=")[^"]*/' => $url,
    ];
    foreach ($replacements as $pattern => $value) {
        $html = preg_replace_callback($pattern, fn ($m) => $m[1].$e($value), $html);
    }

    return response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
        'Cache-Control' => 'no-cache',
    ]);
});

Route::get('/fonda-challenge', [FondaChallengeController::class, 'landing'])->name('fonda-challenge.landing');
Route::post('/fonda-challenge', [FondaChallengeController::class, 'store'])->name('fonda-challenge.store');
Route::get('/fonda-challenge/{code}', [FondaChallengeController::class, 'show'])->name('fonda-challenge.show');
Route::get('/fonda-challenge/{code}/qr', [FondaChallengeController::class, 'qr'])->name('fonda-challenge.qr');

Route::get('/fanlyc', [FanlycController::class, 'landing'])->name('fanlyc.landing');
Route::post('/fanlyc/registro', [FanlycController::class, 'store'])->name('fanlyc.store');
Route::get('/fanlyc/gracias', [FanlycController::class, 'thanks'])->name('fanlyc.thanks');
Route::get('/fanlyc/estado', [FanlycController::class, 'status'])->name('fanlyc.status');
Route::post('/fanlyc/estado', [FanlycController::class, 'searchStatus'])->name('fanlyc.status.search');
Route::get('/fanlyc/cupon/{code}/qr', [FanlycController::class, 'couponQr'])->name('fanlyc.coupon.qr');

Route::get('/{any?}', function () use ($frontendDist, $serveFrontendFile) {
    abort_unless($frontendDist, 404, 'Frontend no compilado. Ejecuta npm run build en frontend.');

    return $serveFrontendFile($frontendDist.DIRECTORY_SEPARATOR.'index.html');
})->where('any', '^(?!api|up|admin).*$');
