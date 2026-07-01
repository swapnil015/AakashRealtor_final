<script setup lang="ts">
const config = useRuntimeConfig()

useHead({
  titleTemplate: (t) => (t ? `${t} · Aakash Realtor` : 'Aakash Realtor — Nepal Property Marketplace'),
})

useSeoMeta({
  ogType: 'website',
  ogSiteName: 'Aakash Realtor',
  twitterCard: 'summary_large_image',
})
</script>

<template>
  <div>
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </div>
</template>
