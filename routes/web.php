<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laravel\Reverb\Loggers\Log;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn() => redirect('/admin'));

Route::get('/template/category-download', function () {
    return response()->download(public_path('import-templates/category-import-template.xlsx'));
})->name('template.category.download');

// Universal download route for attachments (force download, safe)
Route::get('/download/attachment/{filename}', function ($filename, Request $request) {
    // Prevent path traversal
    $filename = basename($filename);
    $path = storage_path('app/public/articles/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    $mime = \Illuminate\Support\Facades\File::mimeType($path) ?? 'application/octet-stream';
    return response()->download($path, $filename, [
        'Content-Type' => $mime,
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
})->where('filename', '.*')->name('attachment.download');
