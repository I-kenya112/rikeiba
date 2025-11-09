@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 bg-gray-50 rounded-lg shadow-sm">

    <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">出走馬リスト作成</h2>

    {{-- 入力フォーム --}}
    <form id="horse-form" class="mb-6">
        <div class="flex gap-2 items-center">
            <input type="text" id="horse-id" placeholder="馬名またはIDを入力"
                class="border border-gray-300 rounded-lg px-3 py-2 w-72 focus:ring-2 focus:ring-sky-400">
            <button type="button" id="add-horse"
                class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded-lg">追加</button>
        </div>
    </form>

    {{-- サジェストリスト --}}
    <ul id="suggestions"
        class="border border-gray-300 bg-white w-72 absolute mt-1 hidden rounded-lg shadow-lg z-10"></ul>

    {{-- 馬リストテーブル --}}
    <table id="horse-table" class="table-auto w-full mb-8 border border-gray-300 rounded-lg">
        <thead class="bg-sky-100">
            <tr>
                <th class="p-2">馬ID</th>
                <th class="p-2">馬名</th>
                <th class="p-2 text-center">操作</th>
            </tr>
        </thead>
        <tbody id="horse-list" class="bg-white"></tbody>
    </table>

    {{-- 保存フォーム --}}
    <form method="POST" action="{{ route('horse-lists.save') }}" id="save-form">
        @csrf
        <input type="hidden" name="horse_list" id="horse-list-hidden">

        <div class="mb-4">
            <label class="block font-semibold mb-1">タイトル</label>
            <input type="text" name="title"
                class="border border-gray-300 rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-sky-400"
                placeholder="例：天皇賞秋2025" required>
        </div>

        <div class="mb-4">
            <label class="block font-semibold mb-1">メモ</label>
            <textarea name="description"
                class="border border-gray-300 rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-sky-400"
                placeholder="任意でメモを入力"></textarea>
        </div>

        <button type="submit"
            class="bg-green-500 hover:bg-green-600 text-white font-semibold px-6 py-2 rounded-lg">
            💾 保存
        </button>
    </form>

    {{-- 保存済みリスト一覧 --}}
    <h3 class="text-xl font-semibold mb-3">保存済み出走馬リスト</h3>
    <table class="table-auto w-full border rounded shadow-sm">
        <thead class="bg-sky-100">
            <tr><th class="p-2">タイトル</th><th class="p-2">メモ</th><th class="p-2">作成日</th><th class="p-2 text-center">操作</th></tr>
        </thead>
        <tbody>
            @forelse ($lists as $list)
                <tr class="border-t hover:bg-sky-50">
                    <td class="p-2 font-semibold">{{ $list->title }}</td>
                    <td class="p-2">{{ $list->description }}</td>
                    <td class="p-2 text-gray-500">{{ $list->created_at->format('Y-m-d') }}</td>
                    <td class="p-2 text-center">
                        {{-- 1段目：分析ボタン --}}
                        <div class="mb-2">
                            <a href="{{ route('inbreed.analyze.start', ['list_id' => $list->id]) }}"
                            class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded shadow">
                                分析へ進む
                            </a>
                        </div>

                        {{-- 2段目：編集・削除 --}}
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('horse-lists.edit', $list->id) }}"
                            class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded shadow">
                                編集
                            </a>
                            <form action="{{ route('horse-lists.delete', $list->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-1 rounded shadow"
                                    onclick="return confirm('削除しますか？')">
                                    削除
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center p-4 text-gray-500">保存されたリストはありません。</td></tr>
            @endforelse
        </tbody>
    </table>

</div>

{{-- JS --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const horseList = [];
    const tableBody = document.getElementById('horse-list');
    const hiddenInput = document.getElementById('horse-list-hidden');
    const horseInput = document.getElementById('horse-id');
    const suggestions = document.getElementById('suggestions');

    // Ajaxサジェスト
    horseInput.addEventListener('input', async () => {
        const q = horseInput.value.trim();
        if (q.length < 2) return suggestions.classList.add('hidden');

        const res = await fetch(`/api/uma/search?q=${encodeURIComponent(q)}`);
        const data = await res.json();

        suggestions.innerHTML = '';
        data.forEach(item => {
            const li = document.createElement('li');
            li.textContent = `${item.name} (${item.id})`;
            li.className = 'px-3 py-2 hover:bg-sky-100 cursor-pointer';
            li.addEventListener('click', () => selectHorse(item));
            suggestions.appendChild(li);
        });
        suggestions.classList.remove('hidden');
    });

    // サジェスト選択
    function selectHorse(item) {
        horseList.push({ id: item.id, name: item.name });
        renderList();
        horseInput.value = '';
        suggestions.classList.add('hidden');
    }

    // 手動追加
    document.getElementById('add-horse').addEventListener('click', () => {
        const val = horseInput.value.trim();
        if (!val) return;
        horseList.push({ id: val, name: '(手動入力)' });
        renderList();
        horseInput.value = '';
    });

    // リスト表示更新
    function renderList() {
        tableBody.innerHTML = '';
        horseList.forEach((h, i) => {
            tableBody.insertAdjacentHTML('beforeend', `
                <tr class="hover:bg-sky-50">
                    <td class="p-2">${h.id}</td>
                    <td class="p-2">${h.name}</td>
                    <td class="p-2 text-center">
                        <button type="button" class="bg-rose-500 text-white px-3 py-1 rounded"
                            onclick="removeHorse(${i})">削除</button>
                    </td>
                </tr>`);
        });
        hiddenInput.value = JSON.stringify(horseList); // ✅ JSON形式で保存
    }

    window.removeHorse = i => {
        horseList.splice(i, 1);
        renderList();
    };
});
</script>
@endsection
