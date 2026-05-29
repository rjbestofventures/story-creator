<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoryController;
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
    return to_route('stories.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// Billing — auth + verified, no subscription check
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/billing/plans',     [BillingController::class, 'plans'])->name('billing.plans');
    Route::post('/billing/free',     [BillingController::class, 'selectFree'])->name('billing.free');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success',   [BillingController::class, 'success'])->name('billing.success');
});

// Stripe webhook — no auth, no CSRF
Route::post('/stripe/webhook', [BillingController::class, 'webhook'])->name('stripe.webhook');

// Stories — require active subscription
Route::middleware(['auth', 'verified', 'requires.subscription'])->group(function () {
    Route::get('/stories',                          [StoryController::class, 'index'])->name('stories.index');
    Route::get('/stories/create',                   [StoryController::class, 'create'])->name('stories.create');
    Route::post('/stories',                         [StoryController::class, 'store'])->name('stories.store');
    Route::post('/stories/interview',               [StoryController::class, 'interview'])->name('stories.interview');
    Route::get('/stories/{story}',                  [StoryController::class, 'show'])->name('stories.show');
    Route::delete('/stories/{story}',               [StoryController::class, 'destroy'])->name('stories.destroy');
    Route::post('/stories/{story}/regenerate',      [StoryController::class, 'regenerateEpisode'])->name('stories.regenerate');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {

    // Module pages
    Route::get('/', fn () => to_route('admin.users.index'))->name('index');
    Route::get('/users', [AdminController::class, 'usersIndex'])->name('users.index');
    Route::get('/plans', [AdminController::class, 'plansIndex'])->name('plans.index');
    Route::get('/stories', [AdminController::class, 'storiesIndex'])->name('stories.index');
    Route::get('/billing', [AdminController::class, 'billingIndex'])->name('billing.index');

    // User actions
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [AdminController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/profile', [AdminController::class, 'updateProfile'])->name('users.profile');
    Route::post('/users/{user}/password', [AdminController::class, 'resetPassword'])->name('users.password');
    Route::post('/users/{user}/plan', [AdminController::class, 'assignPlan'])->name('users.assign-plan');
    Route::patch('/users/{user}/subscription', [AdminController::class, 'updateSubscription'])->name('users.subscription');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');

    // Plan actions
    Route::post('/plans', [AdminController::class, 'storePlan'])->name('plans.store');
    Route::patch('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [AdminController::class, 'destroyPlan'])->name('plans.destroy');
});

require __DIR__.'/auth.php';
