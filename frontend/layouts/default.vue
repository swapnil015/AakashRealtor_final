<script setup lang="ts">
// Global shell: fixed editorial header (h-20 → pt-20 on main), page content,
// footer, floating WhatsApp, scroll-to-top FAB.
const showTop = ref(false)
function onScroll() { showTop.value = window.scrollY > 100 }
onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }))
onUnmounted(() => window.removeEventListener('scroll', onScroll))
function toTop() { window.scrollTo({ top: 0, behavior: 'smooth' }) }
</script>

<template>
  <div class="flex min-h-screen flex-col bg-surface">
    <AppHeader />
    <main class="flex-1 pt-20">
      <slot />
    </main>
    <AppFooter />
    <WhatsAppButton />

    <!-- Scroll-to-top FAB -->
    <Transition name="page">
      <button v-if="showTop" aria-label="Scroll to top"
        class="fixed bottom-8 left-8 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-primary text-white shadow-xl transition-all hover:scale-110 active:scale-95"
        @click="toTop">
        <span class="material-symbols-outlined">arrow_upward</span>
      </button>
    </Transition>
  </div>
</template>
