<?php

use App\Http\Controllers\Author24\Author24Controller;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth.api.token')->prefix('crm')->name('crm.')->group(function () {
    Route::get('dreamkas/add/', 'App\Http\Controllers\Crm\Dreamkas@add')
        ->name('cancel.count');
    Route::post('emails/send/', 'App\Http\Controllers\Crm\Email@send')
        ->name('emails.send');
    Route::get('queue/size/', 'App\Http\Controllers\Crm\Queue@size')
        ->name('queue.size');
    //Queue restart failed job
    Route::get('queue/retry/{id}', 'App\Http\Controllers\Crm\Queue@retry')
        ->name('queue.retry');
    // Antiplagiat checker
    Route::group(['name' => 'antiplagiat.', 'prefix' => 'antiplagiat'], function () {
        Route::post('send', '\App\Http\Controllers\Crm\AntiplagiatController@send');
    });


    Route::controller(Author24Controller::class)->name('author24.')->prefix('author24')
        ->group(function () {
            Route::get('dictionary', 'getDictionary');
            Route::get('auctionOrders', 'getNewOrdersWithOutOffer');
            Route::get('performerOrders', 'getPerformerOrders');
            Route::get('getDialog/{id}', 'getDialog');
            Route::get('getOrder/{id}', 'getOrder');
            Route::post('send', 'sendMessage');
            Route::post('sendFile', 'sendMessageWithFile');
            Route::post('accept', 'acceptWork');
            Route::post('reject', 'rejectWork');
            Route::post('setNewPrice', 'setNewPrice');
        });


    Route::group(['name' => 'order.', 'prefix' => 'order'], function () {
        Route::post('list', 'App\Http\Controllers\ClientLk\OrderController@orderList')
            ->name('list');
        Route::post('info', 'App\Http\Controllers\ClientLk\OrderController@index')
            ->name('info');
        Route::post('price', 'App\Http\Controllers\ClientLk\OrderController@changeOrderPrice')
            ->name('price');
        Route::post('bonuses', 'App\Http\Controllers\ClientLk\OrderController@orderBonusesPayment')
            ->name('bonuses');
        Route::post('pay', 'App\Http\Controllers\ClientLk\OrderController@orderPayment')
            ->name('pay');
        Route::post('cashback', 'App\Http\Controllers\ClientLk\OrderController@checkUserCashBack')
            ->name('cashback');
        Route::post('setCashback', 'App\Http\Controllers\ClientLk\OrderController@setUserCashBack')
            ->name('setCashback');
        Route::post('returnCashback', 'App\Http\Controllers\ClientLk\OrderController@returnCashBack')
            ->name('returnCashback');
        Route::post('checkCashBackSize', 'App\Http\Controllers\ClientLk\OrderController@checkOrderCashBackSize')
            ->name('checkCashBackSize');
    });
    Route::group(['name' => 'user.', 'prefix' => 'user'], function () {
        Route::post('saleScale', 'App\Http\Controllers\ClientLk\OrderController@getSaleScale')
            ->name('saleScale');
    });
});
