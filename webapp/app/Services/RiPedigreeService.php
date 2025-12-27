<?php

namespace App\Services;

use App\Models\RiUma;
use App\Models\RiHansyoku;
use App\Models\RiPedigree;
use Illuminate\Support\Facades\DB;

/**
 * ri_pedigree（5代血統表）を生成するサービス
 *
 * 特徴：
 *  - ri_hansyoku を最優先に使用（正式な繁殖登録情報）
 *  - ri_uma は繁殖馬名が空のときだけ補完用に参照
 *  - キャッシュを用いてDBアクセスを最小化
 */
class RiPedigreeService
{
    /** @var array<string, RiHansyoku|null> */
    protected array $hcache = [];

    /**
     * 全競走馬分の5代血統表を生成
     */
    public function buildAll(): void
    {
        echo "Building pedigree for ALL horses...\n";

        RiUma::chunk(500, function ($umas) {
            foreach ($umas as $uma) {
                $this->buildForUma($uma);
            }

            // キャッシュをバッチごとにリセット（メモリ節約）
            $this->hcache = [];
        });

        echo "✅ Pedigree build completed.\n";
    }

    /**
     * 🔥 手動追加した馬（ri_uma_manual_logs）だけ血統表を再生成
     *
     * @param string $source    manual / netkeiba など
     * @param string|null $from YYYY-MM-DD
     * @param string|null $to   YYYY-MM-DD
     */
    public function buildManualOnly(string $source = 'manual', ?string $from = null, ?string $to = null): void
    {
        echo "Building pedigree for MANUAL horses (source={$source})...\n";

        $query = DB::table('ri_uma_manual_logs')
            ->select('ketto_num')
            ->where('source', $source)
            ->whereNotNull('ketto_num')
            ->distinct()
            ->orderBy('ketto_num');

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $query->chunk(500, function ($rows) {
            $kettoNums = collect($rows)
                ->pluck('ketto_num')
                ->filter()
                ->values();

            // ri_uma をまとめて取得（N+1防止）
            $umas = RiUma::whereIn('KettoNum', $kettoNums)
                ->get()
                ->keyBy('KettoNum');

            foreach ($kettoNums as $kettoNum) {
                $uma = $umas->get($kettoNum);
                if (!$uma) {
                    echo "⚠ ri_uma not found: KettoNum={$kettoNum}\n";
                    continue;
                }
                $this->buildForUma($uma);
            }

            // バッチ単位でキャッシュクリア
            $this->hcache = [];
        });

        echo "✅ Manual pedigree build completed.\n";
    }

    /**
     * 特定の競走馬の血統表を生成
     */
    public function buildForUma(RiUma $uma): void
    {
        DB::transaction(function () use ($uma) {

            // 既存データ削除（再生成時もクリーンに）
            RiPedigree::where('horse_id', $uma->KettoNum)->delete();

            // 本馬の登録
            RiPedigree::create([
                'horse_id'              => $uma->KettoNum,
                'horse_name'            => $uma->Bamei,
                'relation_path'         => 'SELF',
                'generation'            => 0,
                'ancestor_id_uma'       => $uma->KettoNum,
                'ancestor_id_hansyoku'  => null,
                'ancestor_name'         => $uma->Bamei,
                'blood_share'           => 1.000000,
                'source'                => 'batch',
            ]);

            // 父・母の展開開始
            $this->expand(
                $uma->Ketto3InfoHansyokuNum1,
                $uma->Ketto3InfoBamei1,
                $uma->KettoNum,
                $uma->Bamei,
                1,
                'F'
            );

            $this->expand(
                $uma->Ketto3InfoHansyokuNum2,
                $uma->Ketto3InfoBamei2,
                $uma->KettoNum,
                $uma->Bamei,
                1,
                'M'
            );
        });
    }

    /**
     * 再帰的に5代血統を展開する
     */
    protected function expand(
        ?string $ancId,
        ?string $ancName,
        string $horseId,
        string $horseName,
        int $gen,
        string $path
    ): void {
        if ($gen > 5) return;

        $parent = null;

        if ($ancId && $ancId !== '0000000000') {
            $parent = $this->getHansyoku($ancId);
        }

        if (!$parent && $ancName) {
            $normalized = str_replace([' ', '　'], '', $ancName);
            $parent = RiHansyoku::whereRaw(
                "REPLACE(REPLACE(Bamei, ' ', ''), '　', '') = ?",
                [$normalized]
            )->first();
        }

        $ancestorName = $parent
            ? ($parent->Bamei ?: ($parent->BameiEng ?: $ancName))
            : $ancName;

        $ancestorIdUma = ($parent && !empty($parent->KettoNum) && $parent->KettoNum !== '0000000000')
            ? $parent->KettoNum
            : null;

        $ancestorIdHansyoku = $parent ? $parent->HansyokuNum : null;

        RiPedigree::create([
            'horse_id'             => $horseId,
            'horse_name'           => $horseName,
            'relation_path'        => $path,
            'generation'           => $gen,
            'ancestor_id_uma'      => $ancestorIdUma,
            'ancestor_id_hansyoku' => $ancestorIdHansyoku,
            'ancestor_name'        => $ancestorName ?: '(不明)',
            'blood_share'          => round(pow(0.5, $gen), 6),
            'source'               => 'batch',
        ]);

        if ($parent) {
            $this->expand($parent->HansyokuFNum ?? null, null, $horseId, $horseName, $gen + 1, $path . 'F');
            $this->expand($parent->HansyokuMNum ?? null, null, $horseId, $horseName, $gen + 1, $path . 'M');
        }
    }

    /**
     * 繁殖馬をキャッシュ付きで取得
     */
    protected function getHansyoku(string $num): ?RiHansyoku
    {
        if (isset($this->hcache[$num])) {
            return $this->hcache[$num];
        }

        $this->hcache[$num] = RiHansyoku::where('HansyokuNum', $num)->first();
        return $this->hcache[$num];
    }
}
