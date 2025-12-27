<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CourseAnalyzeService;

class CourseAnalyze extends Command
{
    protected $signature = 'course:analyze
        {--mode=ALL : ANCESTOR|INBREED|ALL}
        {--jyo= : 競馬場コード（例: 05）。未指定なら全場}
        {--from= : 期間開始 (YYYY or YYYY-MM-DD)}
        {--to= : 期間終了 (YYYY or YYYY-MM-DD)}
        {--excludeYears= : 除外したい年（例：2021,2022）}
        {--grade=ALL : ALL|G1|G2|G3|OP|COND}
        {--ancestor_mode=ALL : ALL|F|M|FM}
    ';

    protected $description = 'コース別血統・インブリード傾向を一括集計する';

    public function handle(CourseAnalyzeService $svc)
    {
        $this->info('🔥 CourseAnalyze handle() START');

        $opts = [
            'mode'          => $this->option('mode'),
            'jyo'           => $this->option('jyo'),
            'from'          => $this->option('from'),
            'to'            => $this->option('to'),
            'excludeYears'  => $this->option('excludeYears'),
            'grade'         => $this->option('grade'),
            'ancestor_mode' => $this->option('ancestor_mode'),
        ];

        $this->info('🔥 OPTIONS = ' . json_encode($opts));

        $count = $svc->run($opts);

        $this->info("✅ 集計完了: {$count} rows upserted.");

        return self::SUCCESS;
    }

}
