<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RiPedigreeService;

class RiPedigreeBuild extends Command
{
    protected $signature = 'ri:pedigree-build {--horse_id=}';
    protected $description = 'Build 5-gen pedigrees for horses (all or one by horse_id)';

    public function handle(RiPedigreeService $svc)
    {
        $horseId = $this->option('horse_id');

        if ($horseId) {
            $this->info("Building pedigree for horse {$horseId}...");
            $uma = \App\Models\RiUma::where('KettoNum', $horseId)->first();
            if ($uma) {
                $svc->buildForUma($uma);
                $this->info("Done for horse {$horseId}.");
            } else {
                $this->error("Horse {$horseId} not found.");
            }
        } else {
            $this->info("Building pedigree for ALL horses...");

            $svc->buildAll(function ($done, $total, $lastHorseId) {

                if ($done % 1000 === 0 || $done === $total) {
                    $percent = round($done / $total * 100, 1);
                    $this->info("  {$done} / {$total} ({$percent}%)  last={$lastHorseId}");
                }

            });

            $this->info("Done for all horses.");
        }

        return 0;
    }
}
