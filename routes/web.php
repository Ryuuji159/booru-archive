<?php

use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PostMediaController;
use App\Http\Controllers\PostShowController;
use Illuminate\Support\Facades\Route;

Route::get('/', GalleryController::class)->name('home');
Route::get('/posts/{post}', PostShowController::class)->name('posts.show');
Route::get('/media/posts/{post}/preview', [PostMediaController::class, 'preview'])->name('posts.media.preview');
Route::get('/media/posts/{post}/full', [PostMediaController::class, 'full'])->name('posts.media.full');
