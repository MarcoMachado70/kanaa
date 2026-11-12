<?php

use App\Http\Controllers\ChurchController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CommunityManagementController;
use App\Http\Controllers\CommunityMembershipController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\PostInteractionController;
use App\Http\Controllers\PostPhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SinglesController;
use App\Http\Controllers\SocialPostController;
use App\Http\Controllers\SocialProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/igrejas', [ChurchController::class, 'index'])->name('churches.index');
Route::get('/igrejas/{church:slug}', [ChurchController::class, 'show'])->name('churches.show');

Route::get('/comunidades', [CommunityController::class, 'index'])->name('communities.index');
Route::get('/comunidades/criar', [CommunityController::class, 'create'])->middleware('auth')->name('communities.create');
Route::get('/comunidades/{slug}', [CommunityController::class, 'show'])->name('communities.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/entrar', [SessionController::class, 'create'])->name('login');
    Route::post('/entrar', [SessionController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
    Route::get('/cadastro', [RegistrationController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegistrationController::class, 'store'])->middleware('throttle:6,1')->name('register.store');
});
Route::middleware('auth')->group(function (): void {
    Route::post('/sair', [SessionController::class, 'destroy'])->name('logout');
    Route::get('/perfil', ProfileController::class)->name('profile');
    Route::put('/comunidades/{slug}/participacao', [CommunityMembershipController::class, 'store'])->middleware('throttle:20,1')->name('memberships.store');
    Route::delete('/comunidades/{slug}/participacao', [CommunityMembershipController::class, 'destroy'])->name('memberships.destroy');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/comunidades/{community:slug}/gestao', [CommunityManagementController::class, 'index'])->name('communities.manage');
    Route::patch('/comunidades/{community:slug}/gestao/participacoes/{membership}', [CommunityManagementController::class, 'update'])->whereNumber('membership')->middleware('throttle:30,1')->name('communities.review');
});

Route::get('/pessoas/{user}', [SocialProfileController::class, 'show'])->name('people.show');
Route::middleware('auth')->group(function (): void {
    Route::get('/perfil/editar', [SocialProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/perfil', [SocialProfileController::class, 'update'])->name('profile.update');
    Route::post('/comunidades', [CommunityController::class, 'store'])->middleware('throttle:5,1')->name('communities.store');
    Route::post('/publicacoes', [SocialPostController::class, 'store'])->middleware('throttle:20,1')->name('posts.store');
    Route::delete('/publicacoes/{post}', [SocialPostController::class, 'destroy'])->name('posts.destroy');
});

Route::get('/publicacoes/{post}', [PostInteractionController::class, 'show'])->name('posts.show');
Route::middleware(['auth', 'throttle:30,1'])->group(function (): void {
    Route::put('/publicacoes/{post}/curtida', [PostInteractionController::class, 'like'])->name('posts.like');
    Route::delete('/publicacoes/{post}/curtida', [PostInteractionController::class, 'unlike'])->name('posts.unlike');
    Route::post('/publicacoes/{post}/comentarios', [PostInteractionController::class, 'comment'])->name('comments.store');
    Route::delete('/publicacoes/{post}/comentarios/{comment}', [PostInteractionController::class, 'destroyComment'])->whereNumber('comment')->name('comments.destroy');
});

Route::get('/publicacoes/{post}/foto', PostPhotoController::class)->name('posts.photo');

Route::middleware('auth')->group(function (): void {
    Route::get('/pessoas', [PeopleController::class, 'index'])->name('people.index');
    Route::put('/pessoas/{user}/seguir', [PeopleController::class, 'store'])->middleware('throttle:30,1')->name('people.follow');
    Route::delete('/pessoas/{user}/seguir', [PeopleController::class, 'destroy'])->name('people.unfollow');
});

Route::middleware('auth')->prefix('solteiros')->name('singles.')->group(function (): void {
    Route::get('/', [SinglesController::class, 'index'])->name('index');
    Route::put('/perfil', [SinglesController::class, 'save'])->middleware('throttle:10,1')->name('save');
    Route::delete('/perfil', [SinglesController::class, 'pause'])->name('pause');
    Route::put('/interesses/{profile}', [SinglesController::class, 'interest'])->middleware('throttle:15,1')->name('interest');
    Route::delete('/interesses/{profile}', [SinglesController::class, 'cancelInterest'])->name('cancel');
    Route::get('/matches/{match}', [SinglesController::class, 'showMatch'])->name('match');
    Route::delete('/matches/{match}', [SinglesController::class, 'endMatch'])->name('end');
    Route::put('/matches/{match}/autorizacao', [SinglesController::class, 'authorizeContact'])->middleware('throttle:10,1')->name('authorize');
    Route::delete('/matches/{match}/autorizacao', [SinglesController::class, 'revokeContact'])->name('revoke');
    Route::get('/matches/{match}/contato', [SinglesController::class, 'contact'])->name('contact');
    Route::put('/bloqueios/{profile}', [SinglesController::class, 'block'])->middleware('throttle:10,1')->name('block');
    Route::post('/denuncias/{profile}', [SinglesController::class, 'report'])->middleware('throttle:5,1')->name('report');
});
