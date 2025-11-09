@extends('layouts.app')

@section('header')
<div class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            🧬 血統共通度分析（{{ $list->title }}）
        </h2>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="bg-white p-6 shadow rounded">
        <p class="text-gray-600 mb-4">
            このページでは、出走馬リストに共通して内包されている血統祖先を分析します。
        </p>

        <button id="analyze-common-btn" class="bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded shadow mb-4">
            分析を実行
        </button>

        <div id="common-result" class="overflow-x-auto">
            <p class="text-gray-500">「分析を実行」ボタンを押すと結果が表示されます。</p>
        </div>
    </div>
</div>

<script>
document.getElementById('analyze-common-btn')?.addEventListener('click', async () => {
    const listId = "{{ $list->id }}";

    const res = await fetch("{{ route('inbreed.common-analyze') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ list_id: listId }),
    });

    const data = await res.json();
    const container = document.getElementById('common-result');
    container.innerHTML = '';

    if (!data.length) {
        container.innerHTML = '<p class="text-gray-500">該当データがありません。</p>';
        return;
    }

    let html = `
        <table class="table-auto w-full border">
            <thead class="bg-sky-100">
                <tr>
                    <th class="p-2">祖先名</th>
                    <th class="p-2 text-center">登場馬数</th>
                    <th class="p-2 text-center">出現率</th>
                    <th class="p-2">該当馬一覧</th>
                </tr>
            </thead>
            <tbody>
    `;
    data.forEach(a => {
        html += `
            <tr class="border-t hover:bg-sky-50">
                <td class="p-2 font-semibold">${a.ancestor_name}</td>
                <td class="p-2 text-center">${a.count}</td>
                <td class="p-2 text-center">${a.rate}%</td>
                <td class="p-2 text-sm text-gray-700">${a.horses.join('、 ')}</td>
            </tr>
        `;
    });
    html += '</tbody></table>';
    container.innerHTML = html;
});
</script>
@endsection
