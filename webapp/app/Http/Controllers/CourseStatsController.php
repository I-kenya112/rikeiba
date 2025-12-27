<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseStatsController extends Controller
{
    /**
     * years パラメータを解釈して「年の配列」を返す
     *
     * - "2022"        → [2022]
     * - "2020-2024"   → [2020,2021,2022,2023,2024]
     * - null / ""     → []
     */
    protected function resolveYears(?string $years): array
    {
        if (!$years) {
            return [];
        }

        // 単年
        if (preg_match('/^\d{4}$/', $years)) {
            return [(int)$years];
        }

        // 範囲
        if (preg_match('/^(\d{4})-(\d{4})$/', $years, $m)) {
            $from = (int)$m[1];
            $to   = (int)$m[2];

            return range(min($from, $to), max($from, $to));
        }

        return [];
    }

    /**
     * コース別 祖先成績（年次集計）
     */
    public function ancestor(Request $request, string $course_key)
    {
        [$jyo, $courseType, $distance] = explode('-', $course_key);

        $grade        = $request->query('grade', 'ALL');
        $yearsParam   = $request->query('years');
        $sort         = $request->query('sort', 'show_rate');
        $ancestorMode = $request->query('ancestor_mode', 'ALL');

        // 年候補（Vue 用）
        $yearOptions = DB::table('ri_course_ancestor_stats')
            ->where('jyo_cd', $jyo)
            ->where('course_type', $courseType)
            ->where('distance', $distance)
            ->where('grade_group', $grade)
            ->where('ancestor_mode', $ancestorMode)
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $years = $this->resolveYears($yearsParam);

        $query = DB::table('ri_course_ancestor_stats')
            ->where('jyo_cd', $jyo)
            ->where('course_type', $courseType)
            ->where('distance', $distance)
            ->where('grade_group', $grade)
            ->where('ancestor_mode', $ancestorMode);

        if (!empty($years)) {
            $query->whereIn('year', $years);
        }

        $rows = $query
            ->selectRaw("
                ancestor_id,
                ancestor_name,
                SUM(start_count)        as start_count,
                SUM(win_count)          as win_count,
                SUM(place_count)        as place_count,
                SUM(show_count)         as show_count,
                SUM(board_count)        as board_count,
                SUM(out_of_board_count) as out_of_board_count,
                AVG(avg_blood_share)    as avg_blood_share
            ")
            ->groupBy('ancestor_id', 'ancestor_name')
            ->get()
            ->map(function ($r) {
                $start = max(1, (int)$r->start_count);
                $r->win_rate   = $r->win_count   / $start;
                $r->place_rate = $r->place_count / $start;
                $r->show_rate  = $r->show_count  / $start;
                $r->board_rate = $r->board_count / $start;
                return $r;
            })
            ->sortByDesc($sort)
            ->values();

        return response()->json([
            'data'                => $rows,
            'years_range_options' => $yearOptions->map(fn ($y) => (string)$y),
        ]);
    }

    /**
     * コース別 インブリード成績（年次集計）
     */
    public function inbreed(Request $request, string $course_key)
    {
        [$jyo, $courseType, $distance] = explode('-', $course_key);

        $grade      = $request->query('grade', 'ALL');
        $yearsParam = $request->query('years');
        $sort       = $request->query('sort', 'show_rate');

        $yearOptions = DB::table('ri_course_inbreed_stats')
            ->where('jyo_cd', $jyo)
            ->where('course_type', $courseType)
            ->where('distance', $distance)
            ->where('grade_group', $grade)
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $years = $this->resolveYears($yearsParam);

        $query = DB::table('ri_course_inbreed_stats')
            ->where('jyo_cd', $jyo)
            ->where('course_type', $courseType)
            ->where('distance', $distance)
            ->where('grade_group', $grade);

        if (!empty($years)) {
            $query->whereIn('year', $years);
        }

        $rows = $query
            ->selectRaw("
                ancestor_id,
                ancestor_name,
                SUM(start_count)        as start_count,
                SUM(win_count)          as win_count,
                SUM(place_count)        as place_count,
                SUM(show_count)         as show_count,
                SUM(board_count)        as board_count,
                SUM(out_of_board_count) as out_of_board_count,
                AVG(avg_blood_share)    as avg_blood_share
            ")
            ->groupBy('ancestor_id', 'ancestor_name')
            ->get()
            ->map(function ($r) {
                $start = max(1, (int)$r->start_count);
                $r->win_rate   = $r->win_count   / $start;
                $r->place_rate = $r->place_count / $start;
                $r->show_rate  = $r->show_count  / $start;
                $r->board_rate = $r->board_count / $start;
                return $r;
            })
            ->sortByDesc($sort)
            ->values();

        return response()->json([
            'data'                => $rows,
            'years_range_options' => $yearOptions->map(fn ($y) => (string)$y),
        ]);
    }
}
