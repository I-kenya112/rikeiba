<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RiCourseAnalyzeService;

class RiCourseAnalyze extends Command
{
    protected $signature = 'ri:course-analyze
        {--mode=ALL : ANCESTOR|INBREED|ALL}
        {--course= : course_key（例: 08-TURF-2200-RIGHT-OUTER）}
        {--jyo= : 競馬場コード（例: 05）}
        {--from= : 開始年（YYYY）}
        {--to= : 終了年（YYYY）}
        {--grade=ALL : ALL|G1|GRADE|OP|COND}
        {--ancestor_mode=ALL : ALL|F|M}
    ';

    protected $description = 'ri_courses を基準にコース別血統・インブリードを分析・再集計する';

    public function handle(RiCourseAnalyzeService $service)
    {
        $this->info('🔥 ri:course:analyze START');

        $opts = [
            'mode'          => strtoupper($this->option('mode')),
            'course'        => $this->option('course'),
            'jyo'           => $this->option('jyo'),
            'from'          => $this->option('from'),
            'to'            => $this->option('to'),
            'grade'         => strtoupper($this->option('grade')),
            'ancestor_mode' => strtoupper($this->option('ancestor_mode')),
        ];

        $this->info('OPTIONS = ' . json_encode($opts, JSON_UNESCAPED_UNICODE));

        $count = $service->run($opts);

        $this->info("✅ ANALYZE DONE: {$count} rows");

        return self::SUCCESS;
    }
}
