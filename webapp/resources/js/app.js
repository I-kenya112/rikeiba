// import './bootstrap';

// import Alpine from 'alpinejs';

// window.Alpine = Alpine;

// Alpine.start();

import '../css/app.css';

import './bootstrap';
import Alpine from 'alpinejs';

import { createApp } from 'vue';
import App from './App.vue';
import { router } from './router';

import axios from 'axios';
axios.defaults.withCredentials = true;

createApp(App)
    .use(router)
    .mount('#app');


// ✅ Livewire が既に Alpine を持っている場合は再初期化しない
if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
} else {
    console.warn('⚠️ Alpine already exists (Livewire handled initialization)');
}
