<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class RiPedigreeRebuildIncomplete extends Command
{
    protected $signature = 'ri:pedigree-rebuild-incomplete {--limit=}';
    protected $description = 'Rebuild pedigree only for horses with incomplete data (COUNT <> 63)';

    public function handle()
    {
        // 不完全な馬の抽出
        $query = DB::table('ri_pedigree')
            ->select('horse_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('horse_id')
            ->havingRaw('cnt <> 63');

        if ($this->option('limit')) {
            $query->limit((int)$this->option('limit'));
        }

        $horses = $query->pluck('horse_id');

        $this->info("Found " . count($horses) . " horses with incomplete pedigree.");

        foreach ($horses as $id) {
            $this->info("Rebuilding pedigree for horse_id: {$id}");

            // 既存のコマンドを呼び出し
            Artisan::call('ri:pedigree-build', [
                '--horse_id' => $id
            ]);

            $this->line(Artisan::output());
        }

        $this->info("Rebuild completed.");
    }
}
