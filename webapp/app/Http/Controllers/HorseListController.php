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
            'horse_list' => 'required|string', // JSON文字列で受け取る
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
                $uma = RiUma::select('Bamei')
                    ->where('KettoNum', $horse['id'])
                    ->first();

                RiHorseListItem::create([
                    'list_id' => $list->id,
                    'horse_id' => $horse['id'],
                    'horse_name' => $uma->Bamei ?? $horse['name'] ?? '(取得失敗)',
                    'order_no' => $index + 1,

                ]);
            }
        });

        return redirect()->route('horse-lists.manage')->with('success', '出走馬リストを保存しました。');
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

        // 出走馬IDと名前を取得してJSへ渡す形式に
        $horseList = $list->items->map(fn($item) => [
            'id' => $item->horse_id,
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
            // タイトル・説明更新
            $list->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
            ]);

            // 旧データ削除
            RiHorseListItem::where('list_id', $list->id)->delete();

            // 新データ再登録
            foreach ($horseIds as $index => $horseId) {
                $uma = RiUma::select('Bamei')
                    ->where('KettoNum', $horseId)
                    ->first();

                RiHorseListItem::create([
                    'list_id' => $list->id,
                    'horse_id' => $horseId,
                    'horse_name' => $uma->Bamei ?? '(取得失敗)',
                    'order_no' => $index + 1,
                ]);
            }
        });

        return redirect()->route('horse-lists.manage')->with('success', '出走馬リストを更新しました。');
    }

    /**
     * リスト削除
     */
    public function destroy($id)
    {
        $userId = Auth::id();
        $list = RiHorseList::where('id', $id)->where('user_id', $userId)->firstOrFail();

        DB::transaction(function () use ($list) {
            RiHorseListItem::where('list_id', $list->id)->delete();
            $list->delete();
        });

        return redirect()->route('horse-lists.manage')->with('success', '出走馬リストを削除しました。');
    }
}
