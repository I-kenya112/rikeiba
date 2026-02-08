<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use InvalidArgumentException;

class CourseAncestorController extends Controller
{
    /**
     * コース別 祖先成績（集計済み）
     */
    public function index(Request $request, string $course_key)
    {
        $grade        = strtoupper($request->query('grade', 'ALL'));
        $trackCondition = $request->query('track_condition', 'ALL');
        $ancestorMode = strtoupper($request->query('ancestor_mode', 'ALL'));
        $yearsParam   = $request->query('years');
        $sort         = $request->query('sort', 'show_rate');

        // --- 許可値（Serviceに完全準拠） ---
        $allowedGrades = ['ALL', 'G1', 'GRADE', 'OP'];
        if (!in_array($grade, $allowedGrades, true)) {
            throw new InvalidArgumentException("Invalid grade: {$grade}");
        }

        $allowedTrackConditions = ['ALL', 'GOOD', 'YAYA', 'HEAVY', 'BAD'];
        if (!in_array($trackCondition, $allowedTrackConditions, true)) {
            throw new InvalidArgumentException("Invalid track_condition: {$trackCondition}");
        }

        $allowedModes = ['ALL', 'F', 'M', 'FM'];
        if (!in_array($ancestorMode, $allowedModes, true)) {
            throw new InvalidArgumentException("Invalid ancestor_mode: {$ancestorMode}");
        }

        $years = $this->resolveYears($yearsParam);

        /*
        |--------------------------------------------------------------------------
        | 年候補
        |--------------------------------------------------------------------------
        */
        $yearOptionsQuery = DB::table('ri_course_ancestor')
            ->where('course_key', $course_key)
            ->where('grade_group', $grade)
            ->where('ancestor_mode', $ancestorMode);

        if ($trackCondition !== 'ALL') {
            $yearOptionsQuery->where('track_condition', $trackCondition);
        }

        $yearOptions = $yearOptionsQuery
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        /*
        |--------------------------------------------------------------------------
        | 本体データ
        |--------------------------------------------------------------------------
        */
        $query = DB::table('ri_course_ancestor')
            ->where('course_key', $course_key)
            ->where('grade_group', $grade)
            ->where('ancestor_mode', $ancestorMode);

        if ($trackCondition !== 'ALL') {
            $query->where('track_condition', $trackCondition);
        }

        if (!empty($years)) {
            $query->whereIn('year', $years);
        }

        $sortKey = $this->normalizeSortKey($sort);

        $rows = $query
            ->selectRaw("
                ancestor_id,
                ancestor_name,
                line_key,
                line_key_detail,
                SUM(start_count)        as start_count,
                SUM(win_count)          as win_count,
                SUM(place_count)        as place_count,
                SUM(show_count)         as show_count,
                SUM(board_count)        as board_count,
                SUM(out_of_board_count) as out_of_board_count,
                AVG(avg_blood_share)    as avg_blood_share
            ")
            ->groupBy(
                'ancestor_id',
                'ancestor_name',
                'line_key',
                'line_key_detail'
            )
            ->get()
            ->map(function ($r) {
                $start = max(1, (int)$r->start_count);
                $r->win_rate   = $r->win_count   / $start;
                $r->place_rate = $r->place_count / $start;
                $r->show_rate  = $r->show_count  / $start;
                $r->board_rate = $r->board_count / $start;
                return $r;
            })
            ->sortByDesc($sortKey)
            ->values();

        return response()->json([
            'data' => $rows,
            'years_range_options' => $yearOptions->map(fn($y) => (string)$y),
        ]);
    }

    /**
     * 年パラメータ解釈
     */
    protected function resolveYears(?string $years): array
    {
        if (!$years) return [];

        if (preg_match('/^\d{4}$/', $years)) {
            return [(int)$years];
        }

        if (preg_match('/^(\d{4})-(\d{4})$/', $years, $m)) {
            $a = (int)$m[1];
            $b = (int)$m[2];
            return range(min($a, $b), max($a, $b));
        }

        return [];
    }

    /**
     * sort 安全化
     */
    protected function normalizeSortKey(string $sort): string
    {
        $allowed = [
            'win_rate',
            'place_rate',
            'show_rate',
            'board_rate',
            'start_count',
            'win_count',
            'place_count',
            'show_count',
            'board_count',
            'out_of_board_count',
            'avg_blood_share',
        ];

        return in_array($sort, $allowed, true)
            ? $sort
            : 'show_rate';
    }
}
