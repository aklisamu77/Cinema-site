<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::get('/about', function () {
    return view('pages.about');
});
  

Route::get('/help', function () {
    return view('pages.help');
});
Route::get('/contact', function () {
    return view('pages.contact');
});
Route::get('/terms', function () {
    return view('pages.terms');
});
Route::get('/privacy', function () {
    return view('pages.privacy');
});
Route::get('/faq', function () {
    return view('pages.faq');
});
Route::get('/blog', function () {
    return view('pages.blog');
});
Route::get('/blog/{uuid}', function ($uuid) {
    return view('pages.blog_post', ['uuid' => $id]);
});



Route::get('/dashboard', function () {
    return "Welcome, you are logged in ✅";
})->middleware('auth');
