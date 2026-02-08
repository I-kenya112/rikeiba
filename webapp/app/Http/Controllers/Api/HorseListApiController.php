<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorseListApiController extends Controller
{
    /**
     * ログインユーザーの出走馬リスト一覧
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        return DB::table('ri_horse_lists')
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->select('id', 'title', 'description')
            ->get();
    }

    /**
     * 指定リストの馬一覧
     */
    public function items(Request $request, int $listId)
    {
        $userId = $request->user()->id;

        // 自分のリストかチェック（保険）
        $list = DB::table('ri_horse_lists')
            ->where('id', $listId)
            ->where('user_id', $userId)
            ->first();

        if (! $list) {
            abort(404);
        }

        return DB::table('ri_horse_list_items')
            ->where('list_id', $listId)
            ->orderBy('order_no')
            ->select('horse_id', 'horse_name', 'order_no')
            ->get();
    }

    /**
     * 馬の祖先一覧（ri_pedigree）
     */
    public function ancestors(Request $request, string $horseId)
    {
        // 必要なら「自分のリストに含まれる馬か？」のチェックも入れられる

        $rows = DB::table('ri_pedigree')
            ->where('horse_id', $horseId)
            ->select(
                'ancestor_id_hansyoku as ancestor_id',
                'ancestor_name'
            )
            ->get();

        return $rows;
    }

    /**
     * 馬のインブリード情報一覧（ri_inbreed_ratio）
     */
    public function inbreed(Request $request, string $horseId)
    {
        return DB::table('ri_inbreed_ratio')
            ->where('horse_id', $horseId)
            ->select(
                'ancestor_id',
                'ancestor_name',
                'cross_ratio_percent', // 血量
                'cross_count',
                'inbreed_degree'
            )
            ->orderByDesc('cross_ratio_percent')
            ->get();
    }

    /**
     * 馬の血統系統情報（父系・母父系）
     * ri_pedigree -> ri_hansyoku_bloodline
     */
    public function line(Request $request, string $horseId)
    {
        // 1. 父・母父を取得
        $parents = DB::table('ri_pedigree')
            ->where('horse_id', $horseId)
            ->whereIn('relation_path', ['F', 'MF'])
            ->select(
                'relation_path',
                'ancestor_id_hansyoku as hansyoku_num'
            )
            ->get()
            ->keyBy('relation_path');

        if (! isset($parents['F'], $parents['MF'])) {
            return response()->json(null);
        }

        // 2. 血統マスタから系統を解決
        $bloodlines = DB::table('ri_hansyoku_bloodline')
            ->whereIn('hansyoku_num', [
                $parents['F']->hansyoku_num,
                $parents['MF']->hansyoku_num,
            ])
            ->get()
            ->keyBy('hansyoku_num');

        $fatherLine = $bloodlines[$parents['F']->hansyoku_num] ?? null;
        $mfLine     = $bloodlines[$parents['MF']->hansyoku_num] ?? null;

        // 3. 事実だけを返す
        return [
            'father' => [
                'line_key'        => $fatherLine->line_key ?? null,
                'line_key_detail' => $fatherLine->line_key_detail ?? null,
            ],
            'mother_father' => [
                'line_key'        => $mfLine->line_key ?? null,
                'line_key_detail' => $mfLine->line_key_detail ?? null,
            ],
        ];
    }
}
