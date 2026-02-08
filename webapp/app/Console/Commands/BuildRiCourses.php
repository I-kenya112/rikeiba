<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BuildRiCourses extends Command
{
    protected $signature = 'ri:courses:build';
    protected $description = 'Build ri_courses from ri_course and ri_track_code_definitions';

    public function handle()
    {
        $this->info('Building ri_courses...');

        DB::table('ri_courses')->truncate();

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

        // ri_course × TrackCD辞書
        $rows = DB::table('ri_course as c')
            ->join(
                'ri_track_code_definitions as t',
                'c.TrackCD',
                '=',
                't.track_cd'
            )
            ->select(
                'c.JyoCD',
                'c.Kyori',
                'c.TrackCD',
                't.course_type',
                't.course_type_label',
                't.turn_direction',
                't.course_layout',
                't.layout_label'
            )
            ->distinct()
            ->get();

        foreach ($rows as $r) {
            $jyoCd = str_pad($r->JyoCD, 2, '0', STR_PAD_LEFT);
            $distance = (int)$r->Kyori;

            $courseKey = sprintf(
                '%s-%s-%d-%s-%s',
                $jyoCd,
                $r->course_type,
                $distance,
                $r->turn_direction,
                $r->course_layout
            );

            $displayGroupKey = sprintf(
                '%s-%s-%d',
                $jyoCd,
                $r->course_type,
                $distance
            );

            DB::table('ri_courses')->insert([
                'display_group_key' => $displayGroupKey,
                'course_key'        => $courseKey,

                'jyo_cd'            => $jyoCd,
                'jyo_name'          => $placeMap[$jyoCd] ?? $jyoCd,

                'course_type'       => $r->course_type,
                'course_type_label' => $r->course_type_label,
                'distance'          => $distance,

                'turn_direction'    => $r->turn_direction,
                'course_detail'     => $r->course_layout,
                'detail_label'      => $r->layout_label,

                'is_active'         => 1,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        $this->info('ri_courses build completed!');
        return Command::SUCCESS;
    }
}
