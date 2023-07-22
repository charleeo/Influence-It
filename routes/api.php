<?php

use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(["prefix" => "posts"], function () {
    Route::post("/create", [PostController::class, "saveMultiplePosts"]);
    Route::post("/get", [PostController::class, "getPosts"]);
    Route::delete("/get/{id}", [PostController::class, "deletePost"]);
    Route::get("/get/{id}", [PostController::class, "getPost"]);
});
