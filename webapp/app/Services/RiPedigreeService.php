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
            $this->expand($uma->Ketto3InfoHansyokuNum1, $uma->Ketto3InfoBamei1, $uma->KettoNum, $uma->Bamei, 1, 'F');
            $this->expand($uma->Ketto3InfoHansyokuNum2, $uma->Ketto3InfoBamei2, $uma->KettoNum, $uma->Bamei, 1, 'M');
        });
    }

    /**
     * 再帰的に5代血統を展開する
     *
     * 優先順位:
     *  1. ri_hansyoku.Bamei
     *  2. ri_hansyoku.BameiEng
     *  3. 引数の名前（ri_uma由来）
     *
     * IDが空でも馬名がある場合は、名前検索で補完する。
     */
    protected function expand(?string $ancId, ?string $ancName, string $horseId, string $horseName, int $gen, string $path): void
    {
        // 深すぎる場合だけスキップ
        if ($gen > 5) return;

        $parent = null;

        // 1️⃣ 通常パターン：IDが存在するならそのまま取得
        if ($ancId && $ancId !== '0000000000') {
            $parent = $this->getHansyoku($ancId);
        }

        // 2️⃣ IDが無い場合：名前で検索（全角半角スペース除去）
        if (!$parent && $ancName) {
            $normalized = str_replace([' ', '　'], '', $ancName);
            $parent = RiHansyoku::whereRaw("REPLACE(REPLACE(Bamei, ' ', ''), '　', '') = ?", [$normalized])
                ->first();
        }

        // 3️⃣ 馬名を決定（ri_hansyoku優先）
        $ancestorName = $parent
            ? ($parent->Bamei ?: ($parent->BameiEng ?: $ancName))
            : $ancName;

        // 4️⃣ IDをセット（存在すれば保持）
        $ancestorIdUma = ($parent && !empty($parent->KettoNum) && $parent->KettoNum !== '0000000000')
            ? $parent->KettoNum
            : null;

        $ancestorIdHansyoku = $parent ? $parent->HansyokuNum : null;

        // 5️⃣ INSERT
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

        // 6️⃣ 再帰（親がいる場合のみ）
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
