<?php

namespace App\Services;

use App\Models\RiPedigree;
use App\Models\RiInbreedRatio;
use Illuminate\Support\Facades\DB;

class RiInbreedRatioService
{
    /**
     * インブリード比率を算出し、ri_inbreed_ratioテーブルに保存
     *
     * @param string|null $horseId 対象馬ID（指定がなければ全件）
     * @return void
     */
    public function build(?string $horseId = null, ?string $year = null): void
    {
        info('--- RiInbreedRatioService build() START ---');

        if ($horseId) {
            info("単体モード horse_id={$horseId}");
            DB::transaction(fn() => $this->calculateForHorse($horseId));
            return;
        }

        info('全馬処理モード開始');
        $query = DB::table('ri_pedigree')
            ->where('relation_path', '=', 'SELF')
            ->select('horse_id')
            ->distinct();

        if ($year) {
            $query->whereRaw('LEFT(horse_id, 4) = ?', [$year]);
            info("年度指定: {$year}");
        }

        $total = $query->count();
        info("対象件数: {$total}");

        $processed = 0;
        $start = microtime(true);

        // ✅ chunkで安全に逐次処理
        $query->orderBy('horse_id')->chunk(2000, function ($chunk) use (&$processed, $total, $start) {
            $grouped = $chunk->groupBy(fn($row) => substr($row->horse_id, 0, 4));

            foreach ($grouped as $year => $ids) {
                DB::transaction(function () use ($ids) {
                    foreach ($ids as $idObj) {
                        $this->calculateForHorse($idObj->horse_id);
                    }
                });
                info("✅ {$year}年分: {$ids->count()}頭完了");
            }

            $processed += $chunk->count();
            $elapsed = round(microtime(true) - $start, 1);
            info("進捗: {$processed}/{$total}件 ({$elapsed}s経過)");
        });

        $elapsed = round(microtime(true) - $start, 1);
        info("🎯 全処理完了: {$total}件, 所要時間 {$elapsed}秒");
        info('--- RiInbreedRatioService build() END ---');
    }

    /**
     * 単一馬についてインブリード比率を算出
     */
    protected function calculateForHorse(string $horseId): void
    {
        $records = RiPedigree::where('horse_id', $horseId)
            ->select('horse_id', 'horse_name', 'ancestor_id_hansyoku', 'ancestor_name', 'relation_path', 'blood_share')
            ->get();

        if ($records->isEmpty()) {
            return;
        }

        // 祖先単位でグルーピング（id優先）
        $grouped = $records->groupBy(function ($item) {
            return $item->ancestor_id_hansyoku ?: $item->ancestor_name;
        });

        foreach ($grouped as $ancestorKey => $group) {
            if ($group->count() < 2) continue;

            $first = $group->first();
            $bloodSum = $group->sum('blood_share');
            $ratio = $bloodSum * 100;
            $degree = $this->getDegreeLabel($bloodSum);
            $paths = $group->pluck('relation_path')->values()->toArray();

            RiInbreedRatio::updateOrCreate(
                [
                    'horse_id' => $first->horse_id,
                    'ancestor_id' => $first->ancestor_id_hansyoku,
                ],
                [
                    'horse_name' => $first->horse_name,
                    'ancestor_name' => $first->ancestor_name,
                    'generation_paths' => json_encode($paths, JSON_UNESCAPED_UNICODE),
                    'cross_count' => $group->count(),
                    'blood_share_sum' => $bloodSum,
                    'cross_ratio_percent' => $ratio,
                    'inbreed_degree' => $degree,
                    'source' => 'auto_calc',
                ]
            );
        }
    }

    /**
     * 血量比率に応じたインブリード強度を判定
     */
    protected function getDegreeLabel(float $bloodShare): ?string
    {
        return match (true) {
            $bloodShare >= 0.25 => '強',
            $bloodShare >= 0.125 => '中',
            $bloodShare >= 0.0625 => '弱',
            default => null,
        };
    }
}
