<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseEntriesEvalController extends Controller
{
    public function evaluate($course_key, Request $req)
    {
        // 動作確認用の固定レスポンス
        return response()->json([
            'data' => [
                [
                    'horse_uid' => 10001,
                    'name' => 'テストホース',
                    'score' => 88,
                    'hit_ancestors' => [],
                    'inbreed_info' => []
                ]
            ]
        ]);
    }
}
