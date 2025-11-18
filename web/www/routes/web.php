<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MidnightTestController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\KycController;

// Slovak Routes (default - no prefix)
Route::get('/', [ElectionController::class, 'home'])->name('home');
Route::get('/volby/{id}', [ElectionController::class, 'show'])->name('election.show');
Route::get('/volby/{id}/vysledky', [ElectionController::class, 'results'])->name('election.results');
Route::get('/volby/{id}/overit-hlas', [ElectionController::class, 'verifyPage'])->name('election.verify');
Route::post('/volby/{id}/overit-hlas', [ElectionController::class, 'verifyVote'])->name('election.verify.post');
Route::get('/ako-hlasovat', [ElectionController::class, 'howToVote'])->name('how-to-vote');
Route::post('/hlasovat/{election}/{candidate}', [ElectionController::class, 'vote'])->name('vote');

// KYC Routes (Slovak)
Route::get('/kyc', [KycController::class, 'index'])->name('kyc.create');
Route::post('/kyc', [KycController::class, 'store'])->name('kyc.store');
Route::get('/kyc/status', [KycController::class, 'status'])->name('kyc.status');

// English Routes (prefixed with /en)
Route::prefix('en')->group(function () {
    Route::get('/', [ElectionController::class, 'home'])->name('en.home');
    Route::get('/elections/{id}', [ElectionController::class, 'show'])->name('en.election.show');
    Route::get('/elections/{id}/results', [ElectionController::class, 'results'])->name('en.election.results');
    Route::get('/elections/{id}/verify-vote', [ElectionController::class, 'verifyPage'])->name('en.election.verify');
    Route::post('/elections/{id}/verify-vote', [ElectionController::class, 'verifyVote'])->name('en.election.verify.post');
    Route::get('/how-to-vote', [ElectionController::class, 'howToVote'])->name('en.how-to-vote');
    Route::post('/vote/{election}/{candidate}', [ElectionController::class, 'vote'])->name('en.vote');

    // KYC Routes (English)
    Route::get('/kyc', [KycController::class, 'index'])->name('en.kyc.create');
    Route::post('/kyc', [KycController::class, 'store'])->name('en.kyc.store');
    Route::get('/kyc/status', [KycController::class, 'status'])->name('en.kyc.status');
});

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes (requires authentication)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Language Switcher
    Route::get('/locale/{locale}', [AdminController::class, 'switchLocale'])->name('locale.switch');

    // Dashboard
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    // Elections
    Route::get('/elections/create', [AdminController::class, 'createElection'])->name('elections.create');
    Route::post('/elections', [AdminController::class, 'storeElection'])->name('elections.store');
    Route::get('/elections/{id}', [AdminController::class, 'showElection'])->name('elections.show');
    Route::get('/elections/{id}/edit', [AdminController::class, 'editElection'])->name('elections.edit');
    Route::put('/elections/{id}', [AdminController::class, 'updateElection'])->name('elections.update');
    Route::delete('/elections/{id}', [AdminController::class, 'destroyElection'])->name('elections.destroy');

    // Election Blockchain Operations
    Route::post('/elections/{id}/deploy', [AdminController::class, 'deployElection'])->name('elections.deploy');
    Route::post('/elections/{id}/open', [AdminController::class, 'openElection'])->name('elections.open');
    Route::post('/elections/{id}/close', [AdminController::class, 'closeElection'])->name('elections.close');

    // Candidates
    Route::get('/elections/{electionId}/candidates/create', [AdminController::class, 'createCandidate'])->name('candidates.create');
    Route::post('/elections/{electionId}/candidates', [AdminController::class, 'storeCandidate'])->name('candidates.store');
    Route::get('/candidates/{id}/edit', [AdminController::class, 'editCandidate'])->name('candidates.edit');
    Route::put('/candidates/{id}', [AdminController::class, 'updateCandidate'])->name('candidates.update');
    Route::delete('/candidates/{id}', [AdminController::class, 'destroyCandidate'])->name('candidates.destroy');

    // Candidate Blockchain Operations
    Route::post('/candidates/{id}/register', [AdminController::class, 'registerCandidate'])->name('candidates.register');

    // KYC Management
    Route::get('/kyc', [KycController::class, 'adminIndex'])->name('kyc.index');
    Route::post('/kyc/{id}/approve', [KycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{id}/reject', [KycController::class, 'reject'])->name('kyc.reject');
});

// Midnight/Lace Wallet Test Routes
Route::get('/midnight/test', [MidnightTestController::class, 'index'])->name('midnight.test');
Route::post('/api/midnight/test-contract', [MidnightTestController::class, 'testContractCall']);
Route::get('/api/midnight/check-bridge', [MidnightTestController::class, 'checkBridge']);
Route::get('/api/midnight/wallet-status', [MidnightTestController::class, 'walletStatus']);
