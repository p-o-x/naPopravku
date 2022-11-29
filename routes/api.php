<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FileserverController;
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
Route::post('/auth/register', [AuthController::class, 'createUser']);
Route::post('/auth/login', [AuthController::class, 'loginUser', ]);
Route::any('/not_authentificated', function (Request $request) {
    return response() -> json([
        'error' => 'not logged in'
    ], 401);
})->name('login');

Route::middleware('auth:sanctum')->get('/fileserver/{filepath?}', [FileserverController::class, 'getMethods'])
-> where('filepath', '.*');
Route::middleware('auth:sanctum')->delete('/fileserver/{filepath}', [FileserverController::class, 'deleteFileOrFolder'])
-> where('filepath', '.*');
Route::middleware('auth:sanctum')->put('/fileserver/{filepath}', [FileserverController::class, 'renameFile'])
-> where('filepath', '.*');
Route::middleware('auth:sanctum')->post('/fileserver/{filepath?}', [FileserverController::class, 'postMethods'])
-> where('filepath', '.*');
Route::middleware('auth:sanctum')->post('/generateurl/{filepath?}', [FileserverController::class,
                                                                                'generateUniqueURL'])
-> where('filepath', '.*');

Route::get('/storage/{filepath}', [FileserverController::class, 'downloadShortUrl']);
