<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * コース一覧（分析済みコースのみ）
     */
    public function options()
    {
        $rows = DB::table('ri_course_ancestor_stats')
            ->select('jyo_cd', 'course_type', 'distance')
            ->distinct()
            ->orderBy('jyo_cd')
            ->orderBy('course_type')
            ->orderBy('distance')
            ->get();

        $placeMap = [
            '01' => '札幌',
            '02' => '函館',
            '03' => '福島',
            '04' => '新潟',
            '05' => '東京',
            '06' => '中山',
            '07' => '中京',
            '08' => '京都',
            '09' => '阪神',
            '10' => '小倉',
        ];

        $data = $rows->map(function ($r) use ($placeMap) {
            $jyo = str_pad($r->jyo_cd, 2, '0', STR_PAD_LEFT);

            $courseKey = "{$jyo}-{$r->course_type}-{$r->distance}";

            $placeLabel = $placeMap[$jyo] ?? $jyo;
            $trackLabel = $r->course_type === 'TURF' ? '芝' : 'ダート';

            return [
                'course_key'   => $courseKey,
                'course_label' => "{$placeLabel} {$trackLabel} {$r->distance}m",
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }
}
