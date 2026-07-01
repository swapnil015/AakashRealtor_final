<script setup lang="ts">
// Thin gold bar at the very top that tracks scroll depth of the page.
const progress = ref(0)

function onScroll() {
  const max = document.documentElement.scrollHeight - window.innerHeight
  progress.value = max > 0 ? (window.scrollY / max) * 100 : 0
}

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll, { passive: true })
})
onUnmounted(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
})
</script>

<template>
  <div
    class="fixed inset-x-0 top-0 z-[120] h-[3px] origin-left bg-gold transition-[width] duration-150 ease-out"
    :style="{ width: progress + '%' }"
    aria-hidden="true"
  />
</template>
