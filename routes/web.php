<?php

use Illuminate\Support\Facades\Route;

// Redirect legacy /login to admin panel login (avoids redirect loops; register once)
Route::redirect('/login', '/admin/login')->name('login');

if (! (bool) config('features.website.enabled')) {
	Route::redirect('/', '/admin/login')->name('home');
	Route::redirect('/register', '/admin/login')->name('register');
	Route::redirect('/home', '/admin/login');
	Route::redirect('/contact', '/admin/login');
	Route::redirect('/blog', '/admin/login');
}
