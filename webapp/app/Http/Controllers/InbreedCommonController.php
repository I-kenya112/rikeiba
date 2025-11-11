<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\RiPedigree;
use App\Models\RiInbreedRatio;
use App\Models\RiHorseListItem;

class InbreedCommonController extends Controller
{
    /**
     * 共通度分析ページ
     */
    public function index(Request $request, $list_id = null)
    {
        if (!$list_id) {
            return redirect()->route('horse-lists.manage')->with('error', 'リストが指定されていません。');
        }

        // 対象馬一覧を取得
        $horseIds = RiHorseListItem::where('list_id', $list_id)
            ->pluck('horse_id')
            ->toArray();

        if (empty($horseIds)) {
            return view('inbreed.common', [
                'pedigreeResult' => collect(),
                'inbreedResult'  => collect(),
                'list_id'        => $list_id,
            ]);
        }

        /**
         * 内包血統（SELF除外・馬名JOIN）
         */
        $horseOrders = DB::table('ri_horse_list_items')
            ->where('list_id', $list_id)
            ->pluck('order_no', 'horse_name'); // 馬名 => 順番 の対応を取得

        $pedigreeResult = DB::table('ri_pedigree as p')
            ->join('ri_uma as u', 'u.KettoNum', '=', 'p.horse_id')
            ->select('p.ancestor_name', 'u.Bamei as horse_name')
            ->whereIn('p.horse_id', $horseIds)
            ->where('p.relation_path', '!=', 'SELF')
            ->get()
            ->groupBy('ancestor_name')
            ->map(function ($group, $ancestor) use ($horseOrders) {
                $uniqueHorses = $group->pluck('horse_name')->unique()->values();
                // ✅ order_no順で並び替え
                $sorted = $uniqueHorses->sortBy(fn($name) => $horseOrders[$name] ?? 9999)->values();

                return [
                    'ancestor_name' => $ancestor,
                    'count' => $sorted->count(),
                    'horses' => $sorted,
                ];
            })
            ->sortByDesc('count');

        /**
         * インブリード（馬名JOIN）
         */
        $inbreedResult = DB::table('ri_inbreed_ratio as r')
            ->join('ri_uma as u', 'u.KettoNum', '=', 'r.horse_id')
            ->select('r.ancestor_name', 'u.Bamei as horse_name')
            ->whereIn('r.horse_id', $horseIds)
            ->get()
            ->groupBy('ancestor_name')
            ->map(function ($group, $ancestor) {
                $uniqueHorses = $group->pluck('horse_name')->unique()->values();
                return [
                    'ancestor_name' => $ancestor,
                    'count' => $uniqueHorses->count(), // ✅ 重複排除
                    'horses' => $uniqueHorses,
                ];
            })
            ->sortByDesc('count');

        return view('inbreed.common', [
            'pedigreeResult' => $pedigreeResult,
            'inbreedResult'  => $inbreedResult,
            'list_id'        => $list_id,
        ]);
    }
}
