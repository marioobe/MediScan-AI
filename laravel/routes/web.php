<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\AdminLoginController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminTrainingController;
use App\Http\Controllers\AdminModelController;
use App\Http\Controllers\AdminPredictionController;

Route::get('/', [LandingController::class, 'index']);

Route::get('/tes', [PredictionController::class, 'index'])->name('predict');
Route::post('/tes/predict', [PredictionController::class, 'predict'])->name('predict.submit');

Route::post('/api/training/webhook', [AdminTrainingController::class, 'webhook']);

Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminLoginController::class, 'login']);
    Route::post('/logout', [AdminLoginController::class, 'logout'])->name('admin.logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/models', [AdminModelController::class, 'index'])->name('admin.models');
        Route::post('/models/register', [AdminModelController::class, 'store'])->name('admin.models.store');
        Route::post('/models/{modelId}/activate', [AdminModelController::class, 'activate'])->name('admin.models.activate');
        Route::post('/models/{modelId}/update', [AdminModelController::class, 'update'])->name('admin.models.update');
        Route::delete('/models/{modelId}', [AdminModelController::class, 'destroy'])->name('admin.models.destroy');
        Route::get('/training', [AdminTrainingController::class, 'index'])->name('admin.training');
        Route::post('/training', [AdminTrainingController::class, 'upload']);
        Route::post('/training/{jobId}/cancel', [AdminTrainingController::class, 'cancel'])->name('admin.training.cancel');
        Route::post('/training/{jobId}/mark-completed', [AdminTrainingController::class, 'markCompleted']);
        Route::get('/training/{jobId}', [AdminTrainingController::class, 'show'])->name('admin.training.progress');
        Route::get('/predictions', [AdminPredictionController::class, 'index'])->name('admin.predictions');
        Route::get('/predictions/{id}', [AdminPredictionController::class, 'show'])->name('admin.predictions.show');
        Route::delete('/predictions/{id}', [AdminPredictionController::class, 'destroy'])->name('admin.predictions.destroy');
    });
});
