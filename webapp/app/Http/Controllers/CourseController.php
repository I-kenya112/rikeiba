<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * コース一覧（UI用・高速）
     */
    public function options()
    {
        // ri_courses は小さなマスタテーブルなので爆速
        $rows = DB::table('ri_courses')
            ->where('is_active', 1)
            ->orderBy('jyo_cd')
            ->orderBy('course_type')
            ->orderBy('distance')
            ->orderBy('detail_label')
            ->get();

        $data = $rows->map(function ($r) {
            return [
                // UIで使うキー
                'course_key' => $r->course_key,

                // 表示名（iPadで見やすい）
                'course_label' => sprintf(
                    '%s %s %dm %s',
                    $r->jyo_name,
                    $r->course_type_label,
                    $r->distance,
                    $r->detail_label
                ),

                // 将来UI用（今は使わなくてもOK）
                'jyo_cd'        => $r->jyo_cd,
                'course_type'   => $r->course_type,
                'distance'      => $r->distance,
                'turn_direction'=> $r->turn_direction,
                'course_detail' => $r->course_detail,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
