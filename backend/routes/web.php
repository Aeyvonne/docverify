<?php

use Illuminate\Support\Facades\Route;
use App\Services\QRCodeService;
use App\Services\PDFService;

Route::get('/test-certify', function () {
    $token     = (new QRCodeService())->generateToken();
    $verifyUrl = config('app.url') . '/verify/' . $token;
    $qrPng     = (new QRCodeService())->renderQrPng($verifyUrl);
    $path      = (new PDFService())->certifyPdf(
        storage_path('app/test-samples/sample.pdf'),
        $qrPng
    );
    return response()->file($path);
});

Route::get('/test-qr', function () {
    $svg = (new QRCodeService())->renderQrSvg('Bonjour DocVerify');
    return response($svg)->header('Content-Type', 'image/svg+xml');
});

Route::get('/', function () {
    return view('welcome');
});