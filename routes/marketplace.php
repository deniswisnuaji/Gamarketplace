<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gamarketplace Blade pages (frontend)
|--------------------------------------------------------------------------
| Preview routes for the converted HTML designs, under /gm/.
| The URLs keep the ".html" names on purpose: the page JavaScript links to
| e.g. "item.html?id=…" and "login.html", and those relative links keep
| working without rewriting every script.
|
| Data is still saved in the browser by public/marketplace/js/store.js.
| These routes do NOT use Fortify or the database yet. The backend team
| will replace them with controllers + real auth (see the PR description).
*/

Route::redirect('/gm', '/gm/main.html');

Route::prefix('gm')->name('gm.')->group(function () {
    // Student pages
    Route::view('main.html', 'marketplace.home')->name('home');
    Route::view('marketplace.html', 'marketplace.browse')->name('browse');
    Route::view('item.html', 'marketplace.show')->name('show');
    Route::view('bidding.html', 'marketplace.bidding')->name('bidding');
    Route::view('looking-for.html', 'marketplace.looking-for')->name('looking-for');
    Route::view('scam-watch.html', 'marketplace.scam-watch')->name('scam-watch');
    Route::view('list-item.html', 'marketplace.create')->name('create');
    Route::view('profile.html', 'marketplace.profile')->name('profile');
    Route::view('my-listings.html', 'marketplace.mine')->name('mine');
    Route::view('wishlist.html', 'marketplace.wishlist')->name('wishlist');
    Route::view('settings.html', 'marketplace.settings')->name('settings');
    Route::view('messages.html', 'marketplace.messages')->name('messages');
    Route::view('notifications.html', 'marketplace.notifications')->name('notifications');
    Route::view('meetup-map.html', 'marketplace.meetup-map')->name('meetup-map');

    // Sign in / out (design preview — not Fortify yet)
    Route::view('login.html', 'marketplace.login')->name('login');
    Route::view('logout.html', 'marketplace.logout')->name('logout');

    // Admin (the real admin check must be added by the backend: auth + admin role middleware)
    Route::view('admin-login.html', 'marketplace.admin.login')->name('admin.login');
    Route::view('admin.html', 'marketplace.admin.index')->name('admin');
});
