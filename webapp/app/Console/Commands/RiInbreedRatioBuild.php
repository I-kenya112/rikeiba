<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RiInbreedRatioService;

class RiInbreedRatioBuild extends Command
{
    /**
     * コマンド名
     *
     * @var string
     */
    protected $signature = 'inbreed:build {--horse_id=} {--year=}';

    /**
     * 説明
     *
     * @var string
     */
    protected $description = 'ri_pedigree からインブリード比率を計算し、ri_inbreed_ratio に保存する';

    protected RiInbreedRatioService $service;

    /**
     * コンストラクタ
     */
    public function __construct(RiInbreedRatioService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * コマンド実行
     */
    public function handle(): int
    {
        $horseId = $this->option('horse_id');

        $this->info('インブリード比率計算を開始します。');
        $this->service->build($horseId);
        $this->info('✅ インブリード比率の計算が完了しました。');

        return Command::SUCCESS;
    }
}
