<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiAuthController;

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


//AUTH ROUTES
Route::post("login", [ApiAuthController::class, "login"])->name("login");
Route::post("register", [ApiAuthController::class, "register"])->name("register");
Route::get("me", [ApiAuthController::class, "me"])->name("me");

//TYPING PATTERN ROUTES
// Route::post("get-typing-pattern-data", [ApiAuthController::class, "getTypingPatternData"])->name("get-typing-pattern-data");
Route::post("typing-pattern-data", [ApiAuthController::class, "getTypingPatternDataPremium"])->name("typing-pattern-data");
Route::post("delete-user-typing-patterns", [ApiAuthController::class, "deleteUserTypingPatterns"])->name("delete-user-typing-patterns");
