<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BuildRiCourses extends Command
{
    protected $signature = 'ri:courses:build';

    protected $description = 'ri_course_ancestor_stats から ri_courses を自動生成する';

    public function handle()
    {
        $this->info('▶ ri_courses build start');

        // 競馬場コード → 名称
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

        // コース構造ラベル
        $detailLabelMap = [
            'OUTER'     => '外回り',
            'INNER'     => '内回り',
            'INNER_2'   => '内回り',
            'NORMAL'    => '通常',
            'UNKNOWN'   => '通常',
            'STRAIGHT'  => '直線',
            'IN_TO_OUT' => '特殊',
            'OUT_TO_IN' => '特殊',
        ];

        // 元データを distinct で取得
        $rows = DB::table('ri_course_ancestor_stats')
            ->select(
                'jyo_cd',
                'course_type',
                'distance',
                'turn_direction',
                'course_detail'
            )
            ->distinct()
            ->get();

        $count = 0;

        foreach ($rows as $r) {

            $jyoCd = str_pad($r->jyo_cd, 2, '0', STR_PAD_LEFT);

            $jyoName = $placeMap[$jyoCd] ?? $jyoCd;

            $courseTypeLabel = match ($r->course_type) {
                'TURF' => '芝',
                'DIRT' => 'ダート',
                default => $r->course_type,
            };

            // UI 用キー（内外回りをまとめる）
            $displayGroupKey = "{$jyoCd}-{$r->course_type}-{$r->distance}";

            // 内部キー（完全一致）
            $courseKey = implode('-', [
                $jyoCd,
                $r->course_type,
                $r->distance,
                $r->turn_direction,
                $r->course_detail,
            ]);

            $detailLabel = $detailLabelMap[$r->course_detail] ?? $r->course_detail;

            DB::table('ri_courses')->updateOrInsert(
                ['course_key' => $courseKey],
                [
                    'display_group_key'   => $displayGroupKey,
                    'jyo_cd'              => $jyoCd,
                    'jyo_name'            => $jyoName,
                    'course_type'         => $r->course_type,
                    'course_type_label'   => $courseTypeLabel,
                    'distance'            => $r->distance,
                    'turn_direction'      => $r->turn_direction,
                    'course_detail'       => $r->course_detail,
                    'detail_label'        => $detailLabel,
                    'is_active'           => 1,
                    'updated_at'          => now(),
                    'created_at'          => now(),
                ]
            );

            $count++;
        }

        $this->info("✔ ri_courses build finished ({$count} records)");
    }
}
