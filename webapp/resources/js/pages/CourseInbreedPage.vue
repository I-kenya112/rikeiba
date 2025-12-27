<script setup>
import { ref, onMounted, watch } from "vue";
import axios from "axios";
import { useRoute } from "vue-router";

const route = useRoute();
const courseKey = ref(route.params.courseKey);

// ------------------
// コース・インブリード成績
// ------------------
const rows = ref([]);
const loading = ref(false);

// フィルタ
const gradeGroup = ref("ALL");
const years = ref("");
const sortKey = ref("cross_ratio_percent");

// 年度（API から取得）
const yearOptions = ref([]);

// タブ
const activeTab = ref("inbreed");

// 黄色表示 ON/OFF
const showYellow = ref(false);

// 出走馬リスト関連
const horseLists = ref([]);
const selectedListId = ref("");
const listItems = ref([]);
const horseInbreeds = ref([]);
const loadingEntries = ref(false);

// ------------------------------------------------------------
// 1. コース別インブリード成績の取得
// ------------------------------------------------------------
const fetchStats = async () => {
    loading.value = true;

    const res = await axios.get(`/api/course/${courseKey.value}/inbreed-stats`, {
        params: {
            grade: gradeGroup.value,
            years: years.value,
            sort: sortKey.value,
        },
    });

    rows.value = res.data.data || [];
    yearOptions.value = res.data.years_range_options || [];

    // 初回ロード時、自動セット
    if (!years.value && yearOptions.value.length > 0) {
        years.value = yearOptions.value[0];
    }

    loading.value = false;
};

onMounted(() => {
    fetchStats();
    fetchHorseLists();
});

watch([gradeGroup, years, sortKey], fetchStats);

// ------------------------------------------------------------
// 2. 出走馬リスト一覧
// ------------------------------------------------------------
const fetchHorseLists = async () => {
    try {
        const res = await axios.get("/api/horse-lists");
        horseLists.value = res.data;
    } catch (e) {
        console.warn("horse-lists API error", e);
    }
};

// ------------------------------------------------------------
// 3. 出走馬ごとのインブリード情報取得
// ------------------------------------------------------------
const loadListItems = async () => {
    if (!selectedListId.value) {
        listItems.value = [];
        horseInbreeds.value = [];
        return;
    }

    loadingEntries.value = true;

    try {
        // 出走馬一覧
        const res = await axios.get(`/api/horse-lists/${selectedListId.value}/items`);
        listItems.value = res.data;

        // 各馬のインブリード情報を取得
        const promises = listItems.value.map((item) =>
            axios.get(`/api/horse/${item.horse_id}/inbreed`).then((r) => ({
                horse_id: item.horse_id,
                horse_name: item.horse_name,
                inbreeds: r.data,
            }))
        );

        horseInbreeds.value = await Promise.all(promises);

        // 並べ替え（最大血量の高い馬順）
        horseInbreeds.value.sort((a, b) => {
            const maxA = a.inbreeds.length ? Math.max(...a.inbreeds.map(m => m.cross_ratio_percent)) : 0;
            const maxB = b.inbreeds.length ? Math.max(...b.inbreeds.map(m => m.cross_ratio_percent)) : 0;
            return maxB - maxA;
        });

    } catch (e) {
        console.error("loadListItems error", e);
    } finally {
        loadingEntries.value = false;
    }
};
watch(selectedListId, loadListItems);

// ------------------------------------------------------------
// 4. このコースに相性のいいインブリード（ID一致）を抽出
// ------------------------------------------------------------
const matchedInbreedsForHorse = (horse) => {
    if (!rowsInbreed.value.length || !horse.inbreeds?.length) return [];

    const idSet = new Set(horse.inbreeds.map(i => String(i.ancestor_id)));

    // コース別インブリード統計を持っているものだけ抽出
    let list = rowsInbreed.value.filter((r) =>
        idSet.has(String(r.ancestor_id))
    );

    // 黄色OFF → 20〜30%帯非表示
    if (!showYellow.value) {
        list = list.filter((r) => {
            const rate = r.show_rate;
            return rate >= 0.30 || rate < 0.20;
        });
    }

    // 複勝率で並び替え
    list.sort((a, b) => b.show_rate - a.show_rate);

    return list;
};

// ------------------------------------------------------------
// バッジ色（血量ベース）
// ------------------------------------------------------------
const inbreedBadgeClass = (rate) => {
    if (rate >= 0.40) return "bg-green-100 text-green-800";
    if (rate >= 0.30) return "bg-sky-100 text-sky-800";
    if (rate >= 0.20) return "bg-yellow-100 text-yellow-800";
    return "bg-red-100 text-red-800";
};
</script>

<template>
    <div class="p-6 space-y-6">

        <h2 class="text-2xl font-bold">コース × インブリード相性：{{ courseKey }}</h2>

        <!-- フィルタ -->
        <div class="flex flex-wrap gap-4">
            <select v-model="gradeGroup" class="border p-2 rounded bg-white">
                <option value="G1">G1</option>
                <option value="GRADE">G1/G2/G3</option>
                <option value="OP">OP</option>
                <option value="ALL">ALL</option>
            </select>

            <!-- 年度（動的） -->
            <select v-model="years" class="border p-2 rounded bg-white">
                <option disabled value="">年数を選択</option>
                <option v-for="y in yearOptions" :key="y" :value="y">
                    {{ y.replace('-', '〜') }}
                </option>
            </select>

            <select v-model="sortKey" class="border p-2 rounded bg-white">
                <option value="cross_ratio_percent">血量（%）</option>
                <option value="start_count">出走数</option>
                <option value="win_rate">勝率</option>
                <option value="show_rate">複勝率</option>
            </select>
        </div>

        <!-- 出走馬チェック -->
        <div class="border-b mt-4">
            <nav class="flex gap-4 text-sm">
                <button
                    class="px-3 py-2 -mb-px border-b-2"
                    :class="activeTab === 'inbreed'
                        ? 'border-sky-500 text-sky-600 font-bold'
                        : 'border-transparent text-gray-500'"
                    @click="activeTab = 'inbreed'"
                >
                    出走馬インブリード相性
                </button>
            </nav>
        </div>

        <!-- 出走馬 UI -->
        <div>
            <div class="flex items-center gap-3 mb-4 mt-4">
                <label class="text-sm">出走馬リスト：</label>
                <select v-model="selectedListId" class="border p-2 rounded">
                    <option value="">選択してください</option>
                    <option v-for="list in horseLists" :key="list.id" :value="list.id">
                        {{ list.title }}
                    </option>
                </select>
            </div>

            <!-- 黄色ON/OFF -->
            <label class="flex items-center gap-2 text-sm mt-2">
                <input type="checkbox" v-model="showYellow" />
                黄色（20〜30%）も表示する
            </label>

            <div v-if="loadingEntries" class="text-gray-600 mt-3">
                出走馬とインブリード情報を読み込み中...
            </div>

            <div v-else-if="!selectedListId" class="mt-3 text-gray-500">
                出走馬リストを選択すると、
                <strong>このコースと相性の良いインブリード</strong>
                が表示されます。
            </div>

            <!-- 馬 × インブリード相性 -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div
                    v-for="horse in horseInbreeds"
                    :key="horse.horse_id"
                    class="border rounded-lg p-4 bg-white shadow-sm">

                    <div class="font-bold text-lg">{{ horse.horse_name }}</div>
                    <div class="text-xs text-gray-500 mb-2">ID: {{ horse.horse_id }}</div>

                    <div v-if="matchedInbreedsForHorse(horse).length">
                        <div class="text-xs text-gray-500 mb-1">
                            このコースと相性の良いインブリード：
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="inc in matchedInbreedsForHorse(horse)"
                                :key="inc.ancestor_id"
                                :class="[
                                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold',
                                    inbreedBadgeClass(inc.cross_ratio_percent)
                                ]"
                            >
                                {{ inc.ancestor_name }}
                                （{{ inc.cross_ratio_percent }}%）
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
