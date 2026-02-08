<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RiTrackCodeDefinitionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ri_track_code_definitions')->truncate();

        $rows = [

            // ===== 芝 =====
            ['10','TURF','芝','STRAIGHT','STRAIGHT','直線','平地 芝 直線'],
            ['11','TURF','芝','LEFT','SINGLE','通常','平地 芝 左回り'],
            ['12','TURF','芝','LEFT','OUTER','外回り','平地 芝 左回り 外回り'],
            ['13','TURF','芝','LEFT','INNER','内回り','平地 芝 左回り 内→外'],
            ['14','TURF','芝','LEFT','OUTER','外回り','平地 芝 左回り 外→内'],
            ['15','TURF','芝','LEFT','INNER','内回り','平地 芝 左回り 内2周'],
            ['16','TURF','芝','LEFT','OUTER','外回り','平地 芝 左回り 外2周'],
            ['17','TURF','芝','RIGHT','SINGLE','通常','平地 芝 右回り'],
            ['18','TURF','芝','RIGHT','OUTER','外回り','平地 芝 右回り 外回り'],
            ['19','TURF','芝','RIGHT','INNER','内回り','平地 芝 右回り 内→外'],
            ['20','TURF','芝','RIGHT','OUTER','外回り','平地 芝 右回り 外→内'],
            ['21','TURF','芝','RIGHT','INNER','内回り','平地 芝 右回り 内2周'],
            ['22','TURF','芝','RIGHT','OUTER','外回り','平地 芝 右回り 外2周'],

            // ===== ダート =====
            ['23','DIRT','ダート','LEFT','SINGLE','通常','平地 ダート 左回り'],
            ['24','DIRT','ダート','RIGHT','SINGLE','通常','平地 ダート 右回り'],
            ['25','DIRT','ダート','LEFT','INNER','内回り','平地 ダート 左回り 内回り'],
            ['26','DIRT','ダート','RIGHT','OUTER','外回り','平地 ダート 右回り 外回り'],
            ['29','DIRT','ダート','STRAIGHT','STRAIGHT','直線','平地 ダート 直線'],

            // ===== 障害（今回は解析外だが辞書として保持） =====
            ['51','STEEP','障害','UNKNOWN','SINGLE','通常','障害 芝 襷'],
            ['52','STEEP','障害','UNKNOWN','SINGLE','通常','障害 芝→ダート'],
            ['53','STEEP','障害','LEFT','SINGLE','通常','障害 芝 左'],
            ['54','STEEP','障害','UNKNOWN','SINGLE','通常','障害 芝'],
            ['55','STEEP','障害','UNKNOWN','OUTER','外回り','障害 芝 外回り'],
            ['56','STEEP','障害','UNKNOWN','OUTER','外回り','障害 芝 外→内'],
            ['57','STEEP','障害','UNKNOWN','INNER','内回り','障害 芝 内→外'],
            ['58','STEEP','障害','UNKNOWN','INNER','内回り','障害 芝 内2周'],
            ['59','STEEP','障害','UNKNOWN','OUTER','外回り','障害 芝 外2周'],
        ];

        foreach ($rows as $r) {
            DB::table('ri_track_code_definitions')->insert([
                'track_cd'          => $r[0],
                'course_type'       => $r[1],
                'course_type_label' => $r[2],
                'turn_direction'    => $r[3],
                'course_layout'     => $r[4],
                'layout_label'      => $r[5],
                'description'       => $r[6],
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
