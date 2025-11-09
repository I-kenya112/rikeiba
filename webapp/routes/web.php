<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HorseListController;
use App\Http\Controllers\InbreedAnalyzeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// トップページ
Route::get('/', function () {
    return view('welcome');
});

// ダッシュボード（ログイン済みのみ）
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// プロフィール設定
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| 出走馬リスト管理
|--------------------------------------------------------------------------
|
| horse-lists/... に統一。ユーザーごとのリスト作成・削除・一覧。
|
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/horse-lists/manage', [HorseListController::class, 'index'])
        ->name('horse-lists.manage');
    Route::post('/horse-lists/save', [HorseListController::class, 'store'])
        ->name('horse-lists.save');

    Route::get('/horse-lists/edit/{id}', [HorseListController::class, 'edit'])
        ->name('horse-lists.edit');
    Route::post('/horse-lists/update/{id}', [HorseListController::class, 'update'])
        ->name('horse-lists.update');

    Route::delete('/horse-lists/delete/{id}', [HorseListController::class, 'destroy'])
        ->name('horse-lists.delete');
});

/*
|--------------------------------------------------------------------------
| 血統分析（Inbreed）
|--------------------------------------------------------------------------
|
| 出走馬リストに紐づく分析ページ。
| GET: 分析ページ表示
| POST: 検索実行
|
*/
Route::middleware(['auth'])->group(function () {

    // 分析画面の表示（list_id が渡される）
    Route::get('/inbreed/analyze/{list_id?}', [InbreedAnalyzeController::class, 'index'])
        ->name('inbreed.analyze');

    // 「分析へ進む」でキャッシュ作成してページ表示
    Route::get('/inbreed/analyze/start/{list_id}', [InbreedAnalyzeController::class, 'start'])
    ->name('inbreed.analyze.start');

    // 血統共通度分析ページ表示
    Route::get('/inbreed/common/{list_id}', [InbreedAnalyzeController::class, 'commonPage'])
        ->name('inbreed.common');

    // Ajax 分析実行
    Route::post('/inbreed/common-analyze', [InbreedAnalyzeController::class, 'commonAncestor'])
        ->name('inbreed.common-analyze');

    // 検索実行
    Route::post('/inbreed/analyze/search', [InbreedAnalyzeController::class, 'search'])
        ->name('inbreed.analyze.search');

    // 馬名サジェストAPI（Ajax）
    Route::get('/api/uma/search', [InbreedAnalyzeController::class, 'ajaxSearch'])
        ->name('uma.ajax.search');
    // 祖先名サジェストAPI（Ajax）
    Route::get('/api/ancestor/search', [InbreedAnalyzeController::class, 'ajaxAncestorSearch'])
    ->name('ancestor.ajax.search');
});

/*
|--------------------------------------------------------------------------
| 認証ルート
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
