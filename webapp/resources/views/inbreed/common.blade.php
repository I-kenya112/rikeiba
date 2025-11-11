@extends('layouts.app')

@section('header')
<div class="bg-white shadow">
    <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            血統共通度一覧
        </h2>
    </div>
</div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8 space-y-10">

    {{-- 内包血統一覧 --}}
    <div x-data class="bg-white p-6 shadow rounded mb-6">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-xl font-bold text-green-700">内包血統一覧</h3>
            <div class="space-x-2">
                <button @click="$dispatch('toggle-all', { open: true })"
                    class="bg-green-500 hover:bg-green-600 text-white text-sm px-3 py-1 rounded">
                    すべて展開
                </button>
                <button @click="$dispatch('toggle-all', { open: false })"
                    class="bg-gray-400 hover:bg-gray-500 text-white text-sm px-3 py-1 rounded">
                    すべて折り畳み
                </button>
            </div>
        </div>

        @if ($pedigreeResult->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300 text-sm">
                <thead class="bg-green-100">
                    <tr>
                        <th class="border px-2 py-1 text-center">#</th>
                        <th class="border px-2 py-1 text-left">祖先名</th>
                        <th class="border px-2 py-1 text-right">該当数</th>
                        <th class="border px-2 py-1 text-left">該当馬（馬名）</th>
                        <th class="border px-2 py-1 text-center">操作</th>
                    </tr>
                </thead>
                    {{-- 2頭以上の血統 --}}
                    @foreach ($pedigreeResult->filter(fn($r) => $r['count'] > 1) as $i => $row)
                    <tr x-data="{ open: true }" x-on:toggle-all.window="open = $event.detail.open"
                        class="hover:bg-green-50 transition">
                        <td class="border px-2 py-1 text-center">{{ $loop->iteration }}</td>
                        <td class="border px-2 py-1 font-semibold">{{ $row['ancestor_name'] }}</td>
                        <td class="border px-2 py-1 text-right">{{ $row['count'] }}</td>
                        <td class="border px-2 py-1 align-top">
                            <template x-if="open">
                                <ul class="list-decimal list-inside space-y-0.5">
                                    @foreach ($row['horses'] as $name)
                                        <li>{{ $name }}</li>
                                    @endforeach
                                </ul>
                            </template>
                            <template x-if="!open">
                                <ul><li>{{ $row['horses'][0] ?? '(不明)' }}</li></ul>
                            </template>
                        </td>
                        <td class="border px-2 py-1 text-center">
                            <button @click="open = !open"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs px-2 py-1 rounded">
                                <span x-text="open ? '折り畳み' : '展開'"></span>
                            </button>
                        </td>
                    </tr>
                    @endforeach

                    {{-- 単独血統（1頭のみ） --}}
                    @php
                        $singleCount = $pedigreeResult->filter(fn($r) => $r['count'] == 1)->count();
                    @endphp
                    @if ($singleCount > 0)
                    <tr x-data="{ open: false }" class="bg-gray-100">
                        <td colspan="5" class="p-2">
                            <button @click="open = !open"
                                class="bg-gray-300 hover:bg-gray-400 text-gray-800 text-xs px-2 py-1 rounded">
                                <span x-text="open ? '単独血統を隠す' : '単独血統（{{ $singleCount }}件）を表示'"></span>
                            </button>
                            <template x-if="open">
                                <div class="mt-2 border-t pt-2">
                                    <table class="min-w-full border border-gray-200 text-xs">
                                        <tbody>
                                            @foreach ($pedigreeResult->filter(fn($r) => $r['count'] == 1) as $row)
                                            <tr>
                                                <td class="border px-2 py-1 text-left">{{ $row['ancestor_name'] }}</td>
                                                <td class="border px-2 py-1 text-left">{{ $row['horses'][0] ?? '(不明)' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @else
            <p class="text-gray-500">内包血統データが見つかりません。</p>
        @endif
    </div>

    {{-- インブリード一覧 --}}
    <div x-data class="bg-white p-6 shadow rounded">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-xl font-bold text-orange-700">インブリード一覧</h3>
            <div class="space-x-2">
                <button @click="$dispatch('toggle-all-inbreed', { open: true })"
                    class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-3 py-1 rounded">
                    すべて展開
                </button>
                <button @click="$dispatch('toggle-all-inbreed', { open: false })"
                    class="bg-gray-400 hover:bg-gray-500 text-white text-sm px-3 py-1 rounded">
                    すべて折り畳み
                </button>
            </div>
        </div>

        @if ($inbreedResult->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300 text-sm">
                <thead class="bg-orange-100">
                    <tr>
                        <th class="border px-2 py-1 text-center">#</th>
                        <th class="border px-2 py-1 text-left">祖先名</th>
                        <th class="border px-2 py-1 text-right">該当数</th>
                        <th class="border px-2 py-1 text-left">該当馬（馬名）</th>
                        <th class="border px-2 py-1 text-center">操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inbreedResult as $i => $row)
                    <tr
                        x-data="{ open: true }"
                        x-on:toggle-all-inbreed.window="open = $event.detail.open"
                        class="hover:bg-orange-50 transition"
                    >
                        <td class="border px-2 py-1 text-center">{{ $loop->iteration }}</td>
                        <td class="border px-2 py-1 font-semibold">{{ $row['ancestor_name'] }}</td>
                        <td class="border px-2 py-1 text-right">{{ $row['count'] }}</td>
                        <td class="border px-2 py-1 align-top">
                            <template x-if="open">
                                <ul class="list-decimal list-inside space-y-0.5">
                                    @foreach ($row['horses'] as $name)
                                        <li>{{ $name }}</li>
                                    @endforeach
                                </ul>
                            </template>
                            <template x-if="!open">
                                <ul><li>{{ $row['horses'][0] ?? '(不明)' }}</li></ul>
                            </template>
                        </td>
                        <td class="border px-2 py-1 text-center">
                            <button @click="open = !open"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs px-2 py-1 rounded">
                                <span x-text="open ? '折り畳み' : '展開'"></span>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
            <p class="text-gray-500">インブリードデータが見つかりません。</p>
        @endif
    </div>
</div>
@endsection
