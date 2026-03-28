<?php

use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PostMediaController;
use App\Http\Controllers\PostShowController;
use App\Livewire\Admin\FirstAdminSetup;
use Illuminate\Support\Facades\Route;

Route::get('/', GalleryController::class)->name('home');
Route::get('/posts/{post:md5}', PostShowController::class)->name('posts.show');
Route::get('/media/posts/preview/{post:md5}', [PostMediaController::class, 'preview'])->name('posts.media.preview');
Route::get('/media/posts/full/{post:md5}', [PostMediaController::class, 'full'])->name('posts.media.full');

Route::prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/setup', FirstAdminSetup::class)->name('setup');
    });
