<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvoiceBackofficeController;

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
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
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

Route::get('/favicon.svg', function () use ($frontendDist, $serveFrontendFile) {
    abort_unless($frontendDist, 404);

    return $serveFrontendFile($frontendDist.DIRECTORY_SEPARATOR.'favicon.svg');
});

Route::get('/icons.svg', function () use ($frontendDist, $serveFrontendFile) {
    abort_unless($frontendDist, 404);

    return $serveFrontendFile($frontendDist.DIRECTORY_SEPARATOR.'icons.svg');
});

Route::get('/adminrepus1car', [InvoiceBackofficeController::class, 'index'])->name('admin.invoice-backoffice');
Route::post('/adminrepus1car', [InvoiceBackofficeController::class, 'update'])->name('admin.invoice-backoffice.update');

Route::get('/media/{path}', function (string $path) {
    $baseDirectory = realpath(storage_path('app/public'));
    abort_unless($baseDirectory, 404);

    $resolvedPath = realpath($baseDirectory.DIRECTORY_SEPARATOR.$path);
    abort_unless($resolvedPath && str_starts_with($resolvedPath, $baseDirectory.DIRECTORY_SEPARATOR), 404);
    abort_unless(File::exists($resolvedPath), 404);

    $extension = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
    ];

    return response(File::get($resolvedPath), 200, [
        'Content-Type' => $mimeTypes[$extension] ?? 'application/octet-stream',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*');

Route::get('/{any?}', function () use ($frontendDist, $serveFrontendFile) {
    abort_unless($frontendDist, 404, 'Frontend no compilado. Ejecuta npm run build en frontend.');

    return $serveFrontendFile($frontendDist.DIRECTORY_SEPARATOR.'index.html');
})->where('any', '^(?!api|up|admin|adminrepus1car).*$');
