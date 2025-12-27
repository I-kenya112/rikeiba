<script setup>
import { ref, onMounted, watch, computed } from "vue"; // ← computed 追加
import axios from "axios";
import { useRoute } from "vue-router";

const showInfo = ref(false);
const route = useRoute();
const courseKey = ref(route.params.courseKey);

// ------------------
// 年指定（単年 / 範囲）
// ------------------
const yearFrom = ref("");
const yearTo = ref("");

// ------------------
// 祖先・インブリード共通フィルタ
// ------------------
const gradeGroup = ref("ALL");
const years = computed(() => {
    if (!yearFrom.value || !yearTo.value) return "";
    return `${yearFrom.value}-${yearTo.value}`;
});
const yearOptions = ref([]);

// ------------------
// 表示年数
// ------------------
const yearSpan = computed(() => {
    if (!yearFrom.value || !yearTo.value) return 0;
    const from = Number(yearFrom.value);
    const to   = Number(yearTo.value);
    return to >= from ? to - from + 1 : 0;
});

// 祖先モード（ALL / F / M）
const ancestorMode = ref("ALL");

// 表示用ラベル
const ancestorModeLabel = computed(() => {
    switch (ancestorMode.value) {
        case "ALL": return "全血統";
        case "F": return "父方のみ";
        case "M": return "母方のみ";
        default: return "全血統";
    }
});

// ------------------
// 祖先成績
// ------------------
const rowsAncestor = ref([]);
const loadingAncestor = ref(false);

// ------------------
// インブリード成績
// ------------------
const rowsInbreed = ref([]);
const loadingInbreed = ref(false);

// ------------------
// 出走馬タブ 共通
// ------------------
const horseLists = ref([]);
const selectedListId = ref("");
const listItems = ref([]);
const horseAncestors = ref([]);
const horseInbreed = ref([]);
const loadingEntries = ref(false);

// タブ
const activeTab = ref("ancestors"); // ancestors / entries / inbreed

// 黄色表示
const showYellow = ref(false);

// 安全に率を表示する
const safeRate = (value) => {
    const num = Number(value);
    if (isNaN(num)) return "0.0";
    return (num * 100).toFixed(1);
};


// 補正関数（ベイズ推定風）
const adjustedShowRate = (row) => {
    const prior = 0.25;
    const K = 20;
    return (row.show_count + K * prior) / (row.start_count + K);
};

// -------------------------------
// 並び替え用スコア
// -------------------------------
const MIN_STARTS_PER_YEAR_ANCESTOR = 1; // 年1頭
const SCORE_COEF_ANCESTOR = 250; // 係数
const calcScoreAncestor = (row) => {
    if (!yearSpan.value) return 0;
    const starts = row.start_count;
    const avgStarts = starts / yearSpan.value;
    // ★ 年1頭未満は「足切りゾーン」
    if (avgStarts < MIN_STARTS_PER_YEAR_ANCESTOR) {
        return -1;
    }
    // メイン評価式
    const raw = row.adjusted_show_rate * Math.log(starts + 1);
    return raw * SCORE_COEF_ANCESTOR;
};
const MIN_STARTS_PER_YEAR_INBREED = 0.5; //　年0.2頭
const SCORE_COEF_INBREED = 250; // 係数
const calcScoreInbreed = (row) => {
    if (!yearSpan.value) return 0;
    const starts = row.start_count;
    const avgStarts = starts / yearSpan.value;
    // ★ 年0.2頭未満は「足切りゾーン」
    if (avgStarts < MIN_STARTS_PER_YEAR_INBREED) {
        return -1;
    }
    // メイン評価式
    const raw = row.adjusted_show_rate * Math.log(starts + 1);
    return raw * SCORE_COEF_INBREED;
};

// -------------------------------
// API: 祖先統計
// -------------------------------
const fetchAncestorStats = async () => {
    loadingAncestor.value = true;
    rowsAncestor.value = [];

    const res = await axios.get(`/api/course/${courseKey.value}/ancestor-stats`, {
        params: {
            grade: gradeGroup.value,
            years: years.value,
            sort: "show_rate",
            ancestor_mode: ancestorMode.value || "ALL",
        },
    });

    rowsAncestor.value = (res.data.data ?? []).map(r => {
        const adjusted = adjustedShowRate(r);

        return {
            ...r,
            adjusted_show_rate: adjusted,
            score: calcScoreAncestor({
                ...r,
                adjusted_show_rate: adjusted,
            }),
        };
    });

    // ソート
    rowsAncestor.value.sort((a, b) => b.score - a.score);

    yearOptions.value = res.data.years_range_options ?? [];

    // 初期年セット
    if (yearOptions.value.length && (!yearFrom.value || !yearTo.value)) {
        const latest = Number(yearOptions.value[0]);
        const from   = Math.max(
            latest - 9,
            Number(yearOptions.value[yearOptions.value.length - 1])
        );

        yearFrom.value = String(from);
        yearTo.value   = String(latest);
    }

    loadingAncestor.value = false;
};

// -------------------------------
// API: インブリード統計
// -------------------------------
const fetchInbreedStats = async () => {
    loadingInbreed.value = true;

    const res = await axios.get(`/api/course/${courseKey.value}/inbreed-stats`, {
        params: {
            grade: gradeGroup.value,
            years: years.value,
            sort: "show_rate",
        },
    });

    rowsInbreed.value = (res.data.data ?? []).map(r => {
        const adjusted = adjustedShowRate(r);

        return {
            ...r,
            adjusted_show_rate: adjusted,
            score: calcScoreInbreed({
                ...r,
                adjusted_show_rate: adjusted,
            }),
        };
    });

    // ソート
    rowsInbreed.value.sort((a, b) => b.score - a.score);
    loadingInbreed.value = false;

};

// -------------------------------
// 初回ロード
// -------------------------------
onMounted(async () => {
    await fetchAncestorStats();
    await fetchInbreedStats();
    fetchHorseLists();
});

// ① フィルタ変更時（API再取得専用）
watch(
    [gradeGroup, ancestorMode, yearFrom, yearTo],
    async () => {
        await fetchAncestorStats();
        await fetchInbreedStats();
        resortEntries();
    }
);

// -------------------------------
// 出走馬リスト API
// -------------------------------
const fetchHorseLists = async () => {
    try {
        const res = await axios.get("/api/horse-lists");
        horseLists.value = res.data;
    } catch (e) {
        console.warn("horse-lists API error", e);
    }
};

// -------------------------------
// ★ 馬ごとの祖先・インブリードを並び替え直す（条件変更時に再実行）
// -------------------------------
const resortEntries = () => {
    // --- 祖先側 ---
    horseAncestors.value.sort((a, b) => {
        const A = matchedAncestorsForHorse(a);
        const B = matchedAncestorsForHorse(b);

        const maxA = A.length ? Math.max(...A.map(m => m.adjusted_show_rate)) : 0;
        const maxB = B.length ? Math.max(...B.map(m => m.adjusted_show_rate)) : 0;

        return maxB - maxA;
    });

    // --- インブリード側 ---
    horseInbreed.value.sort((a, b) => {
        const A = matchedInbreedForHorse(a);
        const B = matchedInbreedForHorse(b);

        const maxA = A.length ? Math.max(...A.map(m => m.show_rate)) : 0;
        const maxB = B.length ? Math.max(...B.map(m => m.show_rate)) : 0;

        return maxB - maxA;
    });
};

// -------------------------------
// 出走馬ごとのデータ取得
// -------------------------------
const loadListItems = async () => {
    if (!selectedListId.value) {
        horseAncestors.value = [];
        horseInbreed.value = [];
        return;
    }

    loadingEntries.value = true;
    horseAncestors.value = [];
    horseInbreed.value = [];

    try {
        // 1) 出走馬リスト
        const res = await axios.get(`/api/horse-lists/${selectedListId.value}/items`);
        listItems.value = res.data;

        // 2) 祖先データ
        const p1 = listItems.value.map((item) =>
            axios.get(`/api/horse/${item.horse_id}/ancestors`).then((r) => ({
                horse_id: item.horse_id,
                horse_name: item.horse_name,
                ancestors: r.data,
            }))
        );
        horseAncestors.value = await Promise.all(p1);

        // ⭐ 祖先分析用の並び順復活！！（複勝率最大値で降順）
        horseAncestors.value.sort((a, b) => {
            const aList = matchedAncestorsForHorse(a);
            const bList = matchedAncestorsForHorse(b);

            const maxA = aList.length ? Math.max(...aList.map(m => m.adjusted_show_rate)) : 0;
            const maxB = bList.length ? Math.max(...bList.map(m => m.adjusted_show_rate)) : 0;

            return maxB - maxA;
        });

        // 3) インブリード
        const p2 = listItems.value.map((item) =>
            axios.get(`/api/horse/${item.horse_id}/inbreed`).then((r) => ({
                horse_id: item.horse_id,
                horse_name: item.horse_name,
                inbreeds: r.data,
            }))
        );
        horseInbreed.value = await Promise.all(p2);

        // ⭐ インブリードタブ用の並び順（最大 show_rate 順）⭐⭐⭐
        horseInbreed.value.sort((a, b) => {

            const listA = matchedInbreedForHorse(a);
            const listB = matchedInbreedForHorse(b);

            const maxA = listA.length ? Math.max(...listA.map(m => m.show_rate)) : 0;
            const maxB = listB.length ? Math.max(...listB.map(m => m.show_rate)) : 0;

            return maxB - maxA;
        });

    } catch (e) {
        console.error("loadListItems error", e);
    } finally {
        loadingEntries.value = false;
    }
};
watch(selectedListId, loadListItems);

// -------------------------------
// 祖先：馬に一致する祖先
// -------------------------------
const matchedAncestorsForHorse = (horse) => {
    if (!rowsAncestor.value.length || !horse.ancestors) return [];

    const ancestorIdSet = new Set(
        horse.ancestors.map((a) => String(a.ancestor_id))
    );

    let list = rowsAncestor.value.filter((r) =>
        ancestorIdSet.has(String(r.ancestor_id))
    );

    if (!showYellow.value) {
        list = list.filter((r) => r.show_rate >= 0.3 || r.show_rate < 0.2);
    }

    return list.sort((a, b) =>
        b.adjusted_show_rate - a.adjusted_show_rate
    );
};

// -------------------------------
// インブリード：馬に一致するインブリード
// -------------------------------
const matchedInbreedForHorse = (horse) => {
    if (!rowsInbreed.value.length || !horse.inbreeds) return [];

    const idSet = new Set(
        horse.inbreeds.map((i) => String(i.ancestor_id))
    );

    return rowsInbreed.value
        .filter((r) => idSet.has(String(r.ancestor_id)))
        .sort((a, b) => b.cross_ratio_percent - a.cross_ratio_percent);
};

// -------------------------------
// バッジ色（祖先＝複勝率）
// -------------------------------
const ancestorBadgeClass = (rate) => {
    if (rate >= 0.4) return "bg-green-100 text-green-800";
    if (rate >= 0.3) return "bg-sky-100 text-sky-800";
    if (rate >= 0.2) return "bg-yellow-100 text-yellow-800";
    return "bg-red-100 text-red-800";
};

// -------------------------------
// バッジ色（インブリード＝血量）
// -------------------------------
const inbreedBadgeClass = (ratio) => {
    if (ratio >= 6) return "bg-green-100 text-green-800";
    if (ratio >= 3) return "bg-sky-100 text-sky-800";
    if (ratio >= 1.5) return "bg-yellow-100 text-yellow-800";
    return "bg-red-100 text-red-800";
};
</script>

<template>
    <div class="p-6 space-y-6">
        <!-- 戻るボタン -->
        <button
            @click="$router.push('/course')"
            class="inline-flex items-center px-3 py-2 mb-4 bg-sky-100 text-sky-700 rounded hover:bg-sky-200 transition"
            >
            ← コース一覧に戻る
        </button>
        <!-- タイトル -->
        <h2 class="text-2xl font-bold">
            コース分析：{{ courseKey }}
            <span class="ml-2 text-sm text-gray-500">（{{ ancestorModeLabel }}）</span>
        </h2>
        <!-- ▼ ベイズ補正の注釈と詳細ポップアップ -->
        <div class="relative text-xs text-gray-500 mt-1">
            ※ 小頭数の偏りを補正する推定を使っています
            <button @click="showInfo = !showInfo" class="text-sky-600 underline">
                （詳しく）
            </button>
            <!-- 詳細ポップアップ -->
            <div
                v-if="showInfo"
                class="absolute z-50 mt-2 w-80 bg-white border border-gray-200 shadow-lg rounded-lg p-4 text-sm"
            >
                <p class="font-bold mb-1">ベイズ推定による補正とは？</p>
                <p class="text-gray-600 mb-2">
                    出走頭数が少ない血統が極端な値（複勝率100%など）にならないよう、
                    全体の平均に少し寄せて補正する手法です。
                </p>
                <p class="text-gray-600">
                    これにより「たまたま好走しただけの血統」が上位に来にくくなり、
                    多くの頭数でより安定している血統を評価しやすくなっています。
                </p>
                <button
                    @click="showInfo = false"
                    class="mt-3 text-xs text-sky-600 underline"
                >
                    閉じる
                </button>
            </div>
        </div>

        <!-- フィルタ -->
        <div class="flex flex-wrap gap-4 mt-4">
            <select v-model="gradeGroup" class="border p-2 pr-8 rounded">
                <option value="G1">G1</option>
                <option value="GRADE">G1/G2/G3</option>
                <option value="OP">OP</option>
                <option value="ALL">ALL</option>
            </select>

            <!-- 年度 -->
            <div class="flex items-center gap-2">
                <select v-model="yearFrom" class="border p-2 pr-8 rounded">
                    <option disabled value="">開始</option>
                    <option
                        v-for="y in [...yearOptions].slice().reverse()"
                        :key="y"
                        :value="y"
                    >
                        {{ y }}
                    </option>
                </select>

                <span>〜</span>

                <select v-model="yearTo" class="border p-2 pr-8 rounded">
                    <option disabled value="">終了</option>
                    <option v-for="y in yearOptions" :key="y" :value="y">
                        {{ y }}
                    </option>
                </select>
            </div>

            <!-- ★ 祖先モード（父方 / 母方 / 全血統） -->
            <select v-model="ancestorMode" class="border p-2 pr-8 rounded">
                <option value="ALL">全血統</option>
                <option value="F">父方のみ</option>
                <option value="M">母方のみ</option>
            </select>
        </div>

        <!-- ▼ タブ -->
        <div class="border-b mt-2">
            <nav class="flex gap-4 text-sm">

                <button
                    class="px-3 py-2 -mb-px border-b-2"
                    :class="activeTab === 'ancestors'
                        ? 'border-sky-500 text-sky-600 font-bold'
                        : 'border-transparent text-gray-500'"
                    @click="activeTab = 'ancestors'">
                    祖先分析
                </button>

                <button
                    class="px-3 py-2 -mb-px border-b-2"
                    :class="activeTab === 'inbreed_table'
                        ? 'border-sky-500 text-sky-600 font-bold'
                        : 'border-transparent text-gray-500'"
                    @click="activeTab = 'inbreed_table'">
                    インブリード分析
                </button>

                <button
                    class="px-3 py-2 -mb-px border-b-2"
                    :class="activeTab === 'entries'
                        ? 'border-sky-500 text-sky-600 font-bold'
                        : 'border-transparent text-gray-500'"
                    @click="activeTab = 'entries'">
                    内包血統チェック
                </button>

                <button
                    class="px-3 py-2 -mb-px border-b-2"
                    :class="activeTab === 'inbreed'
                        ? 'border-sky-500 text-sky-600 font-bold'
                        : 'border-transparent text-gray-500'"
                    @click="activeTab = 'inbreed'">
                    インブリード相性
                </button>

            </nav>
        </div>

        <!-- -------------------------
             ① 祖先分析タブ
        ------------------------- -->
        <div v-if="activeTab === 'ancestors'">
            <div v-if="loadingAncestor">読み込み中...</div>

            <table v-else class="min-w-full bg-white border rounded text-sm">
                <thead class="bg-gray-100 text-xs font-bold">
                    <tr>
                        <th class="px-3 py-2 text-left">祖先</th>
                        <th class="px-3 py-2 text-left">1〜3着 / 着外 (出走頭数)</th>
                        <th class="px-3 py-2 text-left">掲示板 / 掲示板外</th>
                        <th class="px-3 py-2 text-left">勝率 / 連対率 /複勝率 / 掲示板率</th>
                        <th class="px-3 py-2 text-right">評価指数</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="row in rowsAncestor"
                        :key="row.id"
                        class="border-b hover:bg-gray-50"
                        :class="row.start_count < 20 ? 'opacity-50' : ''">

                        <td class="px-3 py-2 font-bold">
                            {{ row.ancestor_name }}
                        </td>

                        <td class="px-3 py-2">
                            {{ row.win_count }} -
                            {{ row.place_count - row.win_count }} -
                            {{ row.show_count - row.place_count }} /
                            {{ row.start_count - row.show_count }}
                            ({{ row.start_count }})
                        </td>

                        <td class="px-3 py-2">
                            ({{ row.board_count }}) /
                            ({{ row.start_count - row.board_count }})
                        </td>

                        <td class="px-3 py-2">
                            {{ safeRate(row.win_rate) }}% -
                            {{ safeRate(row.place_rate) }}% -
                            {{ safeRate(row.show_rate) }}% /
                            {{ safeRate(row.board_rate) }}%
                        </td>

                        <td class="px-3 py-2 text-right">
                            <span v-if="row.score >= 0">
                                {{ row.score.toFixed(2) }}
                            </span>
                            <span v-else class="text-gray-400 text-xs">
                                出走数不足
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- -------------------------
            ④ インブリード分析（一覧表）
        ------------------------- -->
        <div v-if="activeTab === 'inbreed_table'">
            <div v-if="loadingInbreed">読み込み中...</div>

            <table v-else class="min-w-full bg-white border rounded text-sm">
                <thead class="bg-gray-100 text-xs font-bold">
                    <tr>
                        <th class="px-3 py-2 text-left">インブリード祖先</th>
                        <th class="px-3 py-2 text-left">1〜3着 / 着外</th>
                        <th class="px-3 py-2 text-left">掲示板 / 掲示板外</th>
                        <th class="px-3 py-2 text-left">勝率 / 連対率 /複勝率 / 掲示板率</th>
                        <th class="px-3 py-2 text-right">評価指数</th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="row in rowsInbreed"
                        :key="row.id"
                        class="border-b hover:bg-gray-50"
                        :class="row.start_count < 20 ? 'opacity-50' : ''">

                        <td class="px-3 py-2 font-bold">
                            {{ row.ancestor_name }}
                        </td>

                        <td class="px-3 py-2">
                            {{ row.win_count }} -
                            {{ row.place_count - row.win_count }} -
                            {{ row.show_count - row.place_count }} /
                            {{ row.start_count - row.show_count }}
                        </td>

                        <td class="px-3 py-2">
                            ({{ row.board_count }}) /
                            ({{ row.start_count - row.board_count }})
                        </td>

                        <td class="px-3 py-2">
                            {{ safeRate(row.win_rate) }}% -
                            {{ safeRate(row.place_rate) }}% -
                            {{ safeRate(row.show_rate) }}% /
                            {{ safeRate(row.board_rate) }}%
                        </td>

                        <td class="px-3 py-2 text-right">
                            <span v-if="row.score >= 0">
                                {{ row.score.toFixed(2) }}
                            </span>
                            <span v-else class="text-gray-400 text-xs">
                                出走数不足
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- -------------------------
             ② 内包血統チェックタブ
        ------------------------- -->
        <div v-else-if="activeTab === 'entries'">

            <div class="flex items-center gap-3 mb-4">
                <label class="text-sm">出走馬リスト：</label>

                <select v-model="selectedListId" class="border p-2 rounded">
                    <option value="">選択してください</option>

                    <option v-for="list in horseLists" :key="list.id" :value="list.id">
                        {{ list.title }}
                    </option>
                </select>
            </div>

            <label class="flex items-center gap-2 text-sm mt-2">
                <input type="checkbox" v-model="showYellow" />
                黄色の血統も表示する
            </label>

            <div v-if="loadingEntries" class="text-gray-600 mt-2">
                出走馬と血統を読み込み中...
            </div>

            <div v-else-if="!selectedListId" class="mt-2">
                出走馬リストを選択すると、このコースで<strong>相性の良い血統</strong>が表示されます。
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                <div
                    v-for="horse in horseAncestors"
                    :key="horse.horse_id"
                    class="border rounded-lg p-4 bg-white shadow-sm">

                    <div class="font-bold text-lg">{{ horse.horse_name }}</div>
                    <div class="text-xs text-gray-500 mb-2">
                        ID: {{ horse.horse_id }}
                    </div>

                    <div v-if="matchedAncestorsForHorse(horse).length">
                        <div class="text-xs text-gray-500 mb-1">
                            このコースで<strong>相性の良い血統</strong>：
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="anc in matchedAncestorsForHorse(horse)"
                                :key="anc.ancestor_id"
                                :class="[
                                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold',
                                    ancestorBadgeClass(anc.show_rate)
                                ]">
                                {{ anc.ancestor_name }}
                                （複勝 {{ safeRate(anc.show_rate) }}%）
                            </span>
                        </div>
                    </div>

                    <div v-else class="text-xs text-gray-400">
                        この馬は、このコースで特に目立つ祖先がありません。
                    </div>
                </div>
            </div>
        </div>

        <!-- -------------------------
             ③ インブリード相性タブ
        ------------------------- -->
        <div v-else-if="activeTab === 'inbreed'">

            <div class="flex items-center gap-3 mb-4">
                <label class="text-sm">出走馬リスト：</label>

                <select v-model="selectedListId" class="border p-2 rounded">
                    <option value="">選択してください</option>
                    <option v-for="list in horseLists" :key="list.id" :value="list.id">
                        {{ list.title }}
                    </option>
                </select>
            </div>

            <div v-if="loadingEntries" class="text-gray-600 mt-2">
                出走馬とインブリードを読み込み中...
            </div>

            <div v-else-if="!selectedListId">
                出走馬リストを選択すると、このコースで<strong>相性の良いインブリード</strong>が表示されます。
            </div>

            <!-- 出走馬ごとのカード -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div
                    v-for="horse in horseInbreed"
                    :key="horse.horse_id"
                    class="border rounded-lg p-4 bg-white shadow-sm">

                    <div class="font-bold text-lg">{{ horse.horse_name }}</div>
                    <div class="text-xs text-gray-500 mb-2">ID: {{ horse.horse_id }}</div>

                    <div v-if="matchedInbreedForHorse(horse).length">
                        <div class="text-xs text-gray-500 mb-1">
                            このコースで<strong>相性が良いインブリード</strong>：
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="inc in matchedInbreedForHorse(horse)"
                                :key="inc.ancestor_id"
                                :class="[
                                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold',
                                    ancestorBadgeClass(inc.show_rate)   // ← ★複勝率の色を流用
                                ]"
                            >
                                {{ inc.ancestor_name }}
                                （複勝 {{ safeRate(inc.show_rate) }}%）
                            </span>
                        </div>
                    </div>

                    <div v-else class="text-xs text-gray-400">
                        この馬には、このコースで特に強調されるインブリードはありません。
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

