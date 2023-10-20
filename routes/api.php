<?php

use App\Http\Controllers\MessageController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/send-message', [MessageController::class, 'sendMessages']);
Route::get('/whatsapp-webhook', [MessageController::class, 'verifyWebhook']);
Route::post('/whatsapp-webhook', [MessageController::class, 'processWebhook']);
Route::apiResources([
    'messages' => MessageController::class,
]);
Route::get('/message-templates', [MessageController::class, 'loadMessageTemplates']);
Route::post('/send-message-templates', [MessageController::class, 'sendMessageTemplate']);
