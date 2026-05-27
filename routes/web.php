<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    // Module pages
    Route::get('/', fn () => to_route('admin.users.index'))->name('index');
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
    Route::get('/plans', [AdminController::class, 'plansIndex'])->name('plans.index');
    Route::get('/stories', [AdminController::class, 'storiesIndex'])->name('stories.index');
    Route::get('/billing', [AdminController::class, 'billingIndex'])->name('billing.index');

    // User actions
    Route::patch('/users/{user}', [AdminController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/plan', [AdminController::class, 'assignPlan'])->name('users.assign-plan');
    Route::patch('/users/{user}/subscription', [AdminController::class, 'updateSubscription'])->name('users.subscription');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');

    // Plan actions
    Route::post('/plans', [AdminController::class, 'storePlan'])->name('plans.store');
    Route::patch('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
});

require __DIR__.'/auth.php';
