<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\LandingLockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StoryController;
use App\Http\Middleware\CheckLandingLock;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->middleware(CheckLandingLock::class)->name('welcome');

Route::get('/terms', fn () => Inertia::render('Terms'))->name('terms');
Route::get('/privacy', fn () => Inertia::render('Privacy'))->name('privacy');

Route::get('/verified-partner', function () {
    return Inertia::render('VerifiedPartner', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('partner');

Route::get('/unlock', [LandingLockController::class, 'show'])->name('landing.unlock');
Route::post('/unlock', [LandingLockController::class, 'unlock'])->name('landing.unlock.submit');

Route::get('/email/verified-success', function () {
    return Inertia::render('Auth/EmailVerified');
})->middleware('auth')->name('verification.success');

Route::get('/demo', function (Request $request) {
    if (! $request->user()) {
        return redirect()->route('register');
    }

    $story = $request->user()->stories()
        ->where('is_demo', true)
        ->whereIn('status', ['interviewing', 'interview_complete'])
        ->first();

    return $story
        ? redirect()->route('stories.resume', $story->id)
        : redirect()->route('stories.index');
})->name('demo');

Route::get('/dashboard', function () {
    return to_route('stories.index');
})->middleware(['auth'])->name('dashboard');

// Billing — auth + verified
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/billing/plans', [BillingController::class, 'plans'])->name('billing.plans');
    Route::post('/billing/free', [BillingController::class, 'selectFree'])->name('billing.free');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
});

// Stripe webhook — no auth, no CSRF
Route::post('/stripe/webhook', [BillingController::class, 'webhook'])->name('stripe.webhook');

// Stories index — auth only (demo browsing, no subscription needed)
Route::middleware(['auth'])->group(function () {
    Route::get('/stories', [StoryController::class, 'index'])->name('stories.index');
});

// Stories write/AI routes — registered before wildcard /{story} routes to avoid conflict
// Require verified email + active subscription
Route::middleware(['auth', 'verified', 'requires.subscription'])->group(function () {
    Route::get('/stories/create', [StoryController::class, 'create'])->name('stories.create');
    Route::post('/stories/init', [StoryController::class, 'init'])->name('stories.init');
    Route::post('/stories/interview', [StoryController::class, 'interview'])->name('stories.interview');
    Route::post('/stories', [StoryController::class, 'store'])->name('stories.store');
    Route::patch('/stories/{story}/progress', [StoryController::class, 'saveProgress'])->name('stories.progress');
    Route::post('/stories/{story}/generate', [StoryController::class, 'generate'])->name('stories.generate');
    Route::delete('/stories/{story}', [StoryController::class, 'destroy'])->name('stories.destroy');
    Route::post('/stories/{story}/regenerate', [StoryController::class, 'regenerateEpisode'])->name('stories.regenerate');
    Route::get('/stories/{story}/episodes/{episode}/versions', [StoryController::class, 'episodeVersions'])->name('stories.episode.versions');
    Route::post('/stories/{story}/episodes/{episode}/versions/{version}/restore', [StoryController::class, 'restoreVersion'])->name('stories.episode.restore');
});

// Stories read-only by ID — wildcard routes, registered after literal /stories/create
// Auth only (unverified, unsubscribed users can browse demo content)
Route::middleware(['auth'])->group(function () {
    Route::get('/stories/{story}', [StoryController::class, 'show'])->name('stories.show');
    Route::get('/stories/{story}/resume', [StoryController::class, 'resume'])->name('stories.resume');
});

Route::middleware('auth')->group(function () {
    Route::post('/impersonate/stop', [AdminController::class, 'stopImpersonating'])->name('impersonate.stop');
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
    Route::get('/stories/{story}', [AdminController::class, 'storyShow'])->name('stories.show');
    Route::get('/manual', fn () => Inertia::render('Admin/Manual'))->name('manual');
    Route::get('/billing', [AdminController::class, 'billingIndex'])->name('billing.index');
    Route::get('/settings', [AdminController::class, 'settingsIndex'])->name('settings.index');
    Route::get('/settings/access', [AdminController::class, 'accessSettingsIndex'])->name('settings.access');
    Route::post('/settings/access', [AdminController::class, 'updateAccessSettings'])->name('settings.access.update');
    Route::get('/settings/ai', [AdminController::class, 'aiSettingsIndex'])->name('settings.ai');
    Route::post('/settings/ai', [AdminController::class, 'updateAiSettings'])->name('settings.ai.update');
    Route::get('/settings/ai/models', [AdminController::class, 'fetchModels'])->name('settings.ai.models');
    Route::get('/settings/stripe', [AdminController::class, 'stripeSettingsIndex'])->name('settings.stripe');
    Route::post('/settings/stripe', [AdminController::class, 'updateStripeSettings'])->name('settings.stripe.update');

    // User actions
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [AdminController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/profile', [AdminController::class, 'updateProfile'])->name('users.profile');
    Route::post('/users/{user}/password', [AdminController::class, 'resetPassword'])->name('users.password');
    Route::post('/users/{user}/plan', [AdminController::class, 'assignPlan'])->name('users.assign-plan');
    Route::patch('/users/{user}/subscription', [AdminController::class, 'updateSubscription'])->name('users.subscription');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/impersonate', [AdminController::class, 'impersonate'])->name('users.impersonate');
    Route::get('/users/{user}/invoices', [AdminController::class, 'userInvoices'])->name('users.invoices');

    // Plan actions
    Route::post('/plans', [AdminController::class, 'storePlan'])->name('plans.store');
    Route::patch('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
    Route::delete('/plans/{plan}', [AdminController::class, 'destroyPlan'])->name('plans.destroy');
});

require __DIR__.'/auth.php';
