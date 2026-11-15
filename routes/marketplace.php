<?php

use App\Http\Controllers\Marketplace\AuthController;
use App\Http\Controllers\Marketplace\CoreController;
use App\Http\Controllers\Marketplace\InteractionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/gm', '/gm/main.html');
Route::prefix('gm')->name('gm.')->group(function () {
    foreach (['main' => 'home', 'marketplace' => 'browse', 'item' => 'show', 'bidding' => 'bidding', 'looking-for' => 'looking-for', 'scam-watch' => 'scam-watch', 'profile' => 'profile', 'meetup-map' => 'meetup-map', 'login' => 'login', 'logout' => 'logout'] as $url => $view) {
        Route::view($url.'.html', 'marketplace.'.$view)->name($view);
    }
    Route::view('admin-login.html', 'marketplace.admin.login')->name('admin.login');
    Route::middleware('auth')->group(function () {
        foreach (['list-item' => 'create', 'my-listings' => 'mine', 'wishlist' => 'wishlist', 'settings' => 'settings', 'messages' => 'messages', 'notifications' => 'notifications'] as $url => $view) {
            Route::view($url.'.html', 'marketplace.'.$view)->name($view);
        }
        Route::view('admin.html', 'marketplace.admin.index')->middleware('gm.admin')->name('admin');
    });
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('state', [AuthController::class, 'state'])->name('state')->middleware('throttle:120,1');
        Route::post('login', [AuthController::class, 'login'])->name('login')->middleware('throttle:login');
        Route::post('register', [AuthController::class, 'register'])->name('register')->middleware('throttle:5,1');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::middleware(['auth', 'throttle:120,1'])->group(function () {
            Route::post('profile', [AuthController::class, 'profile'])->name('profile');
            Route::post('password', [AuthController::class, 'password'])->name('password')->middleware('throttle:6,1');
            foreach (['createListing', 'updateListing', 'deleteListing', 'addView', 'createRequest', 'offerOnRequest', 'withdrawRequestOffer', 'setRequestFound', 'deleteRequest', 'createReport', 'toggleSeen'] as $action) {
                Route::post('action/'.$action, [CoreController::class, $action])->name('action.'.$action);
            }
            foreach (['moderate', 'setReportStatus', 'setReportRisk', 'setUserStatus'] as $action) {
                Route::post('action/'.$action, [CoreController::class, $action])->name('action.'.$action)->middleware('gm.admin');
            }
            foreach (['placeBid', 'buyNow', 'toggleSaved', 'openConversation', 'sendMessage', 'markConversationRead', 'respondMeetup', 'markNotification', 'markAllNotifications', 'deleteNotification'] as $action) {
                Route::post('action/'.$action, [InteractionController::class, $action])->name('action.'.$action);
            }
            Route::get('reports/evidence/{evidence}', [CoreController::class, 'evidence'])->name('report.evidence')->whereNumber('evidence');
            Route::get('messages/{message}/image', [InteractionController::class, 'messageImage'])->name('message.image')->whereNumber('message');
        });
    });
});
