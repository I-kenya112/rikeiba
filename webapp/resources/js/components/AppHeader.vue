<template>
    <nav class="bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- 左側：ロゴ + ナビ -->
                <div class="flex">
                    <!-- ロゴ -->
                    <div class="shrink-0 flex items-center">
                        <a href="/dashboard">
                            <span class="text-xl font-bold text-gray-800">Rikeiba</span>
                        </a>
                    </div>

                    <!-- ナビリンク -->
                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                        <!-- Dashboard -->
                        <a href="/dashboard" :class="navClass('/dashboard')">
                            Dashboard
                        </a>

                        <!-- 出走馬リスト管理（Blade側の画面） -->
                        <a href="/horse-lists/manage" :class="navClass('/horse-lists')">
                            出走馬リスト管理
                        </a>

                        <!-- コース分析（Vueの画面） -->
                        <router-link to="/course" :class="navClass('/course')">
                            コース分析
                        </router-link>
                    </div>
                </div>

                <!-- 右側：ログアウトだけ（シンプル版） -->
                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <form method="POST" action="/logout">
                        <input type="hidden" name="_token" :value="csrf" />
                        <button type="submit"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                            ログアウト
                        </button>
                    </form>
                </div>

                <!-- モバイル用ハンバーガーはいったん省略（必要なら後で足す） -->
            </div>
        </div>
    </nav>
</template>

<script setup>
const csrfTag = document.querySelector('meta[name="csrf-token"]');
const csrf = csrfTag ? csrfTag.getAttribute('content') : "";

// Breeze の x-nav-link と同じような「アクティブなタブ」の見た目にする
const navClass = (prefix) => {
    const base =
        "inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium";
    const path = window.location.pathname;

    const active = path.startsWith(prefix);

    if (active) {
        return (
            base + " border-indigo-400 text-gray-900"
        ); // アクティブタブ：下線＋濃い文字
    } else {
        return (
            base +
            " border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
        );
    }
};
</script>
