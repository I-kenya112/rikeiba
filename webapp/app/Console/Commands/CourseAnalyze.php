<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CourseAnalyzeService;

class CourseAnalyze extends Command
{
    /**
     * artisan コマンドの署名（＝コマンド名と引数オプション）
     */
    protected $signature = 'course:analyze
        {--mode=ALL : KEITO|ANCESTOR|INBREED|ALL}
        {--grade=ALL : ALL|G1|G2|G3|OP|COND}
        {--from= : 期間開始 (YYYY-MM-DD)}
        {--to= : 期間終了 (YYYY-MM-DD)}
        {--course= : カンマ区切り course_key フィルタ}
        {--ancestor=ALL : ALL|SIRE|DAMSIRE}
        {--soft : 緩評価（掲示板加点）を有効化}
        {--limitYears=0 : from未指定なら直近N年だけ対象}
        {--ancestor_mode=ALL : ALL|F|M|FM}
        {--excludeCurrentYear : 今年のデータを除外}
        {--excludeYears= : カンマ区切りで除外したい年を指定（例：2020,2021）}';

    /**
     * コマンド説明
     */
    protected $description = 'コース別血統傾向の集計（KEITO/祖先/INBREED、厳・緩モード対応）';

    public function handle(CourseAnalyzeService $svc)
    {
        $opts = [
            'mode'        => $this->option('mode'),
            'grade'       => $this->option('grade'),
            'from'        => $this->option('from'),
            'to'          => $this->option('to'),
            'course'      => $this->option('course'),
            'ancestor'    => $this->option('ancestor'),
            'soft'        => $this->option('soft'),
            'limitYears'  => $this->option('limitYears'),
            'ancestor_mode' => $this->option('ancestor_mode'),
        ];

        $count = $svc->run($opts);

        $this->info("✅ 集計完了: {$count} rows upserted.");

        return self::SUCCESS;
    }
}
