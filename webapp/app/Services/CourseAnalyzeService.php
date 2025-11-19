<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourseAnalyzeService
{
    /**
     * メインエントリ
     *
     * @param  array<string,mixed>  $opts
     *   - mode          : ALL|ANCESTOR|INBREED
     *   - grade         : ALL|G1|G2|G3|OP|COND
     *   - from          : YYYY-MM-DD|null
     *   - to            : YYYY-MM-DD|null
     *   - course        : "08-TURF-2200,05-TURF-2400" など
     *   - soft          : （将来用）
     *   - limitYears    : from 未指定時の直近年数
     *   - excludeCurrentYear : bool
     *   - excludeYears  : "2020,2021" など（未使用：将来用）
     *   - ancestor_mode : ALL|F|M|FM  （A-2 モード）
     *
     * @return int  upsert した行数
     */
    public function run(array $opts): int
    {
        $mode          = strtoupper($opts['mode']         ?? 'ALL');  // ALL|ANCESTOR|INBREED
        $grade         = strtoupper($opts['grade']        ?? 'ALL');  // ALL|G1|G2|G3|OP|COND...
        $from          = $opts['from']        ?? null;                // YYYY-MM-DD or null
        $to            = $opts['to']          ?? null;                // YYYY-MM-DD or null
        $courseFilter  = $opts['course']      ?? null;                // "08-TURF-2200,05-TURF-2400" など
        $limitYears    = (int)($opts['limitYears'] ?? 0);
        $excludeCurrentYear = !empty($opts['excludeCurrentYear']);
        $excludeYearsRaw    = $opts['excludeYears'] ?? '';            // "2020,2021" など（未使用だが将来用）

        // A-2 モード：ALL / F / M / FM
        // ancestor_mode / ancestor の両方から拾う（後方互換）
        $ancestorMode = strtoupper($opts['ancestor_mode'] ?? $opts['ancestor'] ?? 'ALL');

        // 除外年はとりあえず配列にしておくだけ（将来対応用）
        $excludeYears = [];
        if (is_string($excludeYearsRaw) && trim($excludeYearsRaw) !== '') {
            $excludeYears = array_values(array_filter(array_map('trim', explode(',', $excludeYearsRaw))));
        }

        // 対象期間（Year ベース）の決定と、表示用 years_range 文字列
        [$fromYear, $toYear, $yearsRange] = $this->resolveYearRange($from, $to, $limitYears, $excludeCurrentYear);

        $totalUpserted = 0;

        // 祖先ベースの集計（ri_pedigree）
        if ($mode === 'ALL' || $mode === 'ANCESTOR') {
            $totalUpserted += $this->aggregateAncestors(
                $grade,
                $fromYear,
                $toYear,
                $yearsRange,
                $courseFilter,
                $ancestorMode
            );
        }

        // インブリードベースの集計（ri_inbreed_ratio）
        if ($mode === 'ALL' || $mode === 'INBREED') {
            $totalUpserted += $this->aggregateInbreeds(
                $grade,
                $fromYear,
                $toYear,
                $yearsRange,
                $courseFilter,
                $ancestorMode  // 現状はラベル用途のみ
            );
        }

        return $totalUpserted;
    }

    /**
     * 期間オプションから Year レンジを決定
     *
     * @return array{0:int,1:int,2:string} [$fromYear, $toYear, $yearsRangeLabel]
     */
    protected function resolveYearRange(?string $from, ?string $to, int $limitYears, bool $excludeCurrentYear): array
    {
        if ($from !== null && $from !== '') {
            $fromDate = Carbon::parse($from);
            $toDate   = $to ? Carbon::parse($to) : $fromDate;

            $fromYear = (int)$fromDate->year;
            $toYear   = (int)$toDate->year;
        } else {
            $nowYear = (int)Carbon::now()->year;
            $toYear  = $excludeCurrentYear ? $nowYear - 1 : $nowYear;

            if ($limitYears > 0) {
                $fromYear = $toYear - $limitYears + 1;
            } else {
                // limitYears が 0 以下なら、その年だけ
                $fromYear = $toYear;
            }
        }

        if ($fromYear > $toYear) {
            [$fromYear, $toYear] = [$toYear, $fromYear];
        }

        $yearsRange = sprintf('%04d-%04d', $fromYear, $toYear);

        return [$fromYear, $toYear, $yearsRange];
    }

    /**
     * 京都芝2200 など、コースのキー式
     *
     * 例: "08-TURF-2200"
     */
    protected function courseKeyExpression(): string
    {
        return "
            CONCAT(
                LPAD(r.JyoCD, 2, '0'),
                '-',
                CASE
                    WHEN r.TrackCD IN ('10','11','12','13','14','15','16','17','18','19','20','21','22') THEN 'TURF'
                    WHEN r.TrackCD IN ('23','24','25','26','27','28','29') THEN 'DIRT'
                    WHEN r.TrackCD BETWEEN '51' AND '59' THEN 'STEEP'
                    ELSE 'UNKNOWN'
                END,
                '-',
                r.Kyori
            )
        ";
    }

    /**
     * TURF / DIRT / STEEP を返す式
     */
    protected function trackTypeExpression(): string
    {
        return "
            CASE
                WHEN r.TrackCD IN ('10','11','12','13','14','15','16','17','18','19','20','21','22') THEN 'TURF'
                WHEN r.TrackCD IN ('23','24','25','26','27','28','29') THEN 'DIRT'
                WHEN r.TrackCD BETWEEN '51' AND '59' THEN 'STEEP'
                ELSE 'UNKNOWN'
            END
        ";
    }

    /**
     * グレードフィルタをクエリに適用
     */
    protected function applyGradeFilter($query, string $grade): void
    {
        switch ($grade) {
            case 'G1':
                // G1 + JG1
                $query->whereIn('r.GradeCD', ['A', 'F']);
                break;

            case 'G2':
                // G2 + JG2
                $query->whereIn('r.GradeCD', ['B', 'G']);
                break;

            case 'G3':
                // G3 + JG3
                $query->whereIn('r.GradeCD', ['C', 'H']);
                break;

            case 'GRADE':
                // 重賞
                $query->whereIn('r.GradeCD', ['A','B','C', 'F','G','H']);
                break;

            case 'OP':
                // OP以上（OP + 全重賞）
                // A～H（G1,G2,G3,グレードなし重賞,特別,障害G1～G3）
                $query->whereIn('r.GradeCD', ['A','B','C','D','E','F','G','H']);
                break;

            case 'COND':
                // 条件戦（一般競走）= GradeCD '_' or NULL
                $query->where(function ($q) {
                    $q->where('r.GradeCD', '_')
                      ->orWhereNull('r.GradeCD');
                });
                break;

            case 'ALL':
            default:
                // 絞りなし（重賞＋条件戦すべて）
                break;
        }
    }

    /**
     * コースフィルタ（08-TURF-2200 など）をクエリに適用
     */
    protected function applyCourseFilter($query, ?string $courseFilter, string $courseKeyExpr): void
    {
        if (!$courseFilter) {
            return;
        }

        $keys = array_values(array_filter(array_map('trim', explode(',', $courseFilter))));
        if (empty($keys)) {
            return;
        }

        $query->whereIn(DB::raw($courseKeyExpr), $keys);
    }

    /**
     * ancestor_mode による relation_path 絞り込み（A-2 仕様）
     *
     * - ALL: SELF 以外すべて
     * - F  : F から始まる（父系起点）
     * - M  : M から始まる（母系起点）
     * - FM : F または M から始まる（父＋母系）
     */
    protected function applyAncestorModeFilter($query, string $ancestorMode): void
    {
        switch ($ancestorMode) {
            case 'F':
                // 父系のみ
                $query->where('p.relation_path', 'LIKE', 'F%');
                break;

            case 'M':
                // 母系のみ
                $query->where('p.relation_path', 'LIKE', 'M%');
                break;

            case 'FM':
                // 父系 or 母系（SELF は除外）
                $query->where(function ($q) {
                    $q->where('p.relation_path', 'LIKE', 'F%')
                      ->orWhere('p.relation_path', 'LIKE', 'M%');
                });
                break;

            case 'ALL':
            default:
                // SELF 以外の全祖先
                $query->where('p.relation_path', '<>', 'SELF');
                break;
        }
    }

    /**
     * 祖先（ri_pedigree）ベースの集計
     */
    protected function aggregateAncestors(
        string $grade,
        int $fromYear,
        int $toYear,
        string $yearsRange,
        ?string $courseFilter,
        string $ancestorMode
    ): int {
        $courseKeyExpr  = $this->courseKeyExpression();
        $trackTypeExpr  = $this->trackTypeExpression();

        $query = DB::table('ri_race as r')
            ->join('ri_uma_race as ur', 'ur.race_key', '=', 'r.race_key')
            ->join('ri_pedigree as p', 'p.horse_id', '=', 'ur.KettoNum')
            ->whereNotNull('ur.KakuteiJyuni')
            ->where('ur.KakuteiJyuni', '>', '0')
            ->whereBetween('r.Year', [$fromYear, $toYear]);

        // ancestor_mode による relation_path 絞り込み
        $this->applyAncestorModeFilter($query, $ancestorMode);

        // グレードフィルタ
        $this->applyGradeFilter($query, $grade);

        // コースフィルタ
        $this->applyCourseFilter($query, $courseFilter, $courseKeyExpr);

        // 祖先ID（未解決は "(UNKNOWN)"）、名前（NULLは "(不明)"）
        $ancestorIdExpr   = "COALESCE(p.ancestor_id_hansyoku, p.ancestor_id_uma, '(UNKNOWN)')";
        $ancestorNameExpr  = "COALESCE(p.ancestor_name, '(不明)')";

        $rows = $query
            ->selectRaw("
                {$courseKeyExpr}   as course_key,
                {$trackTypeExpr}   as track_type,
                CAST(r.Kyori AS UNSIGNED) as distance,
                ?                  as years_range,
                ?                  as grade_group,
                {$ancestorIdExpr} as ancestor_id,
                {$ancestorNameExpr} as ancestor_name,
                COUNT(*) as start_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) = 1 THEN 1 ELSE 0 END) as win_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) <= 2 THEN 1 ELSE 0 END) as place_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) <= 3 THEN 1 ELSE 0 END) as show_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) <= 5 THEN 1 ELSE 0 END) as board_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) > 5 THEN 1 ELSE 0 END)  as out_of_board_count,
                AVG(p.blood_share) as avg_blood_share
            ", [
                $yearsRange,
                $grade,
            ])
            ->groupByRaw("
                {$courseKeyExpr},
                {$trackTypeExpr},
                CAST(r.Kyori AS UNSIGNED),
                {$ancestorIdExpr},
                {$ancestorNameExpr}
            ")
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $now = Carbon::now();
        $hasAncestorModeColumn = Schema::hasColumn('ri_course_ancestor_stats', 'ancestor_mode');

        $insertData = [];
        foreach ($rows as $row) {
            $base = [
                'course_key'          => $row->course_key,
                'grade_group'         => $row->grade_group,
                'distance'            => $row->distance,
                'track_type'          => $row->track_type,
                'years_range'         => $row->years_range,
                'ancestor_id'        => $row->ancestor_id,
                'ancestor_name'       => $row->ancestor_name,
                'start_count'         => (int)$row->start_count,
                'win_count'           => (int)$row->win_count,
                'place_count'         => (int)$row->place_count,
                'show_count'          => (int)$row->show_count,
                'board_count'         => (int)$row->board_count,
                'out_of_board_count'  => (int)$row->out_of_board_count,
                'avg_blood_share'     => $row->avg_blood_share,
                'updated_at'          => $now,
                'created_at'          => $now,
            ];

            if ($hasAncestorModeColumn) {
                $base['ancestor_mode'] = $ancestorMode;
            }

            $insertData[] = $base;
        }

        // unique key は ancestor_mode の有無で変える
        $uniqueBy = [
            'course_key',
            'grade_group',
            'distance',
            'track_type',
            'years_range',
            'ancestor_id',
        ];
        if ($hasAncestorModeColumn) {
            $uniqueBy[] = 'ancestor_mode';
        }

        // ✅ 内包祖先統計
        collect($insertData)
            ->chunk(3000)
            ->each(function ($chunk) use ($uniqueBy) {
                DB::table('ri_course_ancestor_stats')->upsert(
                    $chunk->toArray(),
                    $uniqueBy,
                    [
                        'ancestor_name',
                        'start_count',
                        'win_count',
                        'place_count',
                        'show_count',
                        'board_count',
                        'out_of_board_count',
                        'avg_blood_share',
                        'updated_at',
                    ]
                );
            });

        return count($insertData);
    }

    /**
     * インブリード（ri_inbreed_ratio）ベースの集計
     *
     * ※ 現時点では ancestor_mode は「ラベル用途のみ」。
     *    relation_path での絞り込みは行っていない。
     */
    protected function aggregateInbreeds(
        string $grade,
        int $fromYear,
        int $toYear,
        string $yearsRange,
        ?string $courseFilter,
        string $ancestorMode
    ): int {
        $courseKeyExpr  = $this->courseKeyExpression();
        $trackTypeExpr  = $this->trackTypeExpression();

        $query = DB::table('ri_race as r')
            ->join('ri_uma_race as ur', 'ur.race_key', '=', 'r.race_key')
            ->join('ri_inbreed_ratio as ir', 'ir.horse_id', '=', 'ur.KettoNum')
            ->whereNotNull('ur.KakuteiJyuni')
            ->where('ur.KakuteiJyuni', '>', '0')
            ->whereBetween('r.Year', [$fromYear, $toYear]);

        // グレードフィルタ
        $this->applyGradeFilter($query, $grade);

        // コースフィルタ
        $this->applyCourseFilter($query, $courseFilter, $courseKeyExpr);

        $rows = $query
            ->selectRaw("
                {$courseKeyExpr}   as course_key,
                {$trackTypeExpr}   as track_type,
                CAST(r.Kyori AS UNSIGNED) as distance,
                ?                  as years_range,
                ?                  as grade_group,
                ir.ancestor_id     as ancestor_id,
                ir.ancestor_name   as ancestor_name,
                COUNT(*)           as start_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) = 1 THEN 1 ELSE 0 END) as win_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) <= 2 THEN 1 ELSE 0 END) as place_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) <= 3 THEN 1 ELSE 0 END) as show_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) <= 5 THEN 1 ELSE 0 END) as board_count,
                SUM(CASE WHEN CAST(ur.KakuteiJyuni AS UNSIGNED) > 5 THEN 1 ELSE 0 END)  as out_of_board_count,
                AVG(ir.blood_share_sum)     as avg_blood_share
            ", [
                $yearsRange,
                $grade,
            ])
            ->groupByRaw("
                {$courseKeyExpr},
                {$trackTypeExpr},
                CAST(r.Kyori AS UNSIGNED),
                ir.ancestor_id,
                ir.ancestor_name
            ")
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $now = Carbon::now();
        $hasAncestorModeColumn = Schema::hasColumn('ri_course_inbreed_stats', 'ancestor_mode');

        $insertData = [];
        foreach ($rows as $row) {
            $base = [
                'course_key'          => $row->course_key,
                'grade_group'         => $row->grade_group,
                'distance'            => $row->distance,
                'track_type'          => $row->track_type,
                'years_range'         => $row->years_range,
                'ancestor_id'        => $row->ancestor_id,
                'ancestor_name'       => $row->ancestor_name,
                'start_count'         => (int)$row->start_count,
                'win_count'           => (int)$row->win_count,
                'place_count'         => (int)$row->place_count,
                'show_count'          => (int)$row->show_count,
                'board_count'         => (int)$row->board_count,
                'out_of_board_count'  => (int)$row->out_of_board_count,
                'avg_blood_share'     => $row->avg_blood_share,
                'updated_at'          => $now,
                'created_at'          => $now,
            ];

            if ($hasAncestorModeColumn) {
                $base['ancestor_mode'] = $ancestorMode;
            }

            $insertData[] = $base;
        }

        $uniqueBy = [
            'course_key',
            'grade_group',
            'distance',
            'track_type',
            'years_range',
            'ancestor_id',
        ];
        if ($hasAncestorModeColumn) {
            $uniqueBy[] = 'ancestor_mode';
        }

        // ✅ インブリード統計
        collect($insertData)
            ->chunk(3000) // ← 3,000行ずつ分割して安全に
            ->each(function ($chunk) use ($uniqueBy) {
                DB::table('ri_course_inbreed_stats')->upsert(
                    $chunk->toArray(),
                    $uniqueBy,
                    [
                        'ancestor_name',
                        'start_count',
                        'win_count',
                        'place_count',
                        'show_count',
                        'board_count',
                        'out_of_board_count',
                        'avg_blood_share',
                        'updated_at',
                    ]
                );
            });

        return count($insertData);
    }
}
