import { createRouter, createWebHistory } from 'vue-router';

import CourseIndexPage from './pages/CourseIndexPage.vue';
import CourseDetailPage from './pages/CourseDetailPage.vue';

const routes = [
    { path: '/course', name: 'CourseList', component: CourseIndexPage },
    {
        path: "/course/:courseKey",
        name: "course.detail",
        component: CourseDetailPage,
    }
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});
