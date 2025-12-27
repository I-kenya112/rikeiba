@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 max-w-xl">
    <h2 class="text-2xl font-bold mb-6 border-b pb-2">
        新規馬登録（手動）
    </h2>

    <form method="POST" action="{{ route('ri-uma.store') }}">
        @csrf

        {{-- 馬名 --}}
        <div class="mb-4">
            <label class="block font-semibold mb-1">馬名</label>
            <input type="text" name="bamei" required
                class="border rounded px-3 py-2 w-full">
        </div>

        {{-- 父 --}}
        <div class="mb-4">
            <label class="block font-semibold mb-1">父（繁殖馬）</label>
            <input type="hidden" name="father_id" id="father-id">
            <input type="text" id="father-search"
                placeholder="父馬名を検索"
                class="border rounded px-3 py-2 w-full">
            <ul id="father-suggestions"
                class="border bg-white mt-1 hidden rounded shadow"></ul>
        </div>

        {{-- 母 --}}
        <div class="mb-6">
            <label class="block font-semibold mb-1">母（繁殖馬）</label>
            <input type="hidden" name="mother_id" id="mother-id">
            <input type="text" id="mother-search"
                placeholder="母馬名を検索"
                class="border rounded px-3 py-2 w-full">
            <ul id="mother-suggestions"
                class="border bg-white mt-1 hidden rounded shadow"></ul>
        </div>

        <button type="submit"
            class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
            登録する
        </button>
    </form>
</div>

<script>
async function setupHansyokuSearch(inputId, hiddenId, listId) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const list = document.getElementById(listId);

    input.addEventListener('input', async () => {
        const q = input.value.trim();
        if (q.length < 2) {
            list.classList.add('hidden');
            return;
        }

        const res = await fetch(`/api/hansyoku/search?q=${encodeURIComponent(q)}`);
        const data = await res.json();

        list.innerHTML = '';
        if (data.length === 0) {
            list.classList.add('hidden');
            return;
        }

        data.forEach(item => {
            const li = document.createElement('li');
            li.textContent = `${item.name} (${item.id})`;
            li.className = 'px-3 py-2 hover:bg-gray-100 cursor-pointer';
            li.onclick = () => {
                input.value = item.name;
                hidden.value = item.id;
                list.classList.add('hidden');
            };
            list.appendChild(li);
        });

        list.classList.remove('hidden');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setupHansyokuSearch('father-search', 'father-id', 'father-suggestions');
    setupHansyokuSearch('mother-search', 'mother-id', 'mother-suggestions');
});
</script>
@endsection
