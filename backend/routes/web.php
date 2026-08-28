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

    return response(File::get($filePath), 200, [
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

Route::get('/admin/login', [AdminLoginController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

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

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/adminrepus1car/dashboard', [InvoiceBackofficeController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/adminrepus1car', [InvoiceBackofficeController::class, 'index'])->name('admin.invoice-backoffice');
    Route::post('/adminrepus1car', [InvoiceBackofficeController::class, 'update'])->name('admin.invoice-backoffice.update');
    Route::post('/adminrepus1car/promociones', [InvoiceBackofficeController::class, 'storeCampaign'])->name('admin.invoice-backoffice.campaigns.store');
    Route::get('/adminrepus1car/promociones/{campaign}/editar', [InvoiceBackofficeController::class, 'editCampaign'])->name('admin.invoice-backoffice.campaigns.edit');
    Route::post('/adminrepus1car/promociones/{campaign}/estado', [InvoiceBackofficeController::class, 'toggleCampaignStatus'])->name('admin.invoice-backoffice.campaigns.toggle-status');
    Route::get('/adminrepus1car/facturas', [InvoiceBackofficeController::class, 'invoices'])->name('admin.invoices');
    Route::get('/adminrepus1car/ganadores', [InvoiceBackofficeController::class, 'winners'])->name('admin.winners');
    Route::get('/adminrepus1car/promociones/{campaign}/ranking', [InvoiceBackofficeController::class, 'productRanking'])->name('admin.campaigns.product-ranking');
    Route::post('/adminrepus1car/promociones/{campaign}/ranking/freeze', [InvoiceBackofficeController::class, 'freezeProductRanking'])->name('admin.campaigns.product-ranking.freeze');
    Route::get('/adminrepus1car/promociones/{campaign}/operacion', [InvoiceBackofficeController::class, 'productRankingOperations'])->name('admin.campaigns.product-ranking.operations');
    Route::post('/adminrepus1car/promociones/{campaign}/facturas-manuales', [InvoiceBackofficeController::class, 'storeManualProductInvoice'])->name('admin.campaigns.product-ranking.manual-invoice');
    Route::post('/adminrepus1car/promociones/{campaign}/ganadores/{winner}/reemplazar', [InvoiceBackofficeController::class, 'replaceProductRankingWinner'])->name('admin.campaigns.product-ranking.replace-winner');
    Route::post('/adminrepus1car/promociones/{campaign}/fraude/{flag}/resolver', [InvoiceBackofficeController::class, 'resolveProductRankingFraudFlag'])->name('admin.campaigns.product-ranking.resolve-fraud');
    Route::get('/adminrepus1car/auditoria', [InvoiceBackofficeController::class, 'audit'])->name('admin.audit');
    Route::get('/adminrepus1car/media/{path}', [InvoiceBackofficeController::class, 'media'])->where('path', '.*')->name('admin.media');
    Route::post('/adminrepus1car/ganadores/{invoice}', [InvoiceBackofficeController::class, 'selectWinner'])->name('admin.winners.select');
    Route::delete('/adminrepus1car/ganadores/{winner}', [InvoiceBackofficeController::class, 'removeWinner'])->name('admin.winners.remove');
    Route::get('/adminrepus1car/clientes/{user}', [InvoiceBackofficeController::class, 'customerHistory'])->name('admin.customers.history');
    Route::post('/adminrepus1car/clientes/{user}/ganador', [InvoiceBackofficeController::class, 'markCustomerAsWinner'])->name('admin.customers.mark-winner');
    Route::delete('/adminrepus1car/clientes/{user}/ganador', [InvoiceBackofficeController::class, 'unmarkCustomerAsWinner'])->name('admin.customers.unmark-winner');
    Route::get('/adminrepus1car/emprendedores', [InvoiceBackofficeController::class, 'entrepreneurs'])->name('admin.entrepreneurs');
    Route::get('/adminrepus1car/emprendedores/exportar', [InvoiceBackofficeController::class, 'entrepreneursExport'])->name('admin.entrepreneurs.export');
    Route::get('/adminrepus1car/emprendedores/{user}', [InvoiceBackofficeController::class, 'entrepreneurEdit'])->name('admin.entrepreneurs.edit');
    Route::post('/adminrepus1car/emprendedores/{user}', [InvoiceBackofficeController::class, 'entrepreneurUpdate'])->name('admin.entrepreneurs.update');
    Route::post('/adminrepus1car/emprendedores/{user}/facturas', [InvoiceBackofficeController::class, 'entrepreneurInvoiceStore'])->name('admin.entrepreneurs.invoices.store');
    Route::get('/adminrepus1car/blacklist', [BlacklistController::class, 'index'])->name('admin.blacklist');
    Route::post('/adminrepus1car/blacklist', [BlacklistController::class, 'store'])->name('admin.blacklist.store');
    Route::post('/adminrepus1car/blacklist/{entry}/quitar', [BlacklistController::class, 'destroy'])->name('admin.blacklist.remove');
});

Route::middleware(['auth', 'role:admin,supervisor,manager'])->group(function () {
    Route::get('/adminrepus1car/entrega-premio', [InvoiceBackofficeController::class, 'prizeDeliveryIndex'])->name('admin.prize-delivery');
    Route::post('/adminrepus1car/entrega-premio', [InvoiceBackofficeController::class, 'prizeDeliveryLookup'])->name('admin.prize-delivery.lookup');
    Route::post('/adminrepus1car/entrega-premio/lookup', [InvoiceBackofficeController::class, 'prizeDeliveryFind'])->name('admin.prize-delivery.find');
    Route::post('/adminrepus1car/entrega-premio/{winner}', [InvoiceBackofficeController::class, 'prizeDeliveryStore'])->name('admin.prize-delivery.store');
    Route::get('/adminrepus1car/media/{path}', [InvoiceBackofficeController::class, 'media'])->where('path', '.*')->name('admin.media');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/adminrepus1car/entrega-premio/{winner}/reabrir', [InvoiceBackofficeController::class, 'prizeDeliveryOverride'])->name('admin.prize-delivery.override');
    Route::post('/adminrepus1car/campaigns', [InvoiceBackofficeController::class, 'updateCampaigns'])->name('admin.invoice-backoffice.campaigns.update');
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

Route::middleware(['auth', 'role:admin,staff_fanlyc'])->group(function () {
    Route::get('/adminrepus1car/fanlyc-canje/{zoneCode}', [FanlycRedemptionController::class, 'index'])->name('admin.fanlyc.redeem');
    Route::post('/adminrepus1car/fanlyc-canje/{zoneCode}', [FanlycRedemptionController::class, 'lookup'])->name('admin.fanlyc.redeem.lookup');
    Route::post('/adminrepus1car/fanlyc-canje/{zoneCode}/buscar', [FanlycRedemptionController::class, 'findAjax'])->name('admin.fanlyc.redeem.find');
    Route::post('/adminrepus1car/fanlyc-canje/{zoneCode}/cupones/{coupon}', [FanlycRedemptionController::class, 'store'])->name('admin.fanlyc.redeem.store');
});

Route::middleware(['auth', 'role:admin,jurado'])->group(function () {
    Route::get('/adminrepus1car/fonda-jury', [FondaJuryController::class, 'index'])->name('admin.fonda-jury');
    Route::post('/adminrepus1car/fonda-jury/evaluations/{assignment}', [FondaJuryController::class, 'evaluate'])->name('admin.fonda-jury.evaluate');
});

Route::get('/{any?}', function () use ($frontendDist, $serveFrontendFile) {
    abort_unless($frontendDist, 404, 'Frontend no compilado. Ejecuta npm run build en frontend.');

    return $serveFrontendFile($frontendDist.DIRECTORY_SEPARATOR.'index.html');
})->where('any', '^(?!api|up|admin).*$');
