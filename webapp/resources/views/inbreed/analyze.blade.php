@extends('layouts.app')

@section('header')
<div class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            インブリード・内包血統分析
        </h2>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

    {{-- 出走馬リスト --}}
    @if(isset($horseList) && $horseList->count())
        <div class="bg-white p-6 shadow rounded mb-6">
            <h3 class="text-xl font-bold mb-3">出走馬リスト</h3>
            <table class="table-auto w-full border rounded">
                <thead class="bg-sky-100">
                    <tr>
                        <th class="p-2 text-center">順番</th>
                        <th class="p-2">馬名</th>
                        <th class="p-2">父</th>
                        <th class="p-2">母父</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($horseList as $horse)
                        <tr class="border-t hover:bg-sky-50">
                            <td class="p-2 text-center">{{ $horse->order_no ?? '-' }}</td>
                            <td class="p-2">{{ $horse->horse_name ?? '(不明)' }}</td>
                            <td class="p-2">{{ $horse->father_name ?? '(不明)' }}</td>
                            <td class="p-2">{{ $horse->mother_father_name ?? '(不明)' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- 検索フォーム --}}
    <div class="bg-white p-6 shadow rounded mb-6 relative">
        <h2 class="text-2xl font-bold mb-4 text-gray-800 border-b pb-2">検索条件</h2>

        <form method="POST" action="{{ route('inbreed.analyze.search') }}" id="search-form" class="space-y-4">
            @csrf
            <input type="hidden" name="list_id" value="{{ $list_id ?? '' }}">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 relative">
                <div class="col-span-2 relative">
                    <label class="block font-semibold mb-1 text-gray-700">血統名（部分一致）</label>
                    <input type="text" id="keyword-input" name="keyword"
                        class="border rounded px-3 py-2 w-full focus:ring-2 focus:ring-sky-400"
                        placeholder="例：トニービン、サンデーサイレンス"
                        value="{{ $conditions['keyword'] ?? '' }}" autocomplete="off">
                    <ul id="suggestion-list"
                        class="absolute bg-white border rounded w-full mt-1 shadow-md z-10 hidden"></ul>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white px-4 py-2 rounded shadow">
                    検索を実行
                </button>
            </div>
        </form>
    </div>

    {{-- 検索結果 --}}
    @if ($results)
        <div class="mt-6">
            <h3 class="text-xl font-bold mb-3 text-sky-700">検索結果</h3>

            {{-- 内包血統 --}}
            <h4 class="text-lg font-semibold text-green-700 mt-4 mb-2">内包血統該当</h4>
            @if ($results['pedigree']->isNotEmpty())
                <table class="min-w-full border-collapse border border-gray-300 text-sm mb-8">
                    <thead class="bg-green-100">
                        <tr>
                            <th class="border px-2 py-1 text-left">#</th>
                            <th class="border px-2 py-1 text-left">馬名</th>
                            <th class="border px-2 py-1 text-left">父</th>
                            <th class="border px-2 py-1 text-left">母父</th>
                            <th class="border px-2 py-1 text-left">該当血統</th>
                            <th class="border px-2 py-1 text-left">関係経路</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results['pedigree'] as $i => $row)
                            <tr class="hover:bg-green-50">
                                <td class="border px-2 py-1">{{ $i + 1 }}</td>
                                <td class="border px-2 py-1 font-semibold">{{ $row->uma_name }}</td>
                                <td class="border px-2 py-1">{{ $row->father_name }}</td>
                                <td class="border px-2 py-1">{{ $row->mother_father_name }}</td>
                                <td class="border px-2 py-1">{{ $row->ancestor_name }}</td>
                                <td class="border px-2 py-1">{{ relation_path_to_japanese($row->relation_path ?? '') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500">該当する内包血統はありません。</p>
            @endif

            {{-- インブリード --}}
            <h4 class="text-lg font-semibold text-rose-700 mt-4 mb-2">インブリード該当</h4>
            @if ($results['inbreed']->isNotEmpty())
                <table class="min-w-full border-collapse border border-gray-300 text-sm">
                    <thead class="bg-rose-100">
                        <tr>
                            <th class="border px-2 py-1 text-left">#</th>
                            <th class="border px-2 py-1 text-left">馬名</th>
                            <th class="border px-2 py-1 text-left">父</th>
                            <th class="border px-2 py-1 text-left">母父</th>
                            <th class="border px-2 py-1 text-left">該当血統</th>
                            <th class="border px-2 py-1 text-left">血量比率</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($results['inbreed'] as $i => $row)
                            <tr class="hover:bg-rose-50">
                                <td class="border px-2 py-1">{{ $i + 1 }}</td>
                                <td class="border px-2 py-1 font-semibold">{{ $row->uma_name }}</td>
                                <td class="border px-2 py-1">{{ $row->father_name }}</td>
                                <td class="border px-2 py-1">{{ $row->mother_father_name }}</td>
                                <td class="border px-2 py-1">{{ $row->ancestor_name }}</td>
                                <td class="border px-2 py-1">{{ $row->blood_share_sum ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500">該当するインブリードはありません。</p>
            @endif
        </div>
    @endif
</div>

{{-- サジェストスクリプト --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('keyword-input');
    const suggestionList = document.getElementById('suggestion-list');
    let timeout = null;

    input.addEventListener('input', () => {
        clearTimeout(timeout);
        const query = input.value.trim();
        if (query.length < 2) {
            suggestionList.innerHTML = '';
            suggestionList.classList.add('hidden');
            return;
        }

        timeout = setTimeout(() => {
            fetch(`/api/ancestor/search?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    suggestionList.innerHTML = data
                        .map(name => `<li class="p-2 hover:bg-gray-100 cursor-pointer">${name}</li>`)
                        .join('');
                    suggestionList.classList.remove('hidden');

                    suggestionList.querySelectorAll('li').forEach(li => {
                        li.addEventListener('click', () => {
                            input.value = li.textContent;
                            suggestionList.innerHTML = '';
                            suggestionList.classList.add('hidden');
                        });
                    });
                });
        }, 250);
    });
});
</script>

@endsection
