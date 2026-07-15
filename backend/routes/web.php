<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\FondaChallengeController as AdminFondaChallengeController;
use App\Http\Controllers\Admin\FondaJuryController;
use App\Http\Controllers\Admin\FondaMediaController;
use App\Http\Controllers\Admin\FondaResultsController;
use App\Http\Controllers\Admin\InvoiceBackofficeController;
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

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/adminrepus1car/dashboard', [InvoiceBackofficeController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/adminrepus1car', [InvoiceBackofficeController::class, 'index'])->name('admin.invoice-backoffice');
    Route::post('/adminrepus1car', [InvoiceBackofficeController::class, 'update'])->name('admin.invoice-backoffice.update');
    Route::post('/adminrepus1car/promociones', [InvoiceBackofficeController::class, 'storeCampaign'])->name('admin.invoice-backoffice.campaigns.store');
    Route::post('/adminrepus1car/promociones/{campaign}/estado', [InvoiceBackofficeController::class, 'toggleCampaignStatus'])->name('admin.invoice-backoffice.campaigns.toggle-status');
    Route::get('/adminrepus1car/facturas', [InvoiceBackofficeController::class, 'invoices'])->name('admin.invoices');
    Route::get('/adminrepus1car/ganadores', [InvoiceBackofficeController::class, 'winners'])->name('admin.winners');
    Route::get('/adminrepus1car/auditoria', [InvoiceBackofficeController::class, 'audit'])->name('admin.audit');
    Route::get('/adminrepus1car/media/{path}', [InvoiceBackofficeController::class, 'media'])->where('path', '.*')->name('admin.media');
    Route::post('/adminrepus1car/ganadores/{invoice}', [InvoiceBackofficeController::class, 'selectWinner'])->name('admin.winners.select');
    Route::delete('/adminrepus1car/ganadores/{winner}', [InvoiceBackofficeController::class, 'removeWinner'])->name('admin.winners.remove');
    Route::get('/adminrepus1car/clientes/{user}', [InvoiceBackofficeController::class, 'customerHistory'])->name('admin.customers.history');
    Route::post('/adminrepus1car/clientes/{user}/ganador', [InvoiceBackofficeController::class, 'markCustomerAsWinner'])->name('admin.customers.mark-winner');
    Route::delete('/adminrepus1car/clientes/{user}/ganador', [InvoiceBackofficeController::class, 'unmarkCustomerAsWinner'])->name('admin.customers.unmark-winner');
    Route::get('/adminrepus1car/emprendedores', [InvoiceBackofficeController::class, 'entrepreneurs'])->name('admin.entrepreneurs');
    Route::get('/adminrepus1car/emprendedores/{user}', [InvoiceBackofficeController::class, 'entrepreneurEdit'])->name('admin.entrepreneurs.edit');
    Route::post('/adminrepus1car/emprendedores/{user}', [InvoiceBackofficeController::class, 'entrepreneurUpdate'])->name('admin.entrepreneurs.update');
    Route::post('/adminrepus1car/emprendedores/{user}/facturas', [InvoiceBackofficeController::class, 'entrepreneurInvoiceStore'])->name('admin.entrepreneurs.invoices.store');
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
    Route::post('/adminrepus1car/fonda-challenge/{registration}/check-in', [AdminFondaChallengeController::class, 'checkIn'])->name('admin.fonda-challenge.check-in');
    Route::get('/adminrepus1car/fonda-challenge/ranking', [AdminFondaChallengeController::class, 'ranking'])->name('admin.fonda-challenge.ranking');
    Route::get('/adminrepus1car/fonda-jury', [FondaJuryController::class, 'index'])->name('admin.fonda-jury');
    Route::post('/adminrepus1car/fonda-jury/{registration}/assign', [FondaJuryController::class, 'assign'])->name('admin.fonda-jury.assign');
    Route::post('/adminrepus1car/fonda-jury/evaluations/{assignment}', [FondaJuryController::class, 'evaluate'])->name('admin.fonda-jury.evaluate');
    Route::post('/adminrepus1car/fonda-media/{registration}', [FondaMediaController::class, 'create'])->name('admin.fonda-media.create');
    Route::post('/adminrepus1car/fonda-results/freeze', [FondaResultsController::class, 'freeze'])->name('admin.fonda-results.freeze');
    Route::post('/adminrepus1car/fonda-results/publish', [FondaResultsController::class, 'publish'])->name('admin.fonda-results.publish');
});

Route::get('/{any?}', function () use ($frontendDist, $serveFrontendFile) {
    abort_unless($frontendDist, 404, 'Frontend no compilado. Ejecuta npm run build en frontend.');

    return $serveFrontendFile($frontendDist.DIRECTORY_SEPARATOR.'index.html');
})->where('any', '^(?!api|up|admin).*$');
