<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PetsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/pets',[PetsController::class,'list'])->name('pets.list');
    Route::post('/pets',[PetsController::class,'create'])->name('pets.create');
    Route::patch('/pets/{id}',[PetsController::class,'update'])->where('id', '[0-9]+')->name('pets.update');
    Route::delete('/pets/{id}',[PetsController::class,'delete'])->where('id', '[0-9]+')->name('pets.delete');
    Route::get('/pets/{id}',[PetsController::class,'details'])->where('id', '[0-9]+')->name('pets.details');
    Route::get('/pets/add',[PetsController::class,'empty'])->where('id', '[0-9]+')->name('pets.empty');
});


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
