<script setup>
import { ref, onMounted, computed } from "vue";
import axios from "axios";

// 全コースデータ
const courses = ref([]);

// フィルタ
const place = ref("");       // 競馬場コード（05, 08 等）
const trackType = ref("");   // TURF / DIRT
const distance = ref("");    // 1200 / 1600 など

// 競馬場マップ（course_key の先頭2桁）
const placeMap = {
    "01": "札幌",
    "02": "函館",
    "03": "福島",
    "04": "新潟",
    "05": "東京",
    "06": "中山",
    "07": "中京",
    "08": "京都",
    "09": "阪神",
    "10": "小倉",
};

// 距離リスト（コース一覧から自動生成）
const distanceList = computed(() => {
    const set = new Set();
    courses.value.forEach((c) => {
        const d = c.course_key.split("-")[2];
        set.add(d);
    });
    return [...set].sort((a, b) => Number(a) - Number(b));
});

// 絞り込みロジック
const filteredCourses = computed(() => {
    return courses.value.filter((c) => {
        const [p, t, d] = c.course_key.split("-");

        if (place.value && place.value !== p) return false;
        if (trackType.value && trackType.value !== t) return false;
        if (distance.value && distance.value !== d) return false;

        return true;
    });
});

// API から一覧取得
onMounted(async () => {
    const res = await axios.get("/api/course-options");
    courses.value = res.data.data ?? [];
});
</script>

<template>
    <div class="p-6 space-y-6">

        <!-- タイトル -->
        <h2 class="text-2xl font-bold">コース一覧</h2>

        <!-- フィルタ -->
        <div class="flex gap-4 flex-wrap">

            <!-- 競馬場 -->
            <select v-model="place" class="border p-2 pr-8 rounded bg-white">
                <option value="">全ての競馬場</option>
                <option
                    v-for="(label, key) in placeMap"
                    :key="key"
                    :value="key"
                >
                    {{ label }}
                </option>
            </select>

            <!-- 馬場（芝/ダ） -->
            <select v-model="trackType" class="border p-2 pr-8 rounded bg-white">
                <option value="">芝 / ダート</option>
                <option value="TURF">芝</option>
                <option value="DIRT">ダート</option>
            </select>

            <!-- 距離 -->
            <select v-model="distance" class="border p-2 pr-8 rounded bg-white">
                <option value="">全距離</option>
                <option
                    v-for="d in distanceList"
                    :key="d"
                    :value="d"
                >
                    {{ d }}m
                </option>
            </select>
        </div>

        <!-- コースカード一覧 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
            <router-link
                v-for="c in filteredCourses"
                :key="c.course_key"
                :to="`/course/${c.course_key}`"
                class="border rounded-lg p-4 shadow-sm bg-white hover:bg-gray-100 transition flex flex-col gap-1"
            >
                <div class="font-bold text-lg">{{ c.course_label }}</div>
                <div class="text-xs text-gray-500">{{ c.course_key }}</div>
            </router-link>
        </div>

    </div>
</template>
