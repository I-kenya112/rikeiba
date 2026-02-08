<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\RiHansyokuBloodline;

class RiCourseAnalyzeService
{
    /**
     * エントリポイント
     */
    public function run(array $opts): int
    {
        $mode         = $opts['mode'] ?? 'ALL';
        $grade        = $opts['grade'] ?? 'ALL';
        $ancestorMode = $opts['ancestor_mode'] ?? 'ALL';

        [$fromYear, $toYear] = $this->resolveYears(
            $opts['from'] ?? null,
            $opts['to']   ?? null
        );

        $courses = $this->fetchTargetCourses($opts);

        $total = 0;

        foreach ($courses as $course) {
            Log::info('[RiCourseAnalyze] course start', [
                'course_key' => $course->course_key,
            ]);

            if ($mode === 'ALL' || $mode === 'ANCESTOR') {
                $total += $this->analyzeAncestorsForCourse(
                    course: $course,
                    fromYear: $fromYear,
                    toYear: $toYear,
                    grade: $grade,
                    ancestorMode: $ancestorMode
                );
            }
            if ($mode === 'ALL' || $mode === 'STALLION') {
                $total += $this->analyzeStallionsForCourse(
                    course: $course,
                    fromYear: $fromYear,
                    toYear: $toYear,
                    grade: $grade,
                    ancestorMode: $ancestorMode
                );
            }
            if ($mode === 'ALL' || $mode === 'INBREED') {
                $total += $this->analyzeInbreedsForCourse(
                    course: $course,
                    fromYear: $fromYear,
                    toYear: $toYear,
                    grade: $grade
                );
            }

            if ($mode === 'ALL' || $mode === 'LINE_COMBO') {
                $total += $this->analyzeLineComboForCourse(
                    course: $course,
                    fromYear: $fromYear,
                    toYear: $toYear,
                    grade: $grade
                );
            }
        }

        return $total;
    }

    /**
     * 年範囲解決
     */
    protected function resolveYears(?string $from, ?string $to): array
    {
        $parse = fn($v) =>
        $v ? (int)preg_replace('/[^0-9]/', '', substr($v, 0, 4)) : null;

        $fromYear = $parse($from);
        $toYear   = $parse($to);

        if ($fromYear && !$toYear) {
            $toYear = $fromYear;
        }

        if (!$fromYear && !$toYear) {
            $year = (int)Carbon::now()->year;
            return [$year, $year];
        }

        if ($fromYear > $toYear) {
            [$fromYear, $toYear] = [$toYear, $fromYear];
        }

        return [$fromYear, $toYear];
    }

    /**
     * 対象コース取得（ri_courses が唯一の真実）
     */
    protected function fetchTargetCourses(array $opts)
    {
        $q = DB::table('ri_courses')
            ->where('is_active', 1);

        if (!empty($opts['course'])) {
            $q->where('course_key', $opts['course']);
        }

        if (!empty($opts['jyo'])) {
            $q->where('jyo_cd', str_pad($opts['jyo'], 2, '0', STR_PAD_LEFT));
        }

        return $q->orderBy('course_key')->get();
    }

    /**
     * グレード条件を適用
     */
    protected function applyGradeFilter($query, string $grade): void
    {
        switch (strtoupper($grade)) {
            case 'G1':
                $query->whereIn('r.GradeCD', ['A', 'F']);
                break;

            case 'G2':
                $query->whereIn('r.GradeCD', ['B', 'G']);
                break;

            case 'G3':
                $query->whereIn('r.GradeCD', ['C', 'H']);
                break;

            case 'GRADE':
                // 重賞（G1～G3 + 障害G）
                $query->whereIn('r.GradeCD', ['A', 'B', 'C', 'F', 'G', 'H']);
                break;

            case 'OP':
                // OP以上（重賞 + OP特別）
                $query->whereIn('r.GradeCD', ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);
                break;

            case 'COND':
                // 条件戦
                $query->where(function ($q) {
                    $q->where('r.GradeCD', '_')
                        ->orWhereNull('r.GradeCD');
                });
                break;

            case 'ALL':
            default:
                // 絞り込みなし
                break;
        }
    }

    /**
     * ancestor_mode による祖先絞り込み（ri_pedigree / SELF除外対応）
     */
    protected function applyAncestorModeFilter($query, string $ancestorMode): void
    {
        // ★ SELF を確実に除外（最重要）
        $query->where('p.generation', '>=', 1);

        switch (strtoupper($ancestorMode)) {
            case 'F':
                $query->where('p.relation_type', 'F');
                break;

            case 'M':
                $query->where('p.relation_type', 'M');
                break;

            case 'FM':
                $query->whereIn('p.relation_type', ['F', 'M']);
                break;

            case 'ALL':
            default:
                // generation >= 1 のみ
                break;
        }
    }

    /**
     * stallion 対象定義
     */
    protected function resolveStallionTargets(string $ancestorMode): array
    {
        switch (strtoupper($ancestorMode)) {
            case 'F':
                return ['F'];   // 父

            case 'MF':
                return ['MF'];  // 母父

            case 'ALL':
            default:
                return ['F','MF'];
        }
    }

    /**
     * 馬場状態定義
     */
    protected function resolveTrackConditions(object $course): array
    {
        // 芝 or ダートで参照カラムを切り替える
        $column = $course->course_type === 'TURF'
            ? 'r.SibaBabaCD'
            : 'r.DirtBabaCD';
        return [
            'ALL'   => null,                     // 馬場無視
            'GOOD'  => "{$column} = '1'",        // 良
            'YAYA'  => "{$column} = '2'",        // 稍重
            'HEAVY' => "{$column} = '3'",        // 重
            'BAD'   => "{$column} = '4'",        // 不良
        ];
    }

    /**
     * 祖先分析（1コース単位）
     */
    protected function analyzeAncestorsForCourse(
        object $course,
        int $fromYear,
        int $toYear,
        string $grade,
        string $ancestorMode
    ): int {
        $total = 0;

        $trackConditions = $this->resolveTrackConditions($course);

        for ($year = $fromYear; $year <= $toYear; $year++) {

            foreach ($trackConditions as $trackKey => $trackWhere) {

                $startAt = microtime(true);

                // --- ベースクエリ ---
                $query = DB::table('ri_race as r')
                    ->join('ri_uma_race as ur', 'ur.race_key', '=', 'r.race_key')
                    ->join('ri_pedigree as p', 'p.horse_id', '=', 'ur.KettoNum')
                    ->where('r.Year', $year)
                    ->whereNotNull('ur.KakuteiJyuni')
                    ->where('ur.KakuteiJyuni', '>', 0)
                    ->where('p.generation', '>=', 1)
                    ->where(function ($q) {
                        $q->whereNotNull('p.ancestor_id_hansyoku')
                        ->orWhereNotNull('p.ancestor_id_uma');
                    })
                    // ★ ri_courses を絶対条件として使用
                    ->where('r.Kyori', $course->distance)
                    ->whereRaw("LPAD(r.JyoCD, 2, '0') = ?", [$course->jyo_cd])
                    ->whereRaw("
                        CASE
                            WHEN r.TrackCD IN ('10','11','12','13','14','15','16','17','18','19','20','21','22') THEN 'TURF'
                            WHEN r.TrackCD IN ('23','24','25','26','27','28','29') THEN 'DIRT'
                            WHEN r.TrackCD BETWEEN '51' AND '59' THEN 'STEEP'
                            ELSE 'UNKNOWN'
                        END = ?
                    ", [$course->course_type])
                    ->whereRaw("
                        CASE
                            WHEN r.TrackCD IN ('10','29') THEN 'STRAIGHT'
                            WHEN r.TrackCD IN ('11','12','13','14','15','16','23','25','27','53') THEN 'LEFT'
                            WHEN r.TrackCD IN ('17','18','19','20','21','22','24','26','28') THEN 'RIGHT'
                            ELSE 'UNKNOWN'
                        END = ?
                    ", [$course->turn_direction])
                    ->whereRaw("
                        CASE
                            WHEN r.TrackCD IN ('12','18','55') THEN 'OUTER'
                            WHEN r.TrackCD IN ('25') THEN 'INNER'
                            WHEN r.TrackCD IN ('13','19','57') THEN 'IN_TO_OUT'
                            WHEN r.TrackCD IN ('14','20','56') THEN 'OUT_TO_IN'
                            WHEN r.TrackCD IN ('15','21','58') THEN 'INNER_2'
                            WHEN r.TrackCD IN ('16','22','59') THEN 'OUTER_2'
                            WHEN r.TrackCD IN ('26') THEN 'OUTER'
                            ELSE 'SINGLE'
                        END = ?
                    ", [$course->course_detail]);

                // --- 馬場状態条件 ---
                if ($trackWhere !== null) {
                    $query->whereRaw($trackWhere);
                }
                // --- グレード条件 ---
                $this->applyGradeFilter($query, $grade);

                // --- ancestor_mode ---
                $this->applyAncestorModeFilter($query, $ancestorMode);

                // --- 集計 ---
                $cursor = $query
                    ->selectRaw("
                        ? as year,
                        ? as course_key,
                        ? as grade_group,
                        ? as ancestor_mode,
                        ? as track_condition,

                        COALESCE(p.ancestor_id_hansyoku, p.ancestor_id_uma) as ancestor_id,
                        COALESCE(p.ancestor_name, '(不明)') as ancestor_name,

                        COUNT(*) as start_count,
                        SUM(ur.KakuteiJyuni = 1) as win_count,
                        SUM(ur.KakuteiJyuni <= 2) as place_count,
                        SUM(ur.KakuteiJyuni <= 3) as show_count,
                        SUM(ur.KakuteiJyuni <= 5) as board_count,
                        SUM(ur.KakuteiJyuni >  5) as out_of_board_count,
                        AVG(p.blood_share) as avg_blood_share
                    ", [
                        $year,
                        $course->course_key,
                        $grade,
                        $ancestorMode,
                        $trackKey,
                        ])
                    ->groupBy('ancestor_id', 'ancestor_name')
                    ->orderBy('ancestor_id')
                    ->lazy(2000);

                // --- upsert ---
                $buffer = [];
                $now = now();

                foreach ($cursor as $r) {
                    $bloodline = $this->resolveBloodline($r->ancestor_id);

                    $buffer[] = [
                        'year'               => $r->year,
                        'course_key'         => $r->course_key,
                        'display_group_key'  => $course->display_group_key, // ← 追加
                        'grade_group'        => $r->grade_group,
                        'ancestor_mode'      => $r->ancestor_mode,
                        'track_condition'    => $trackKey,
                        'ancestor_id'        => $r->ancestor_id,
                        'ancestor_name'      => $r->ancestor_name,
                        'line_key'           => $bloodline['line_key'],
                        'line_key_detail'    => $bloodline['line_key_detail'],
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

                    if (count($buffer) >= 500) {
                        DB::table('ri_course_ancestor')->upsert(
                            $buffer,
                            [
                                'year',
                                'course_key',
                                'grade_group',
                                'ancestor_mode',
                                'track_condition',
                                'ancestor_id',
                            ],
                            [
                                'ancestor_name',
                                'line_key',
                                'line_key_detail',
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

                        $total += count($buffer);
                        $buffer = [];
                    }
                }

                if ($buffer) {
                    DB::table('ri_course_ancestor')->upsert(
                        $buffer,
                        [
                            'year',
                            'course_key',
                            'grade_group',
                            'ancestor_mode',
                            'track_condition',
                            'ancestor_id',
                        ],
                        [
                            'ancestor_name',
                            'line_key',
                            'line_key_detail',
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

                    $total += count($buffer);
                }

                $elapsed = round(microtime(true) - $startAt, 2);

                Log::info('[RiCourseAnalyze][ANCESTOR]', [
                    'course_key' => $course->course_key,
                    'year'       => $year,
                    'track_condition' => $trackKey,
                    'rows'       => $total,
                    'seconds'    => $elapsed,
                ]);

                if (app()->runningInConsole()) {
                    echo "[ANCESTOR] {$course->course_key} {$year} ({$elapsed}s)\n";
                }
            }
        }
        return $total;
    }

    /**
     * 種牡馬分析（1コース単位）
     */
    protected function analyzeStallionsForCourse(
        object $course,
        int $fromYear,
        int $toYear,
        string $grade,
        string $ancestorMode
    ): int {
        $total = 0;

        $trackConditions = $this->resolveTrackConditions($course);
        $stallionPaths   = $this->resolveStallionTargets($ancestorMode);

        for ($year = $fromYear; $year <= $toYear; $year++) {

            foreach ($trackConditions as $trackKey => $trackWhere) {

                $startAt = microtime(true);

                $query = DB::table('ri_race as r')
                    ->join('ri_uma_race as ur', 'ur.race_key', '=', 'r.race_key')
                    ->join('ri_pedigree as p', 'p.horse_id', '=', 'ur.KettoNum')
                    ->where('r.Year', $year)
                    ->whereNotNull('ur.KakuteiJyuni')
                    ->where('ur.KakuteiJyuni', '>', 0)

                    // stallion 限定
                    ->whereIn('p.relation_path', $stallionPaths)
                    ->where('p.generation', '<=', 2)

                    // ★ ancestor_id が必ず取れるものだけ
                    ->where(function ($q) {
                        $q->whereNotNull('p.ancestor_id_hansyoku')
                        ->orWhereNotNull('p.ancestor_id_uma');
                    })

                    // コース条件
                    ->where('r.Kyori', $course->distance)
                    ->whereRaw("LPAD(r.JyoCD, 2, '0') = ?", [$course->jyo_cd])
                    ->whereRaw("
                        CASE
                            WHEN r.TrackCD IN ('10','11','12','13','14','15','16','17','18','19','20','21','22') THEN 'TURF'
                            WHEN r.TrackCD IN ('23','24','25','26','27','28','29') THEN 'DIRT'
                            ELSE 'UNKNOWN'
                        END = ?
                    ", [$course->course_type]);

                if ($trackWhere !== null) {
                    $query->whereRaw($trackWhere);
                }

                $this->applyGradeFilter($query, $grade);

                $cursor = $query
                    ->selectRaw("
                        ? as year,
                        ? as course_key,
                        ? as grade_group,
                        ? as ancestor_mode,
                        ? as track_condition,

                        COALESCE(p.ancestor_id_hansyoku, p.ancestor_id_uma) as ancestor_id,
                        p.ancestor_name as ancestor_name,
                        p.line_key,
                        p.line_key_detail,

                        COUNT(*) as start_count,
                        SUM(ur.KakuteiJyuni = 1) as win_count,
                        SUM(ur.KakuteiJyuni <= 2) as place_count,
                        SUM(ur.KakuteiJyuni <= 3) as show_count,
                        SUM(ur.KakuteiJyuni <= 5) as board_count,
                        SUM(ur.KakuteiJyuni >  5) as out_of_board_count,
                        AVG(p.blood_share) as avg_blood_share
                    ", [
                        $year,
                        $course->course_key,
                        $grade,
                        $ancestorMode,
                        $trackKey,
                    ])
                    ->groupBy('ancestor_id','ancestor_name','line_key','line_key_detail')
                    ->orderBy('ancestor_id')
                    ->lazy(500);

                $buffer = [];
                $now = now();

                foreach ($cursor as $r) {
                    $buffer[] = [
                        'year'               => $r->year,
                        'course_key'         => $r->course_key,
                        'display_group_key'  => $course->display_group_key,
                        'grade_group'        => $r->grade_group,
                        'ancestor_mode'      => $r->ancestor_mode,
                        'track_condition'    => $r->track_condition,
                        'ancestor_id'        => $r->ancestor_id,
                        'ancestor_name'      => $r->ancestor_name,
                        'line_key'           => $r->line_key,
                        'line_key_detail'    => $r->line_key_detail,
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
                }

                if ($buffer) {
                    DB::table('ri_course_stallion')->upsert(
                        $buffer,
                        [
                            'year',
                            'course_key',
                            'grade_group',
                            'ancestor_mode',
                            'track_condition',
                            'ancestor_id',
                        ],
                        [
                            'ancestor_name',
                            'line_key',
                            'line_key_detail',
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
                    $total += count($buffer);
                }

                $elapsed = round(microtime(true) - $startAt, 2);

                Log::info('[RiCourseAnalyze][STALLION]', [
                    'course_key' => $course->course_key,
                    'year'       => $year,
                    'track'      => $trackKey,
                    'rows'       => count($buffer),
                    'seconds'    => $elapsed,
                ]);

                if (app()->runningInConsole()) {
                    echo "[STALLION] {$course->course_key} {$year} {$trackKey} ({$elapsed}s)\n";
                }
            }
        }

        return $total;
    }

    /**
     * インブリード分析（1コース単位）
     */
    protected function analyzeInbreedsForCourse(
        object $course,
        int $fromYear,
        int $toYear,
        string $grade
    ): int {
        $total = 0;

        $trackConditions = $this->resolveTrackConditions($course);

        for ($year = $fromYear; $year <= $toYear; $year++) {

            foreach ($trackConditions as $trackKey => $trackWhere) {

                $startAt = microtime(true);

                // --- ベースクエリ ---
                $query = DB::table('ri_race as r')
                    ->join('ri_uma_race as ur', 'ur.race_key', '=', 'r.race_key')
                    ->join('ri_inbreed_ratio as ir', 'ir.horse_id', '=', 'ur.KettoNum')
                    ->where('r.Year', $year)
                    ->whereNotNull('ur.KakuteiJyuni')
                    ->where('ur.KakuteiJyuni', '>', 0)

                    // ★ 祖先IDが取れないものは除外（NOT NULL制約対策 + データ品質）
                    ->whereNotNull('ir.ancestor_id')

                    // ★ ri_courses を絶対条件として使用
                    ->where('r.Kyori', $course->distance)
                    ->whereRaw("LPAD(r.JyoCD,2,'0') = ?", [$course->jyo_cd])

                    ->whereRaw("
                        CASE
                            WHEN r.TrackCD IN ('10','11','12','13','14','15','16','17','18','19','20','21','22') THEN 'TURF'
                            WHEN r.TrackCD IN ('23','24','25','26','27','28','29') THEN 'DIRT'
                            WHEN r.TrackCD BETWEEN '51' AND '59' THEN 'STEEP'
                            ELSE 'UNKNOWN'
                        END = ?
                    ", [$course->course_type])

                    ->whereRaw("
                        CASE
                            WHEN r.TrackCD IN ('10','29') THEN 'STRAIGHT'
                            WHEN r.TrackCD IN ('11','12','13','14','15','16','23','25','27','53') THEN 'LEFT'
                            WHEN r.TrackCD IN ('17','18','19','20','21','22','24','26','28') THEN 'RIGHT'
                            ELSE 'UNKNOWN'
                        END = ?
                    ", [$course->turn_direction])

                    ->whereRaw("
                        CASE
                            WHEN r.TrackCD IN ('12','18','55') THEN 'OUTER'
                            WHEN r.TrackCD IN ('25') THEN 'INNER'
                            WHEN r.TrackCD IN ('13','19','57') THEN 'IN_TO_OUT'
                            WHEN r.TrackCD IN ('14','20','56') THEN 'OUT_TO_IN'
                            WHEN r.TrackCD IN ('15','21','58') THEN 'INNER_2'
                            WHEN r.TrackCD IN ('16','22','59') THEN 'OUTER_2'
                            WHEN r.TrackCD IN ('26') THEN 'OUTER'
                            ELSE 'SINGLE'
                        END = ?
                    ", [$course->course_detail]);

                // --- 馬場状態条件 ---
                if ($trackWhere !== null) {
                    $query->whereRaw($trackWhere);
                }

                // --- グレード条件 ---
                $this->applyGradeFilter($query, $grade);

                // --- 集計 ---
                $cursor = $query
                    ->selectRaw("
                        ? as year,
                        ? as course_key,
                        ? as grade_group,
                        'ALL' as ancestor_mode,
                        ? as track_condition,

                        ir.ancestor_id as ancestor_id,
                        COALESCE(ir.ancestor_name, '(不明)') as ancestor_name,

                        COUNT(*) as start_count,
                        SUM(ur.KakuteiJyuni = 1) as win_count,
                        SUM(ur.KakuteiJyuni <= 2) as place_count,
                        SUM(ur.KakuteiJyuni <= 3) as show_count,
                        SUM(ur.KakuteiJyuni <= 5) as board_count,
                        SUM(ur.KakuteiJyuni >  5) as out_of_board_count,
                        AVG(ir.blood_share_sum) as avg_blood_share
                    ", [
                        $year,
                        $course->course_key,
                        $grade,
                        $trackKey,
                    ])
                    ->groupBy('ancestor_id', 'ancestor_name')
                    ->orderBy('ancestor_id')
                    ->lazy(500);

                // --- upsert ---
                $buffer = [];
                $now = now();
                $written = 0;

                foreach ($cursor as $r) {

                    // ★ line_key / line_key_detail を付与（ancestorと同じ思想）
                    $bloodline = $this->resolveBloodline($r->ancestor_id);

                    $buffer[] = [
                        'year'              => $r->year,
                        'course_key'        => $r->course_key,
                        'display_group_key' => $course->display_group_key,
                        'grade_group'       => $r->grade_group,
                        'ancestor_mode'     => 'ALL',
                        'track_condition'   => $trackKey,

                        'ancestor_id'       => $r->ancestor_id,
                        'ancestor_name'     => $r->ancestor_name,

                        // ※ ri_course_inbreed にカラムがある前提（無ければ2行削除）
                        'line_key'          => $bloodline['line_key'],
                        'line_key_detail'   => $bloodline['line_key_detail'],

                        'start_count'       => (int)$r->start_count,
                        'win_count'         => (int)$r->win_count,
                        'place_count'       => (int)$r->place_count,
                        'show_count'        => (int)$r->show_count,
                        'board_count'       => (int)$r->board_count,
                        'out_of_board_count'=> (int)$r->out_of_board_count,
                        'avg_blood_share'   => $r->avg_blood_share,

                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];

                    if (count($buffer) >= 500) {
                        DB::table('ri_course_inbreed')->upsert(
                            $buffer,
                            ['year','course_key','grade_group','ancestor_mode','track_condition','ancestor_id'],
                            [
                                'ancestor_name',
                                'line_key',
                                'line_key_detail',
                                'start_count','win_count','place_count','show_count','board_count','out_of_board_count',
                                'avg_blood_share','updated_at'
                            ]
                        );
                        $written += count($buffer);
                        $total   += count($buffer);
                        $buffer   = [];
                    }
                }

                if ($buffer) {
                    DB::table('ri_course_inbreed')->upsert(
                        $buffer,
                        ['year','course_key','grade_group','ancestor_mode','track_condition','ancestor_id'],
                        [
                            'ancestor_name',
                            'line_key',
                            'line_key_detail',
                            'start_count','win_count','place_count','show_count','board_count','out_of_board_count',
                            'avg_blood_share','updated_at'
                        ]
                    );
                    $written += count($buffer);
                    $total   += count($buffer);
                }

                $elapsed = round(microtime(true) - $startAt, 2);

                Log::info('[RiCourseAnalyze][INBREED]', [
                    'course_key'       => $course->course_key,
                    'year'             => $year,
                    'track_condition'  => $trackKey,
                    'rows'             => $written,
                    'seconds'          => $elapsed,
                ]);

                if (app()->runningInConsole()) {
                    echo "[INBREED] {$course->course_key} {$year} {$trackKey} ({$elapsed}s)\n";
                }
            }
        }

        return $total;
    }

    /**
     * 父 × 母父 系統コンボ分析（1コース単位）
     */
    protected function analyzeLineComboForCourse(
        object $course,
        int $fromYear,
        int $toYear,
        string $grade
    ): int {
        $total = 0;

        $trackConditions = $this->resolveTrackConditions($course);

        for ($year = $fromYear; $year <= $toYear; $year++) {

            foreach ($trackConditions as $trackKey => $trackWhere) {

                $startAt = microtime(true);

                // --- ベースクエリ ---
                $query = DB::table('ri_race as r')
                    ->join('ri_uma_race as ur', 'ur.race_key', '=', 'r.race_key')

                    // 父
                    ->join('ri_pedigree as p_f', function ($j) {
                        $j->on('p_f.horse_id', '=', 'ur.KettoNum')
                        ->where('p_f.relation_path', 'F')
                        ->where('p_f.generation', 1)
                        ->whereNotNull('p_f.ancestor_id_hansyoku')
                        ->whereNotNull('p_f.line_key');
                    })

                    // 母父
                    ->join('ri_pedigree as p_mf', function ($j) {
                        $j->on('p_mf.horse_id', '=', 'ur.KettoNum')
                        ->where('p_mf.relation_path', 'MF')
                        ->where('p_mf.generation', 2)
                        ->whereNotNull('p_mf.ancestor_id_hansyoku')
                        ->whereNotNull('p_mf.line_key');
                    })

                    ->where('r.Year', $year)
                    ->whereNotNull('ur.KakuteiJyuni')
                    ->where('ur.KakuteiJyuni', '>', 0)

                    // --- コース条件（stallionと完全一致） ---
                    ->where('r.Kyori', $course->distance)
                    ->whereRaw("LPAD(r.JyoCD, 2, '0') = ?", [$course->jyo_cd])
                    ->whereRaw("
                        CASE
                            WHEN r.TrackCD IN ('10','11','12','13','14','15','16','17','18','19','20','21','22') THEN 'TURF'
                            WHEN r.TrackCD IN ('23','24','25','26','27','28','29') THEN 'DIRT'
                            ELSE 'UNKNOWN'
                        END = ?
                    ", [$course->course_type]);

                // --- 馬場状態 ---
                if ($trackWhere !== null) {
                    $query->whereRaw($trackWhere);
                }

                // --- グレード ---
                $this->applyGradeFilter($query, $grade);

                // --- 集計 ---
                $cursor = $query
                    ->selectRaw("
                        ? as year,
                        ? as course_key,
                        ? as grade_group,
                        ? as track_condition,

                        p_f.ancestor_id_hansyoku as father_ancestor_id,
                        p_f.ancestor_name as father_name,
                        p_f.line_key as father_line_key,
                        p_f.line_key_detail as father_line_key_detail,

                        p_mf.ancestor_id_hansyoku as mf_ancestor_id,
                        p_mf.ancestor_name as mf_name,
                        p_mf.line_key as mf_line_key,
                        p_mf.line_key_detail as mf_line_key_detail,

                        COUNT(*) as start_count,
                        SUM(ur.KakuteiJyuni = 1) as win_count,
                        SUM(ur.KakuteiJyuni <= 2) as place_count,
                        SUM(ur.KakuteiJyuni <= 3) as show_count,
                        SUM(ur.KakuteiJyuni <= 5) as board_count,
                        SUM(ur.KakuteiJyuni >  5) as out_of_board_count
                    ", [
                        $year,
                        $course->course_key,
                        $grade,
                        $trackKey,
                    ])
                    ->groupBy(
                        'father_ancestor_id',
                        'father_name',
                        'father_line_key',
                        'father_line_key_detail',
                        'mf_ancestor_id',
                        'mf_name',
                        'mf_line_key',
                        'mf_line_key_detail'
                    )
                    ->orderByDesc('start_count')
                    ->lazy(500);

                // --- upsert ---
                $buffer  = [];
                $now     = now();
                $written = 0;

                foreach ($cursor as $r) {
                    $buffer[] = [
                        'year'                   => $r->year,
                        'course_key'             => $r->course_key,
                        'display_group_key'      => $course->display_group_key,
                        'grade_group'            => $r->grade_group,
                        'track_condition'        => $r->track_condition,

                        'father_ancestor_id'     => $r->father_ancestor_id,
                        'father_name'            => $r->father_name,
                        'father_line_key'        => $r->father_line_key,
                        'father_line_key_detail' => $r->father_line_key_detail,

                        'mf_ancestor_id'         => $r->mf_ancestor_id,
                        'mf_name'                => $r->mf_name,
                        'mf_line_key'            => $r->mf_line_key,
                        'mf_line_key_detail'     => $r->mf_line_key_detail,

                        'start_count'            => (int)$r->start_count,
                        'win_count'              => (int)$r->win_count,
                        'place_count'            => (int)$r->place_count,
                        'show_count'             => (int)$r->show_count,
                        'board_count'            => (int)$r->board_count,
                        'out_of_board_count'     => (int)$r->out_of_board_count,

                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ];

                    if (count($buffer) >= 500) {
                        DB::table('ri_course_line_combo')->upsert(
                            $buffer,
                            [
                                'year',
                                'course_key',
                                'grade_group',
                                'track_condition',
                                'father_ancestor_id',
                                'mf_ancestor_id',
                            ],
                            [
                                'father_name',
                                'father_line_key',
                                'father_line_key_detail',
                                'mf_name',
                                'mf_line_key',
                                'mf_line_key_detail',
                                'start_count',
                                'win_count',
                                'place_count',
                                'show_count',
                                'board_count',
                                'out_of_board_count',
                                'updated_at',
                            ]
                        );
                        $written += count($buffer);
                        $total   += count($buffer);
                        $buffer   = [];
                    }
                }

                if ($buffer) {
                    DB::table('ri_course_line_combo')->upsert(
                        $buffer,
                        [
                            'year',
                            'course_key',
                            'grade_group',
                            'track_condition',
                            'father_ancestor_id',
                            'mf_ancestor_id',
                        ],
                        [
                            'father_name',
                            'father_line_key',
                            'father_line_key_detail',
                            'mf_name',
                            'mf_line_key',
                            'mf_line_key_detail',
                            'start_count',
                            'win_count',
                            'place_count',
                            'show_count',
                            'board_count',
                            'out_of_board_count',
                            'updated_at',
                        ]
                    );
                    $written += count($buffer);
                    $total   += count($buffer);
                }

                $elapsed = round(microtime(true) - $startAt, 2);

                Log::info('[RiCourseAnalyze][LINE_COMBO]', [
                    'course_key'      => $course->course_key,
                    'year'            => $year,
                    'track_condition' => $trackKey,
                    'rows'            => $written,
                    'seconds'         => $elapsed,
                ]);

                if (app()->runningInConsole()) {
                    echo "[LINE-COMBO] {$course->course_key} {$year} {$trackKey} ({$elapsed}s)\n";
                }
            }
        }

        return $total;
    }

    /**
     * hansyoku_num => [line_key, line_key_detail]
     */
    protected array $bloodlineMap = [];

    protected function resolveBloodline(?string $hansyokuNum): array
    {
        if (!$hansyokuNum) {
            return ['line_key' => null, 'line_key_detail' => null];
        }

        if (!$this->bloodlineMap) {
            $this->loadBloodlines();
        }

        return $this->bloodlineMap[$hansyokuNum]
            ?? ['line_key' => null, 'line_key_detail' => null];
    }

    protected function loadBloodlines(): void
    {
        $this->bloodlineMap = [];

        RiHansyokuBloodline::query()
            ->select(['hansyoku_num', 'line_key', 'line_key_detail'])
            ->whereNotNull('line_key')
            ->chunk(5000, function ($rows) {
                foreach ($rows as $r) {
                    $this->bloodlineMap[$r->hansyoku_num] = [
                        'line_key'        => $r->line_key,
                        'line_key_detail' => $r->line_key_detail,
                    ];
                }
            });
    }
}
