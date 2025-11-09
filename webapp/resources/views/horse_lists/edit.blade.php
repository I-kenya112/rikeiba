@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
    <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">
        出走馬リスト編集：「{{ $list->title }}」
    </h2>

    <form method="POST" action="{{ route('horse-lists.update', $list->id) }}">
        @csrf

        {{-- タイトル --}}
        <div class="mb-4">
            <label class="block font-semibold">タイトル</label>
            <input type="text" name="title" value="{{ $list->title }}"
                class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-sky-400">
        </div>

        {{-- メモ --}}
        <div class="mb-4">
            <label class="block font-semibold">メモ</label>
            <textarea name="description"
                class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-sky-400">{{ $list->description }}</textarea>
        </div>

        {{-- 馬追加フォーム --}}
        <div class="mb-4">
            <label class="block font-semibold mb-2">出走馬追加</label>
            <div class="flex gap-2 items-center">
                <input type="text" id="horse-search" placeholder="馬名または馬IDを入力"
                    class="border rounded px-3 py-2 w-72 focus:ring-2 focus:ring-sky-400">
                <button type="button" id="add-horse"
                    class="bg-sky-500 hover:bg-sky-600 text-white px-4 py-2 rounded shadow transition">
                    ＋追加
                </button>
            </div>
            <ul id="suggestions" class="border bg-white w-72 absolute mt-1 hidden rounded shadow-lg z-10"></ul>
        </div>

        {{-- 出走馬リスト --}}
        <div class="mb-6">
            <input type="hidden" id="horse-ids-hidden" name="horse_ids"
                value="{{ $horseList->pluck('id')->implode(',') }}">
            <table id="horse-table" class="table-auto w-full border rounded shadow-sm">
                <thead class="bg-sky-100">
                    <tr>
                        <th class="p-2 w-20 text-center">順番</th>
                        <th class="p-2">馬ID</th>
                        <th class="p-2">馬名</th>
                        <th class="p-2 text-center">操作</th>
                    </tr>
                </thead>
                <tbody id="horse-list" class="bg-white">
                    @foreach ($horseList as $i => $h)
                        <tr data-id="{{ $h['id'] }}" draggable="true" class="hover:bg-sky-50 cursor-move">
                            <td class="p-2 text-center handle">{{ $i + 1 }}</td>
                            <td class="p-2">{{ $h['id'] }}</td>
                            <td class="p-2">{{ $h['name'] }}</td>
                            <td class="p-2 text-center">
                                <button type="button"
                                    class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-1 rounded"
                                    onclick="removeHorse('{{ $h['id'] }}')">
                                    削除
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="text-gray-500 text-sm mt-1">※行をドラッグして並び替え可能です。</p>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                保存する
            </button>
            <a href="{{ route('horse-lists.manage') }}"
                class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded">戻る</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let horseList = @json($horseList);
    const tableBody = document.getElementById('horse-list');
    const hiddenInput = document.getElementById('horse-ids-hidden');
    const horseInput = document.getElementById('horse-search');
    const suggestions = document.getElementById('suggestions');
    const addBtn = document.getElementById('add-horse');

    /** ---------------------------
     * サジェスト検索
     * --------------------------- */
    horseInput.addEventListener('input', async () => {
        const q = horseInput.value.trim();
        if (q.length < 2) {
            suggestions.classList.add('hidden');
            return;
        }
        const res = await fetch(`/api/uma/search?q=${encodeURIComponent(q)}`);
        const data = await res.json();
        suggestions.innerHTML = '';
        if (data.length === 0) {
            suggestions.classList.add('hidden');
            return;
        }
        data.forEach(item => {
            const li = document.createElement('li');
            li.textContent = `${item.name} (${item.id})`;
            li.className = 'px-3 py-2 hover:bg-sky-100 cursor-pointer';
            li.addEventListener('click', () => {
                selectHorse(item);
                suggestions.classList.add('hidden');
            });
            suggestions.appendChild(li);
        });
        suggestions.classList.remove('hidden');
    });

    /** ---------------------------
     * 馬追加
     * --------------------------- */
    function selectHorse(item) {
        if (horseList.some(h => h.id == item.id)) return; // 重複防止
        horseList.push({ id: item.id, name: item.name });
        horseInput.value = '';
        renderList();
    }

    addBtn.addEventListener('click', () => {
        const val = horseInput.value.trim();
        if (!val) return;
        horseList.push({ id: val, name: '（手動入力）' });
        horseInput.value = '';
        renderList();
    });

    /** ---------------------------
     * 削除
     * --------------------------- */
    window.removeHorse = (id) => {
        horseList = horseList.filter(h => h.id != id);
        renderList();
    };

    /** ---------------------------
     * 並び替え（ドラッグ＆ドロップ対応）
     * --------------------------- */
    let dragSrcEl = null;

    tableBody.addEventListener('dragstart', (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;
        dragSrcEl = tr;
        tr.classList.add('opacity-50');
        e.dataTransfer.effectAllowed = 'move';
    });

    tableBody.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const tr = e.target.closest('tr');
        if (!tr || tr === dragSrcEl) return;
        const rect = tr.getBoundingClientRect();
        const midpoint = rect.top + rect.height / 2;
        const after = (e.clientY - rect.top) > rect.height / 2;
        if (after) {
            tr.parentNode.insertBefore(dragSrcEl, tr.nextSibling);
        } else {
            tr.parentNode.insertBefore(dragSrcEl, tr);
        }
    });

    tableBody.addEventListener('dragend', () => {
        if (dragSrcEl) {
            dragSrcEl.classList.remove('opacity-50');
            dragSrcEl = null;
        }
        updateOrderFromDOM();
    });

    /** ---------------------------
     * 並び順をDOMから更新
     * --------------------------- */
    function updateOrderFromDOM() {
        const rows = Array.from(tableBody.querySelectorAll('tr'));
        horseList = rows.map((row, idx) => {
            const id = row.dataset.id;
            const h = horseList.find(x => x.id == id);
            return { id: h.id, name: h.name };
        });
        renderList();
    }

    /** ---------------------------
     * テーブル再描画
     * --------------------------- */
    function renderList() {
        tableBody.innerHTML = '';
        horseList.forEach((h, idx) => {
            tableBody.insertAdjacentHTML('beforeend', `
                <tr data-id="${h.id}" draggable="true" class="hover:bg-sky-50 cursor-move">
                    <td class="p-2 text-center">${idx + 1}</td>
                    <td class="p-2">${h.id}</td>
                    <td class="p-2">${h.name}</td>
                    <td class="p-2 text-center">
                        <button type="button"
                            class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-1 rounded"
                            onclick="removeHorse('${h.id}')">
                            削除
                        </button>
                    </td>
                </tr>
            `);
        });
        hiddenInput.value = horseList.map(h => h.id).join(',');
    }

    // 初期描画
    renderList();
});
</script>

@endsection
