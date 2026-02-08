<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\RiHansyoku;
use App\Models\RiHansyokuBloodline;

class RiHansyokuBloodlineResolve extends Command
{
    protected $signature = 'ri:hansyoku-bloodline-resolve {--max=20}';
    protected $description = 'Resolve hansyoku bloodline by tracing sire line up to N generations';

    /** @var array<string,array{line_key:?string,line_key_detail:?string,root:?string,depth:?int,method:string}> */
    private array $memo = [];

    /** @var array<string,array{line_key:string,line_key_detail:?string}> root_hansyoku_num => line info */
    private array $seed = [];

    /** @var array<string, string|null> HansyokuNum => FatherHansyokuNum */
    private array $fatherMap = [];

    public function handle(): int
    {
        $maxDepth = (int)$this->option('max');

        $this->info("Loading seeds from ri_bloodline_detail...");
        $this->loadSeed();

        $this->info("Loading hansyoku father map...");
        $this->loadFatherMap();

        $this->info("Resolving all hansyoku...");
        $now = now();

        $buffer = [];
        $count = 0;

        RiHansyoku::query()
            ->select(['HansyokuNum', 'Bamei'])
            ->orderBy('HansyokuNum')
            ->chunk(2000, function ($rows) use ($maxDepth, $now, &$buffer, &$count) {

                foreach ($rows as $r) {
                    $num = (string)$r->HansyokuNum;
                    if (!$num || $num === '0000000000') continue;

                    $bamei = $r->Bamei ? (string)$r->Bamei : null;

                    $res = $this->resolveOne($num, $maxDepth);

                    $buffer[] = [
                        'hansyoku_num'               => $num,
                        'hansyoku_bamei'             => $bamei,

                        'line_key'                   => $res['line_key'],
                        'line_key_detail'            => $res['line_key_detail'],
                        'resolved_root_hansyoku_num' => $res['root'],
                        'resolved_depth'             => $res['depth'],
                        'method'                     => $res['method'],

                        'created_at'                 => $now,
                        'updated_at'                 => $now,
                    ];

                    if (count($buffer) >= 3000) {
                        RiHansyokuBloodline::query()->upsert(
                            $buffer,
                            ['hansyoku_num'],
                            [
                                'hansyoku_bamei',
                                'line_key',
                                'line_key_detail',
                                'resolved_root_hansyoku_num',
                                'resolved_depth',
                                'method',
                                'updated_at',
                            ]
                        );
                        $count += count($buffer);
                        $buffer = [];
                    }
                }

                if ($buffer) {
                    RiHansyokuBloodline::query()->upsert(
                        $buffer,
                        ['hansyoku_num'],
                        [
                            'hansyoku_bamei',
                            'line_key',
                            'line_key_detail',
                            'resolved_root_hansyoku_num',
                            'resolved_depth',
                            'method',
                            'updated_at',
                        ]
                    );
                    $count += count($buffer);
                    $buffer = [];
                }
            });

        $this->info("DONE. upsert rows={$count}");
        return 0;
    }

    private function loadSeed(): void
    {
        $rows = DB::table('ri_bloodline_detail as d')
            ->join('ri_bloodline as b', 'b.id', '=', 'd.bloodline_id')
            ->select([
                'd.root_hansyoku_num as root',
                'b.line_key as line_key',
                'd.line_key_detail as line_key_detail',
            ])
            ->whereNotNull('d.root_hansyoku_num')
            ->get();

        $this->seed = [];

        foreach ($rows as $r) {
            $root = (string)$r->root;

            $this->seed[$root] = [
                'line_key'        => (string)$r->line_key,
                'line_key_detail' => $r->line_key_detail ? (string)$r->line_key_detail : null,
            ];

            // root 自身は seed として確定
            $this->memo[$root] = [
                'line_key'        => $this->seed[$root]['line_key'],
                'line_key_detail' => $this->seed[$root]['line_key_detail'],
                'root'            => $root,
                'depth'           => 0,
                'method'          => 'seed',
            ];
        }
    }

    private function loadFatherMap(): void
    {
        $this->fatherMap = [];

        RiHansyoku::query()
            ->select(['HansyokuNum', 'HansyokuFNum'])
            ->chunk(5000, function ($rows) {
                foreach ($rows as $r) {
                    $num = (string)$r->HansyokuNum;
                    $f   = $r->HansyokuFNum ? (string)$r->HansyokuFNum : null;
                    if ($f === '0000000000') $f = null;
                    $this->fatherMap[$num] = $f;
                }
            });
    }

    private function resolveOne(string $num, int $maxDepth): array
    {
        if (isset($this->memo[$num])) {
            return $this->memo[$num];
        }

        $visited = [];
        $cur = $num;

        for ($depth = 0; $depth <= $maxDepth; $depth++) {
            if (!$cur) break;
            if (isset($visited[$cur])) break; // ループ防止
            $visited[$cur] = true;

            // seed に当たったら確定
            if (isset($this->memo[$cur]) && $this->memo[$cur]['method'] === 'seed') {
                $hit = $this->memo[$cur];

                $res = [
                    'line_key'        => $hit['line_key'],
                    'line_key_detail' => $hit['line_key_detail'],
                    'root'            => $hit['root'],
                    'depth'           => $depth,
                    'method'          => 'trace',
                ];

                return $this->memo[$num] = $res;
            }

            $cur = $this->fatherMap[$cur] ?? null;
        }

        return $this->memo[$num] = [
            'line_key'        => null,
            'line_key_detail' => null,
            'root'            => null,
            'depth'           => null,
            'method'          => 'unknown',
        ];
    }
}
