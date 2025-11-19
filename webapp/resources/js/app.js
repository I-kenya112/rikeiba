// import './bootstrap';

// import Alpine from 'alpinejs';

// window.Alpine = Alpine;

// Alpine.start();


import './bootstrap';
import Alpine from 'alpinejs';

// ✅ Livewire が既に Alpine を持っている場合は再初期化しない
if (!window.Alpine) {
    window.Alpine = Alpine;
    Alpine.start();
} else {
    console.warn('⚠️ Alpine already exists (Livewire handled initialization)');
}
