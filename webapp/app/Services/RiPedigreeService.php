<?php

namespace App\Services;

use App\Models\RiUma;
use App\Models\RiHansyoku;
use App\Models\RiHansyokuBloodline;
use App\Models\RiPedigreeNew;
use Illuminate\Support\Facades\DB;

class RiPedigreeService
{
    /** @var array<string, RiHansyoku|null> */
    protected array $hcache = [];

    /**
     * hansyokuNum => [line_key, line_key_detail]
     *
     * @var array<string,array{line_key:?string,line_key_detail:?string}>
     */
    protected array $bloodlineMap = [];

    /**
     * 単体生成
     */
    public function buildForUma(RiUma $uma): void
    {
        DB::transaction(function () use ($uma) {

            RiPedigreeNew::where('horse_id', $uma->KettoNum)->delete();

            // SELF
            RiPedigreeNew::create([
                'horse_id'        => $uma->KettoNum,
                'horse_name'      => $uma->Bamei,
                'relation_path'   => 'SELF',
                'relation_type'   => 'S',
                'generation'      => 0,
                'position_index'  => 0,
                'ancestor_id_uma' => $uma->KettoNum,
                'ancestor_name'   => $uma->Bamei,
                'blood_share'     => 1.000000,
                'source'          => 'batch',
            ]);

            // Father
            $this->expand(
                $uma->Ketto3InfoHansyokuNum1,
                $uma->Ketto3InfoBamei1,
                $uma->KettoNum,
                $uma->Bamei,
                1,
                'F'
            );

            // Mother
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
     * 全馬生成（進捗コールバック対応）
     *
     * @param callable|null $progress (int $done, int $total, string $lastHorseId)
     */
    public function buildAll(?callable $progress = null): void
    {
        $total = RiUma::count();
        $done  = 0;

        RiUma::query()
            ->orderBy('KettoNum')
            ->chunk(200, function ($umas) use (&$done, $total, $progress) {

                foreach ($umas as $uma) {
                    $this->buildForUma($uma);
                    $done++;
                }

                // 進捗通知（chunkごと）
                if ($progress) {
                    $last = $umas->last();
                    $progress($done, $total, $last->KettoNum);
                }

                // メモリ解放
                $this->hcache = [];
            });
    }

    /**
     * 再帰展開
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

        $ancestorName = $parent ? $parent->Bamei : $ancName;
        $ancestorHansyokuNum = $parent ? $parent->HansyokuNum : null;

        $relationType  = substr($path, -1);
        $positionIndex = $this->calcPositionIndex($path);
        $bloodline     = $this->resolveBloodline($ancestorHansyokuNum);

        RiPedigreeNew::create([
            'horse_id'             => $horseId,
            'horse_name'           => $horseName,
            'relation_path'        => $path,
            'relation_type'        => $relationType,
            'generation'           => $gen,
            'position_index'       => $positionIndex,
            'ancestor_id_hansyoku' => $ancestorHansyokuNum,
            'ancestor_name'        => $ancestorName ?: '(不明)',
            'blood_share'          => round(pow(0.5, $gen), 6),
            'line_key'             => $bloodline['line_key'],
            'line_key_detail'      => $bloodline['line_key_detail'],
            'source'               => 'batch',
        ]);

        if ($parent) {
            $this->expand($parent->HansyokuFNum, null, $horseId, $horseName, $gen + 1, $path . 'F');
            $this->expand($parent->HansyokuMNum, null, $horseId, $horseName, $gen + 1, $path . 'M');
        }
    }

    /**
     * F/M → position_index
     */
    protected function calcPositionIndex(string $path): int
    {
        if ($path === 'SELF') return 0;

        $v = 0;
        foreach (str_split($path) as $c) {
            $v <<= 1;
            if ($c === 'F') $v |= 1;
        }
        return $v;
    }

    /**
     * 系統解決（ri_hansyoku_bloodline）
     */
    protected function resolveBloodline(?string $hansyokuNum): array
    {
        if (!$hansyokuNum) {
            return ['line_key' => null, 'line_key_detail' => null];
        }

        if (!$this->bloodlineMap) {
            $this->loadBloodlines();
        }

        return $this->bloodlineMap[$hansyokuNum]
            ?? ['line_key' => null, 'line_key_detail' => null];
    }

    /**
     * 全 hansyoku の血統分類をロード
     */
    protected function loadBloodlines(): void
    {
        $this->bloodlineMap = [];

        RiHansyokuBloodline::query()
            ->select(['hansyoku_num', 'line_key', 'line_key_detail'])
            ->whereNotNull('line_key')
            ->chunk(5000, function ($rows) {
                foreach ($rows as $r) {
                    $this->bloodlineMap[$r->hansyoku_num] = [
                        'line_key'        => $r->line_key,
                        'line_key_detail' => $r->line_key_detail,
                    ];
                }
            });
    }

    protected function getHansyoku(string $num): ?RiHansyoku
    {
        if (!array_key_exists($num, $this->hcache)) {
            $this->hcache[$num] = RiHansyoku::where('HansyokuNum', $num)->first();
        }
        return $this->hcache[$num];
    }
}
