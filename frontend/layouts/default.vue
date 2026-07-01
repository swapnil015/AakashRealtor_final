<script setup lang="ts">
// Global shell: sticky header, page content, footer, floating WhatsApp.
</script>

<template>
  <div class="min-h-screen flex flex-col bg-canvas">
    <ScrollProgress />
    <AppHeader />
    <main class="flex-1">
      <slot />
    </main>
    <AppFooter />
    <WhatsAppButton />
  </div>
</template>
