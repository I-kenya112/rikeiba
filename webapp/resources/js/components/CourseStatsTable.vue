<template>
  <table class="w-full border-collapse">
    <thead>
      <tr class="bg-gray-100">
        <th class="border p-2 text-left">祖先名</th>
        <th class="border p-2 text-right">出走</th>
        <th class="border p-2 text-right">勝率</th>
      </tr>
    </thead>

    <tbody>
      <tr v-for="r in rows" :key="r.ancestor_uid">
        <td class="border p-2">{{ r.ancestor_name }}</td>
        <td class="border p-2 text-right">{{ r.start_count }}</td>
        <td class="border p-2 text-right">
          {{ (r.win_rate * 100).toFixed(1) }}%
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
defineProps({
  rows: {
    type: Array,
    required: true,
  },
});
</script>
