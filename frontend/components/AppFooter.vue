<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<template>
  <footer class="mt-section-gap w-full border-t border-outline-variant bg-surface-container-highest">
    <div class="mx-auto grid max-w-shell grid-cols-1 gap-content-gap px-margin-mobile py-section-gap md:grid-cols-4 md:px-margin-desktop">
      <div class="md:col-span-1">
        <h2 class="mb-4 font-display text-headline-sm uppercase text-primary">Aakash Realtor</h2>
        <p class="font-sans text-body-md text-on-surface-variant">
          The definitive source for luxury real estate and land assets in the Federal
          Democratic Republic of Nepal. Every listing verified against its lalpurja.
        </p>
      </div>

      <div class="flex flex-col space-y-2">
        <p class="eyebrow mb-2">Quick Links</p>
        <NuxtLink to="/about" class="font-sans text-body-md text-on-surface-variant link-underline">About Us</NuxtLink>
        <NuxtLink to="/contact" class="font-sans text-body-md text-on-surface-variant link-underline">Branch Locations</NuxtLink>
        <NuxtLink to="/blogs" class="font-sans text-body-md text-on-surface-variant link-underline">Market Reports</NuxtLink>
        <NuxtLink to="/faqs" class="font-sans text-body-md text-on-surface-variant link-underline">FAQs</NuxtLink>
      </div>

      <div class="flex flex-col space-y-2">
        <p class="eyebrow mb-2">Locations</p>
        <p class="font-sans text-body-md text-on-surface-variant">Kathmandu (H.O)</p>
        <p class="font-sans text-body-md text-on-surface-variant">Pokhara Lakeside</p>
        <p class="font-sans text-body-md text-on-surface-variant">Butwal Industrial</p>
      </div>

      <div class="flex flex-col space-y-2">
        <p class="eyebrow mb-2">Explore</p>
        <NuxtLink to="/buyHouse" class="font-sans text-body-md text-on-surface-variant link-underline">Buy</NuxtLink>
        <NuxtLink to="/rentHouse" class="font-sans text-body-md text-on-surface-variant link-underline">Rent</NuxtLink>
        <NuxtLink to="/exclusive" class="font-sans text-body-md text-on-surface-variant link-underline">Exclusive</NuxtLink>
        <NuxtLink to="/tools/emi" class="font-sans text-body-md text-on-surface-variant link-underline">Tools</NuxtLink>
      </div>
    </div>

    <div class="border-t border-outline-variant px-margin-mobile py-8 text-center">
      <p class="font-sans text-body-md text-on-surface-variant opacity-70">
        © {{ year }} Aakash Realtor. Licensed Real Estate Brokerage · Made in Kathmandu 🇳🇵
      </p>
    </div>
  </footer>
</template>
