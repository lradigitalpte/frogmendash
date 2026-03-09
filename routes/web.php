<?php

use Illuminate\Support\Facades\Route;

// Redirect legacy /login to admin panel login (avoids redirect loops; register once)
Route::redirect('/login', '/admin/login')->name('login');
