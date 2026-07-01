<script setup lang="ts">
import type { Pagination } from '~/composables/useApi'

const props = defineProps<{ pagination: Pagination }>()
const emit = defineEmits<{ change: [page: number] }>()

// Compact page window around the current page.
const pages = computed(() => {
  const { current_page: c, last_page: l } = props.pagination
  const out: (number | '…')[] = []
  const push = (n: number | '…') => out.push(n)
  const lo = Math.max(1, c - 2)
  const hi = Math.min(l, c + 2)
  if (lo > 1) { push(1); if (lo > 2) push('…') }
  for (let i = lo; i <= hi; i++) push(i)
  if (hi < l) { if (hi < l - 1) push('…'); push(l) }
  return out
})
</script>

<template>
  <nav v-if="pagination.last_page > 1" class="mt-12 flex items-center justify-center gap-2">
    <button
      class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-sm font-semibold transition hover:border-gold disabled:opacity-40"
      :disabled="pagination.current_page <= 1"
      @click="emit('change', pagination.current_page - 1)"
    >‹</button>

    <button
      v-for="(p, i) in pages" :key="i"
      class="grid h-10 min-w-10 place-items-center rounded-lg border px-3 text-sm font-semibold transition"
      :class="p === pagination.current_page
        ? 'border-gold bg-gold text-ink'
        : 'border-slate-200 bg-white hover:border-gold'"
      :disabled="p === '…'"
      @click="p !== '…' && emit('change', p as number)"
    >{{ p }}</button>

    <button
      class="grid h-10 w-10 place-items-center rounded-lg border border-slate-200 bg-white text-sm font-semibold transition hover:border-gold disabled:opacity-40"
      :disabled="!pagination.has_more"
      @click="emit('change', pagination.current_page + 1)"
    >›</button>
  </nav>
</template>
