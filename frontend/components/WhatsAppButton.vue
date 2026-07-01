<script setup lang="ts">
const config = useRuntimeConfig()
const href = computed(
  () => `https://wa.me/${config.public.whatsapp}?text=${encodeURIComponent('Hi Aakash Realtor, I have a property question.')}`,
)
</script>

<template>
  <a :href="href" target="_blank" rel="noopener" aria-label="Chat on WhatsApp"
     class="fixed bottom-7 right-7 z-40 grid h-14 w-14 place-items-center rounded-full bg-[#25D366] text-2xl shadow-[0_14px_32px_-8px_rgba(37,211,102,.6)] transition hover:-translate-y-0.5">
    💬
  </a>
</template>
