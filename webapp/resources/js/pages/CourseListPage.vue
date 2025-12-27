<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">コース一覧</h1>

    <div v-if="loading" class="text-gray-500">読み込み中...</div>

    <div v-else class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="c in courses"
        :key="c.course_key"
        class="p-4 border rounded hover:bg-gray-100 cursor-pointer"
        @click="goToCourse(c.course_key)"
      >
        <div class="text-lg font-semibold">{{ c.course_label }}</div>
        <div class="text-sm text-gray-500">{{ c.course_key }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();

const courses = ref([]);
const loading = ref(true);

onMounted(async () => {
  const res = await axios.get('/api/course-options');
  courses.value = res.data.data;
  loading.value = false;
});

const goToCourse = (key) => router.push('/course/' + key);
</script>
