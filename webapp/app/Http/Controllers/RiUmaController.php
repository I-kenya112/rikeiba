<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RiHansyoku;
use App\Models\RiUmaManualLog;

class RiUmaController extends Controller
{
    /**
     * 新規 ri_uma 登録画面
     */
    public function create()
    {
        return view('ri_uma.create');
    }

    /**
     * 新規 ri_uma 登録処理
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bamei'     => 'required|string|max:36',
            'father_id' => 'required|string', // HansyokuNum
            'mother_id' => 'required|string', // HansyokuNum
        ]);

        $father = RiHansyoku::where('HansyokuNum', $validated['father_id'])->firstOrFail();
        $mother = RiHansyoku::where('HansyokuNum', $validated['mother_id'])->firstOrFail();

        return DB::transaction(function () use ($validated, $father, $mother) {

            // ① ri_uma.id を連番で採番
            $umaId = $this->nextUmaId();

            // ② KettoNum（9999xxxxxx）
            $kettoNum = $this->nextManualKettoNum();

            // ③ ri_uma 登録（insert一発）
            DB::table('ri_uma')->insert([
                'id'       => $umaId,
                'KettoNum' => $kettoNum,
                'Bamei'    => $validated['bamei'],
                'RegDate'  => now()->format('Ymd'),
                'DelKubun' => '0',

                // 父
                'Ketto3InfoHansyokuNum1' => $father->HansyokuNum,
                'Ketto3InfoBamei1'       => $father->Bamei,

                // 母
                'Ketto3InfoHansyokuNum2' => $mother->HansyokuNum,
                'Ketto3InfoBamei2'       => $mother->Bamei,
            ]);

            // ④ 手動登録ログ
            RiUmaManualLog::create([
                'uma_id'    => $umaId,
                'ketto_num' => $kettoNum,
                'source'    => 'manual',
                'meta'      => json_encode([
                    'father' => $father->HansyokuNum,
                    'mother' => $mother->HansyokuNum,
                ]),
            ]);

            return redirect()
                ->route('ri-uma.create')
                ->with('success', "登録しました（ID={$umaId}, KettoNum={$kettoNum}）");
        });
    }

    /**
     * ri_uma.id 用の連番を発行
     */
    private function nextUmaId(): int
    {
        $max = DB::table('ri_uma')->max('id');
        return $max ? $max + 1 : 1;
    }

    /**
     * 手動登録用 KettoNum（9999xxxxxx）
     */
    private function nextManualKettoNum(): string
    {
        $max = DB::table('ri_uma')
            ->where('KettoNum', 'LIKE', '9999%')
            ->max('KettoNum');

        if (!$max) {
            return '9999000001';
        }

        $seq = (int)substr((string)$max, -6);
        return '9999' . sprintf('%06d', $seq + 1);
    }
}
