<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardUserController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

//Public routes-------------------------------------------------------------
Route::get('ping', static function () {
    return "pong";
});
//AUTH
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
//--------------------------------------------------------------------------

//Protected routes----------------------------------------------------------
Route::group(['middleware' => ['auth:sanctum']], static function () {
    Route::get('authenticated', static function () {
        return response(['message' => "Authenticated"], 200);
    });
    //AUTH
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/user', [AuthController::class, 'getUser']);
    //USER
    Route::get('users', [UserController::class, 'users']);
    //BOARD
    Route::post('board/store', [BoardController::class, 'store']);
    Route::get('board/get', [BoardController::class, 'get']);
    Route::put('board/edit/{id}', [BoardController::class, 'edit']);
    Route::delete('board/delete/{id}', [BoardController::class, 'delete']);
    Route::get('board/updated', [BoardController::class, 'updated']);
    Route::get('board/access/{id}', [BoardController::class, 'getAccessLevel']);
    //BOARD/USERS
    Route::post('board/{id}/users/add', [BoardController::class, 'addUser']);
    Route::delete('board/{id}/users/delete/{user_id}', [BoardController::class, 'deleteUser']);
    Route::get('board/{id}/users/get', [BoardController::class, 'getUsers']);
    Route::put('board/{id}/users/edit/{user_id}', [BoardController::class, 'updateUser']);

    //COLUMN
    Route::post('column/store', [ColumnController::class, 'store']);
    Route::put('column/edit/{id}', [ColumnController::class, 'edit']);
    Route::delete('column/delete/{id}', [ColumnController::class, 'delete']);

    //CARD
    Route::post('card/store', [CardController::class, 'store']);
    Route::put('card/edit/{id}', [CardController::class, 'edit']);
    Route::put('card/move/{id}/{to}', [CardController::class, 'move']);
    Route::delete('card/delete/{id}', [CardController::class, 'delete']);

});
//--------------------------------------------------------------------------
