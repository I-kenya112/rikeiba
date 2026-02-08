<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HorseListController;
use App\Http\Controllers\InbreedAnalyzeController;
use App\Http\Controllers\InbreedCommonController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseAncestorController;
use App\Http\Controllers\CourseInbreedController;
use App\Http\Controllers\CourseLineComboController;
use App\Http\Controllers\CourseStallionController;
use App\Http\Controllers\HansyokuController;
use App\Http\Controllers\RiUmaController;
use App\Http\Controllers\Api\HorseListApiController;

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
    // 馬名サジェストAPI（Ajax）
    Route::get('/api/horse-lists/search', [HorseListController::class, 'ajaxSearch'])
        ->name('horse-lists.ajax.search');
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

    // 検索実行
    Route::post('/inbreed/analyze/search', [InbreedAnalyzeController::class, 'search'])
        ->name('inbreed.analyze.search');

    // 馬名サジェストAPI（Ajax）
    Route::get('/api/uma/search', [InbreedAnalyzeController::class, 'ajaxUmaSearch'])
        ->name('uma.ajax.search');
    // 祖先名サジェストAPI（Ajax）
    Route::get('/api/ancestor/search', [InbreedAnalyzeController::class, 'ajaxAncestorSearch'])
        ->name('ancestor.ajax.search');

    // ri_uma 新規登録
    Route::get('/ri-uma/create', [RiUmaController::class, 'create'])
        ->name('ri-uma.create');

    Route::post('/ri-uma/store', [RiUmaController::class, 'store'])
        ->name('ri-uma.store');
});

// ri_hansyoku 検索 API
Route::get('/api/hansyoku/search', [HansyokuController::class, 'ajaxSearch'])
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| 血統共通度一覧ビュー（逆引き分析）
|--------------------------------------------------------------------------
|
| 逆引き分析用の共通度一覧表示ページ。
|
*/
Route::get('/inbreed/common/{list_id?}', [InbreedCommonController::class, 'index'])
    ->name('inbreed.common.index');

/*
|--------------------------------------------------------------------------
| コースビュー
|--------------------------------------------------------------------------
*/
Route::get('/course', function () {
    return view('app');
});

Route::get('/course/{any}', function () {
    return view('app');
})->where('any', '.*');

/*
|==============================
| コース分析 API（Laravel 12 対応）
|==============================
*/

Route::prefix('/api')->group(function () {

    // コース一覧
    Route::get('/course-options', [CourseController::class, 'options']);

    Route::prefix('/course/{course_key}')->group(function () {

        // 祖先成績（ri_course_ancestor）
        Route::get('/ancestor-stats', [
            CourseAncestorController::class,
            'index'
        ]);

        // インブリード成績（ri_course_inbreed_stats）
        Route::get('/inbreed-stats', [
            CourseInbreedController::class,
            'index'
        ]);

        // 父×母父 系統分析（ri_course_line_combo）
        Route::get('/line-combo-stats', [
            CourseLineComboController::class,
            'index'
        ]);

        // 種牡馬分析（父、母父のみ）（ri_course_stallion）
        Route::get('/stallion-stats', [
            CourseStallionController::class,
            'index'
        ]);

    });
});

/*
|--------------------------------------------------------------------------
| 出走馬リストAPI（認証必須）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('/api')->group(function () {
    Route::get('/horse-lists', [HorseListApiController::class, 'index']);
    Route::get('/horse-lists/{list}/items', [HorseListApiController::class, 'items']);
    Route::get('/horse/{horseId}/ancestors', [HorseListApiController::class, 'ancestors']);
    Route::get('/horse/{horseId}/inbreed', [HorseListApiController::class, 'inbreed']);
    Route::get('/horse/{horseId}/line', [HorseListApiController::class, 'line']);
});

/*
|--------------------------------------------------------------------------
| コースビュー（認証必須）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/course', function () {
        return view('app');
    });

    Route::get('/course/{any}', function () {
        return view('app');
    })->where('any', '.*');

});

/*
|--------------------------------------------------------------------------
| 認証ルート
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

