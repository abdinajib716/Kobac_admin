<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::view('/privacy-policy', 'privacy-policy')->name('privacy-policy');
Route::view('/account-deletion', 'account-deletion')->name('account-deletion');
