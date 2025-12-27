<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

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
        $mode   = strtoupper($opts['mode'] ?? 'ALL');
        $grade  = strtoupper($opts['grade'] ?? 'ALL');

        $jyo    = $opts['jyo'] ?? null;
        $from   = $opts['from'] ?? null;
        $to     = $opts['to'] ?? null;

        $ancestorMode = strtoupper($opts['ancestor_mode'] ?? 'ALL');

        $excludeYears = [];
        if (!empty($opts['excludeYears'])) {
            $excludeYears = array_map(
                'intval',
                array_filter(array_map('trim', explode(',', $opts['excludeYears'])))
            );
        }

        [$fromYear, $toYear] = $this->resolveYearRange($from, $to);

        $total = 0;

        // ✅ ANCESTOR
        if ($mode === 'ALL' || $mode === 'ANCESTOR') {
            $total += $this->aggregateAncestors(
                grade: $grade,
                fromYear: $fromYear,
                toYear: $toYear,
                jyo: $jyo,
                excludeYears: $excludeYears,
                ancestorMode: $ancestorMode
            );
        }

        // ✅ INBREED（← これが抜けていた）
        if ($mode === 'ALL' || $mode === 'INBREED') {
            $total += $this->aggregateInbreeds(
                grade: $grade,
                fromYear: $fromYear,
                toYear: $toYear,
                jyo: $jyo,
                excludeYears: $excludeYears,
                ancestorMode: $ancestorMode
            );
        }

        return $total;
    }


    /**
     * from / to から年の範囲を決定
     *
     * @return array{0:int,1:int} [$fromYear, $toYear]
     */
    protected function resolveYearRange(?string $from, ?string $to): array
    {
        $parseYear = function (?string $v): ?int {
            if (!$v) return null;
            if (preg_match('/^\d{4}$/', $v)) return (int)$v;
            return (int) Carbon::parse($v)->year;
        };

        $fromYear = $parseYear($from);
        $toYear   = $parseYear($to);

        if ($fromYear !== null) {
            $toYear ??= $fromYear;
        } else {
            $now = (int) Carbon::now()->year;
            $fromYear = $toYear = $now;
        }

        if ($fromYear > $toYear) {
            [$fromYear, $toYear] = [$toYear, $fromYear];
        }

        return [$fromYear, $toYear];
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
     * turn_direction を返す式（LEFT / RIGHT / STRAIGHT / UNKNOWN）
     * TrackCD は varchar(2)
     */
    protected function turnDirectionExpression(): string
    {
        return "
            CASE
                -- 直線
                WHEN r.TrackCD IN ('10','29') THEN 'STRAIGHT'

                -- 左回り（芝/障害の左、ダ左、サンド左、など）
                WHEN r.TrackCD IN ('11','12','13','14','15','16','23','25','27','53') THEN 'LEFT'

                -- 右回り（芝/障害の右、ダ右、サンド右、など）
                WHEN r.TrackCD IN ('17','18','19','20','21','22','24','26','28') THEN 'RIGHT'

                ELSE 'UNKNOWN'
            END
        ";
    }

    /**
     * course_detail を返す式（OUTER / INNER / ... / UNKNOWN）
     * ※ 20文字に収まるよう短めにしています
     */
    protected function courseDetailExpression(): string
    {
        return "
            CASE
                -- 外回り
                WHEN r.TrackCD IN ('12','18','55') THEN 'OUTER'
                -- 内回り
                WHEN r.TrackCD IN ('25') THEN 'INNER'
                -- 内→外 / 外→内
                WHEN r.TrackCD IN ('13','19','57') THEN 'IN_TO_OUT'
                WHEN r.TrackCD IN ('14','20','56') THEN 'OUT_TO_IN'
                -- 2周系
                WHEN r.TrackCD IN ('15','21','58') THEN 'INNER_2'
                WHEN r.TrackCD IN ('16','22','59') THEN 'OUTER_2'
                -- ダ右外（仕様的に “外回り” として扱う）
                WHEN r.TrackCD IN ('26') THEN 'OUTER'

                ELSE 'UNKNOWN'
            END
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
        ?string $jyo,
        array $excludeYears,
        string $ancestorMode
    ): int {
        $trackTypeExpr     = $this->trackTypeExpression();
        $turnDirectionExpr = $this->turnDirectionExpression();
        $courseDetailExpr  = $this->courseDetailExpression();

        $ancestorIdExpr   = "COALESCE(p.ancestor_id_hansyoku, p.ancestor_id_uma, '(UNKNOWN)')";
        $ancestorNameExpr = "COALESCE(p.ancestor_name, '(不明)')";

        $totalUpserted = 0;

        for ($year = $fromYear; $year <= $toYear; $year++) {

            if (in_array($year, $excludeYears, true)) {
                continue;
            }

            $startAt = microtime(true);

            Log::info('[CourseAnalyze][ANCESTOR] year start', [
                'year' => $year,
                'jyo'  => $jyo,
                'grade'=> $grade,
                'mode' => $ancestorMode,
            ]);

            $query = DB::table('ri_race as r')
                ->join('ri_uma_race as ur', 'ur.race_key', '=', 'r.race_key')
                ->join('ri_pedigree as p', 'p.horse_id', '=', 'ur.KettoNum')
                ->where('r.Year', $year)
                ->whereNotNull('ur.KakuteiJyuni')
                ->where('ur.KakuteiJyuni', '>', 0);

            if ($jyo) {
                $query->whereIn('r.JyoCD', [
                    ltrim($jyo, '0'),
                    str_pad($jyo, 2, '0', STR_PAD_LEFT),
                ]);
            }

            $this->applyGradeFilter($query, $grade);
            $this->applyAncestorModeFilter($query, $ancestorMode);

            $rows = $query
                ->selectRaw("
                    ? as year,
                    LPAD(r.JyoCD, 2, '0') as jyo_cd,
                    {$trackTypeExpr}      as course_type,
                    {$turnDirectionExpr} as turn_direction,
                    {$courseDetailExpr}  as course_detail,
                    CAST(r.Kyori AS UNSIGNED) as distance,
                    ? as grade_group,
                    ? as ancestor_mode,
                    {$ancestorIdExpr}   as ancestor_id,
                    {$ancestorNameExpr} as ancestor_name,

                    COUNT(*) as start_count,
                    SUM(CASE WHEN ur.KakuteiJyuni = 1 THEN 1 ELSE 0 END) as win_count,
                    SUM(CASE WHEN ur.KakuteiJyuni <= 2 THEN 1 ELSE 0 END) as place_count,
                    SUM(CASE WHEN ur.KakuteiJyuni <= 3 THEN 1 ELSE 0 END) as show_count,
                    SUM(CASE WHEN ur.KakuteiJyuni <= 5 THEN 1 ELSE 0 END) as board_count,
                    SUM(CASE WHEN ur.KakuteiJyuni >  5 THEN 1 ELSE 0 END) as out_of_board_count,
                    AVG(p.blood_share) as avg_blood_share
                ", [$year, $grade, $ancestorMode])
                ->groupByRaw("
                    LPAD(r.JyoCD, 2, '0'),
                    {$trackTypeExpr},
                    {$turnDirectionExpr},
                    {$courseDetailExpr},
                    CAST(r.Kyori AS UNSIGNED),
                    {$ancestorIdExpr},
                    {$ancestorNameExpr}
                ")
                ->orderBy('jyo_cd')
                ->lazy(2000);

            $buffer = [];
            $now    = now();
            $count  = 0;

            foreach ($rows as $r) {
                $count++;

                $buffer[] = [
                    'year'               => $r->year,
                    'jyo_cd'             => $r->jyo_cd,
                    'course_type'        => $r->course_type,
                    'turn_direction'     => $r->turn_direction,
                    'course_detail'      => $r->course_detail,
                    'distance'           => (int)$r->distance,
                    'grade_group'        => $r->grade_group,
                    'ancestor_mode'      => $r->ancestor_mode,
                    'ancestor_id'        => $r->ancestor_id,
                    'ancestor_name'      => $r->ancestor_name,
                    'start_count'        => (int)$r->start_count,
                    'win_count'          => (int)$r->win_count,
                    'place_count'        => (int)$r->place_count,
                    'show_count'         => (int)$r->show_count,
                    'board_count'        => (int)$r->board_count,
                    'out_of_board_count' => (int)$r->out_of_board_count,
                    'avg_blood_share'    => $r->avg_blood_share,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];

                if (count($buffer) >= 3000) {
                    DB::table('ri_course_ancestor_stats')->upsert(
                        $buffer,
                        [
                            'year',
                            'jyo_cd',
                            'course_type',
                            'turn_direction',
                            'course_detail',
                            'distance',
                            'grade_group',
                            'ancestor_mode',
                            'ancestor_id',
                        ],
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

                    $totalUpserted += count($buffer);
                    $buffer = [];
                }
            }

            if ($buffer) {
                DB::table('ri_course_ancestor_stats')->upsert(
                    $buffer,
                    [
                        'year',
                        'jyo_cd',
                        'course_type',
                        'turn_direction',
                        'course_detail',
                        'distance',
                        'grade_group',
                        'ancestor_mode',
                        'ancestor_id',
                    ],
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

                $totalUpserted += count($buffer);
            }

            $elapsed = round(microtime(true) - $startAt, 2);

            Log::info('[CourseAnalyze][ANCESTOR] year done', [
                'year'      => $year,
                'rows'      => $count,
                'seconds'   => $elapsed,
            ]);

            if (app()->runningInConsole()) {
                echo "[ANCESTOR] {$year} done ({$elapsed}s)\n";
            }
        }

        return $totalUpserted;
    }

    /**
     * インブリード（ri_inbreed_ratio）ベースの年次集計
     *
     * - 年ごとに1行ずつ保存
     * - 同一キー（year + course + ancestor）では必ず UPDATE
     * - ancestor_mode は現時点ではラベル用途のみ
     */
    protected function aggregateInbreeds(
        string $grade,
        int $fromYear,
        int $toYear,
        ?string $jyo,
        array $excludeYears,
        string $ancestorMode
    ): int {
        $trackTypeExpr     = $this->trackTypeExpression();
        $turnDirectionExpr = $this->turnDirectionExpression();
        $courseDetailExpr  = $this->courseDetailExpression();

        $totalUpserted = 0;

        // 年ごとにループ
        for ($year = $fromYear; $year <= $toYear; $year++) {

            if (in_array($year, $excludeYears, true)) {
                continue;
            }

            $startAt = microtime(true);

            Log::info('[CourseAnalyze][INBREED] year start', [
                'year'          => $year,
                'jyo'           => $jyo,
                'grade'         => $grade,
                'ancestor_mode' => $ancestorMode,
            ]);

            /**
             * ベースクエリ
             */
            $query = DB::table('ri_race as r')
                ->join('ri_uma_race as ur', 'ur.race_key', '=', 'r.race_key')
                ->join('ri_inbreed_ratio as ir', 'ir.horse_id', '=', 'ur.KettoNum')
                ->where('r.Year', $year)
                ->whereNotNull('ur.KakuteiJyuni')
                ->where('ur.KakuteiJyuni', '>', 0);

            // 競馬場（05 / 5 両対応）
            if ($jyo) {
                $query->whereIn('r.JyoCD', [
                    ltrim($jyo, '0'),
                    str_pad($jyo, 2, '0', STR_PAD_LEFT),
                ]);
            }

            // グレード条件
            $this->applyGradeFilter($query, $grade);

            /**
             * SELECT & GROUP BY
             * ※ 集計ロジックは ancestor と完全に同型
             */
            $cursor = $query->selectRaw("
                ? as year,
                LPAD(r.JyoCD, 2, '0') as jyo_cd,

                CASE
                    WHEN r.TrackCD IN ('10','11','12','13','14','15','16','17','18','19','20','21','22') THEN 'TURF'
                    WHEN r.TrackCD IN ('23','24','25','26','27','28','29') THEN 'DIRT'
                    WHEN r.TrackCD BETWEEN '51' AND '59' THEN 'STEEP'
                    ELSE 'UNKNOWN'
                END as course_type,

                {$turnDirectionExpr} as turn_direction,
                {$courseDetailExpr} as course_detail,

                CAST(r.Kyori AS UNSIGNED) as distance,

                ? as grade_group,
                ? as ancestor_mode,

                COALESCE(ir.ancestor_id, '(UNKNOWN)')   as ancestor_id,
                COALESCE(ir.ancestor_name, '(不明)')    as ancestor_name,

                COUNT(*) as start_count,
                SUM(CASE WHEN ur.KakuteiJyuni = 1 THEN 1 ELSE 0 END) as win_count,
                SUM(CASE WHEN ur.KakuteiJyuni <= 2 THEN 1 ELSE 0 END) as place_count,
                SUM(CASE WHEN ur.KakuteiJyuni <= 3 THEN 1 ELSE 0 END) as show_count,
                SUM(CASE WHEN ur.KakuteiJyuni <= 5 THEN 1 ELSE 0 END) as board_count,
                SUM(CASE WHEN ur.KakuteiJyuni >  5 THEN 1 ELSE 0 END) as out_of_board_count,
                AVG(ir.blood_share_sum) as avg_blood_share
            ", [
                $year,
                $grade,
                $ancestorMode,
            ])
            ->groupByRaw("
                LPAD(r.JyoCD, 2, '0'),
                {$trackTypeExpr},
                {$turnDirectionExpr},
                {$courseDetailExpr},
                CAST(r.Kyori AS UNSIGNED),
                ir.ancestor_id,
                ir.ancestor_name
            ")
            ->orderBy('jyo_cd')   // lazy() には必須
            ->lazy(2000);

            /**
             * streaming upsert
             */
            $buffer   = [];
            $now      = now();
            $processed = 0;

            foreach ($cursor as $r) {
                $processed++;

                $buffer[] = [
                    'year'               => $r->year,
                    'jyo_cd'             => $r->jyo_cd,
                    'course_type'        => $r->course_type,
                    'turn_direction'     => $r->turn_direction,
                    'course_detail'      => $r->course_detail,
                    'distance'           => (int)$r->distance,
                    'grade_group'        => $r->grade_group,
                    'ancestor_mode'      => $r->ancestor_mode,
                    'ancestor_id'        => $r->ancestor_id,
                    'ancestor_name'      => $r->ancestor_name,
                    'start_count'        => (int)$r->start_count,
                    'win_count'          => (int)$r->win_count,
                    'place_count'        => (int)$r->place_count,
                    'show_count'         => (int)$r->show_count,
                    'board_count'        => (int)$r->board_count,
                    'out_of_board_count' => (int)$r->out_of_board_count,
                    'avg_blood_share'    => $r->avg_blood_share,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];

                // 進捗ログ（1万件ごと）
                if ($processed % 10000 === 0) {
                    Log::info('[CourseAnalyze][INBREED] progress', [
                        'year'      => $year,
                        'processed' => $processed,
                    ]);

                    if (app()->runningInConsole()) {
                        echo "[INBREED] {$year} processed: {$processed}\n";
                    }
                }

                // バルク upsert
                if (count($buffer) >= 3000) {
                    DB::table('ri_course_inbreed_stats')->upsert(
                        $buffer,
                        [
                            'year',
                            'jyo_cd',
                            'course_type',
                            'turn_direction',
                            'course_detail',
                            'distance',
                            'grade_group',
                            'ancestor_mode',
                            'ancestor_id',
                        ],
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

                    $totalUpserted += count($buffer);
                    $buffer = [];
                }
            }

            // 残り分
            if (!empty($buffer)) {
                DB::table('ri_course_inbreed_stats')->upsert(
                    $buffer,
                    [
                        'year',
                        'jyo_cd',
                        'course_type',
                        'turn_direction',
                        'course_detail',
                        'distance',
                        'grade_group',
                        'ancestor_mode',
                        'ancestor_id',
                    ],
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

                $totalUpserted += count($buffer);
            }

            $elapsed = round(microtime(true) - $startAt, 2);

            Log::info('[CourseAnalyze][INBREED] year done', [
                'year'    => $year,
                'rows'    => $processed,
                'seconds' => $elapsed,
            ]);

            if (app()->runningInConsole()) {
                echo "[INBREED] {$year} done ({$elapsed}s)\n";
            }
        }

        return $totalUpserted;
    }


}
