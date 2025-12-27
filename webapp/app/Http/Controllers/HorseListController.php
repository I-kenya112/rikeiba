<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RiHorseList;
use App\Models\RiHorseListItem;
use App\Models\RiUma;
use Illuminate\Support\Facades\DB;

class HorseListController extends Controller
{
    /**
     * 出走馬リスト管理ページ
     */
    public function index()
    {
        $userId = Auth::id();

        $lists = RiHorseList::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('horse_lists.manage', compact('lists'));
    }

    /**
     * 出走馬リスト保存
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'horse_list' => 'required|string',
        ]);

        $horseList = json_decode($validated['horse_list'], true);
        if (!is_array($horseList) || empty($horseList)) {
            return back()->withErrors(['horse_list' => '出走馬を1頭以上追加してください。']);
        }

        $userId = Auth::id();

        DB::transaction(function () use ($userId, $validated, $horseList) {
            $list = RiHorseList::create([
                'user_id' => $userId,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
            ]);

            foreach ($horseList as $index => $horse) {
                $uma = $this->findUmaOrFail($horse['id']);

                RiHorseListItem::create([
                    'list_id'   => $list->id,
                    'horse_id'  => $uma->KettoNum,
                    'horse_name'=> $uma->Bamei,
                    'order_no'  => $index + 1,
                ]);
            }
        });

        return redirect()->route('horse-lists.manage')
            ->with('success', '出走馬リストを保存しました。');
    }

    /**
     * 編集ページ表示
     */
    public function edit($id)
    {
        $userId = Auth::id();

        $list = RiHorseList::where('id', $id)
            ->where('user_id', $userId)
            ->with('items')
            ->firstOrFail();

        $horseList = $list->items->map(fn ($item) => [
            'id'   => $item->horse_id,
            'name' => $item->horse_name,
        ]);

        return view('horse_lists.edit', compact('list', 'horseList'));
    }

    /**
     * リスト更新処理
     */
    public function update(Request $request, $id)
    {
        $userId = Auth::id();

        $list = RiHorseList::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'horse_ids' => 'required|string',
        ]);

        $horseIds = array_filter(explode(',', $validated['horse_ids']));

        DB::transaction(function () use ($list, $validated, $horseIds) {
            $list->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
            ]);

            RiHorseListItem::where('list_id', $list->id)->delete();

            foreach ($horseIds as $index => $horseId) {
                $uma = $this->findUmaOrFail($horseId);

                RiHorseListItem::create([
                    'list_id'   => $list->id,
                    'horse_id'  => $uma->KettoNum,
                    'horse_name'=> $uma->Bamei,
                    'order_no'  => $index + 1,
                ]);
            }
        });

        return redirect()->route('horse-lists.manage')
            ->with('success', '出走馬リストを更新しました。');
    }

    /**
     * リスト削除
     */
    public function destroy($id)
    {
        $userId = Auth::id();

        $list = RiHorseList::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        DB::transaction(function () use ($list) {
            RiHorseListItem::where('list_id', $list->id)->delete();
            $list->delete();
        });

        return redirect()->route('horse-lists.manage')
            ->with('success', '出走馬リストを削除しました。');
    }

    /**
     * 馬名 Ajax 検索
     */
    public function ajaxSearch(Request $request)
    {
        $keyword = trim($request->get('q', ''));
        if ($keyword === '') {
            return response()->json([]);
        }

        return RiUma::query()
            ->select('KettoNum as id', 'Bamei as name')
            ->where('Bamei', 'LIKE', "{$keyword}%")
            ->orderBy('Bamei')
            ->limit(20)
            ->get();
    }

    /**
     * 既存 ri_uma を取得（存在しなければ例外）
     */
    private function findUmaOrFail(string $kettoNum): RiUma
    {
        return RiUma::where('KettoNum', $kettoNum)->firstOrFail();
    }
}
