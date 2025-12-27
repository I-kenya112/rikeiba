<template>
  <div>
    <div v-if="loading" class="text-gray-500">読み込み中...</div>

    <CourseStatsTable v-else :rows="rows" />
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import CourseStatsTable from './CourseStatsTable.vue';

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
});

const route = useRoute();
const courseKey = route.params.course_key;

const loading = ref(false);
const rows = ref([]);

const fetchStats = async () => {
  loading.value = true;

  const res = await axios.get(`/api/course/${courseKey}/ancestor-stats`, {
    params: props.filters,
  });

  rows.value = res.data.data;
  loading.value = false;
};

watch(
  () => ({ ...props.filters }),
  fetchStats,
  { deep: true }
);

onMounted(fetchStats);
</script>
