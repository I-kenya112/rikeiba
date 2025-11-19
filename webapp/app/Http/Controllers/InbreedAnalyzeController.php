<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\RiUma;
use App\Models\RiPedigree;
use App\Models\RiInbreedRatio;
use App\Models\RiHorseListItem;

class InbreedAnalyzeController extends Controller
{
    /**
     * 分析ページの表示
     */
    public function index(Request $request, $list_id = null)
    {
        $horseList = collect();

        if ($list_id) {
            $horseList = RiHorseListItem::where('list_id', $list_id)->get();
        }

        return view('inbreed.analyze', [
            'results'    => null,
            'conditions' => [],
            'horseList'  => $horseList,
            'list_id'    => $list_id,
        ]);
    }

    /**
     * 「分析へ進む」：出走馬リストの情報を取得
     */
    public function start(Request $request, $list_id = null)
    {
        $listId = $list_id ?? $request->query('list_id');
        $userId = Auth::id();

        if (!$listId) {
            return redirect()->route('horse-lists.manage')
                ->with('error', 'リストが指定されていません。');
        }

        // 父・母父情報つき出走馬リスト
        $horseList = DB::table('ri_horse_list_items as i')
            ->leftJoin('ri_uma as u', 'u.KettoNum', '=', 'i.horse_id')
            ->leftJoin('ri_pedigree as f', function ($join) {
                $join->on('f.horse_id', '=', 'i.horse_id')
                    ->where('f.relation_path', '=', 'F');
            })
            ->leftJoin('ri_pedigree as mf', function ($join) {
                $join->on('mf.horse_id', '=', 'i.horse_id')
                    ->where('mf.relation_path', '=', 'MF');
            })
            ->where('i.list_id', $listId)
            ->select(
                'i.id',
                'i.horse_id',
                'i.horse_name',
                'u.Bamei as uma_name',
                'f.ancestor_name as father_name',
                'mf.ancestor_name as mother_father_name',
                'i.order_no'
            )
            ->orderBy('i.order_no')
            ->get()
            ->map(function ($row) {
                $row->uma_name = $row->uma_name ?? $row->horse_name ?? '(不明)';
                $row->father_name = $row->father_name ?? '(不明)';
                $row->mother_father_name = $row->mother_father_name ?? '(不明)';
                return $row;
            });

        return view('inbreed.analyze', [
            'results'    => null,
            'conditions' => [],
            'horseList'  => $horseList,
            'list_id'    => $listId,
        ]);
    }

    /**
     * 検索統合版（内包＋インブリード）
     */
    public function search(Request $request)
    {
        $listId = $request->input('list_id');
        $keyword = trim($request->input('keyword', ''));

        if (!$listId || $keyword === '') {
            return back()->with('error', '検索キーワードを入力してください。');
        }

        // --- 出走馬リスト取得（父・母父つき）---
        $horseList = DB::table('ri_horse_list_items as i')
            ->leftJoin('ri_uma as u', 'u.KettoNum', '=', 'i.horse_id')
            ->leftJoin('ri_pedigree as f', function ($join) {
                $join->on('f.horse_id', '=', 'i.horse_id')
                    ->where('f.relation_path', '=', 'F');
            })
            ->leftJoin('ri_pedigree as mf', function ($join) {
                $join->on('mf.horse_id', '=', 'i.horse_id')
                    ->where('mf.relation_path', '=', 'MF');
            })
            ->where('i.list_id', $listId)
            ->select(
                'i.horse_id',
                'i.horse_name',
                'i.order_no',
                'u.Bamei as uma_name',
                'f.ancestor_name as father_name',
                'mf.ancestor_name as mother_father_name'
            )
            ->orderBy('i.order_no')
            ->get()
            ->map(function ($row) {
                $row->uma_name = $row->uma_name ?? $row->horse_name ?? '(不明)';
                $row->father_name = $row->father_name ?? '(不明)';
                $row->mother_father_name = $row->mother_father_name ?? '(不明)';
                return $row;
            });

        $horseIds = $horseList->pluck('horse_id')->toArray();

        // --- 内包血統 ---
        $pedigreeMatches = RiPedigree::whereIn('horse_id', $horseIds)
            ->where('ancestor_name', 'LIKE', "%{$keyword}%")
            ->select('horse_id', 'ancestor_name', 'relation_path')
            ->get()
            ->groupBy('horse_id');

        // --- インブリード ---
        $inbreedMatches = RiInbreedRatio::whereIn('horse_id', $horseIds)
            ->where('ancestor_name', 'LIKE', "%{$keyword}%")
            ->select('horse_id', 'ancestor_name', 'blood_share_sum')
            ->get()
            ->groupBy('horse_id');

        // --- 結果構築 ---
        $pedigreeResults = collect();
        $inbreedResults = collect();

        foreach ($horseList as $horse) {
            $id = $horse->horse_id;

            if ($pedigreeMatches->has($id)) {
                foreach ($pedigreeMatches[$id] as $p) {
                    $pedigreeResults->push((object)[
                        'uma_name' => $horse->uma_name,
                        'father_name' => $horse->father_name,
                        'mother_father_name' => $horse->mother_father_name,
                        'ancestor_name' => $p->ancestor_name,
                        'relation_path' => $p->relation_path,
                    ]);
                }
            }

            if ($inbreedMatches->has($id)) {
                foreach ($inbreedMatches[$id] as $r) {
                    $inbreedResults->push((object)[
                        'uma_name' => $horse->uma_name,
                        'father_name' => $horse->father_name,
                        'mother_father_name' => $horse->mother_father_name,
                        'ancestor_name' => $r->ancestor_name,
                        'blood_share_sum' => $r->blood_share_sum,
                    ]);
                }
            }
        }

        return view('inbreed.analyze', [
            'results' => [
                'pedigree' => $pedigreeResults,
                'inbreed'  => $inbreedResults,
            ],
            'conditions' => [
                'keyword' => $keyword,
            ],
            'horseList' => $horseList,
            'list_id' => $listId,
        ]);
    }

    /**
     * 馬名Ajax検索
     */
    public function ajaxUmaSearch(Request $request)
    {
        $keyword = $request->get('q', '');
        if (!$keyword) {
            return response()->json([]);
        }

        $horses = RiUma::where('Bamei', 'LIKE', "%{$keyword}%")
            ->orderBy('Bamei')
            ->limit(20)
            ->pluck('Bamei');

        return response()->json($horses);
    }

    /**
     * 血統名サジェスト（Ajax）
     */
    public function ajaxAncestorSearch(Request $request)
    {
        $keyword = trim($request->input('q', ''));
        if (mb_strlen($keyword) < 2) {
            return response()->json([]);
        }

        $pedigreeNames = DB::table('ri_pedigree')
            ->select('ancestor_name')
            ->where('ancestor_name', 'LIKE', "{$keyword}%")
            ->limit(20);

        $inbreedNames = DB::table('ri_inbreed_ratio')
            ->select('ancestor_name')
            ->where('ancestor_name', 'LIKE', "{$keyword}%")
            ->limit(20);

        $names = $pedigreeNames
            ->union($inbreedNames)
            ->distinct()
            ->orderBy('ancestor_name')
            ->limit(20)
            ->pluck('ancestor_name');

        return response()->json($names);
    }
}
