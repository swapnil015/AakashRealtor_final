<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<template>
  <footer class="w-full border-t border-outline-variant bg-surface-container-highest px-margin-mobile py-section-gap md:px-margin-desktop">
    <div class="mx-auto max-w-shell">
      <div class="mb-16 grid grid-cols-1 gap-gutter md:grid-cols-4">
        <div class="md:col-span-1">
          <h2 class="mb-6 font-display text-headline-sm text-primary">Aakash Realtor</h2>
          <p class="font-sans text-body-md leading-relaxed text-on-surface-variant">
            Nepal's premier agency for strategic land acquisition and luxury residential
            portfolios. Founded on discretion, heritage, and trust.
          </p>
        </div>

        <div class="flex flex-col gap-4">
          <h3 class="mb-2 font-sans text-label-caps font-bold uppercase text-primary">Explore</h3>
          <NuxtLink to="/buyHouse" class="font-sans text-body-md text-on-surface-variant transition-all hover:underline decoration-1 underline-offset-4">Featured Listings</NuxtLink>
          <NuxtLink to="/buyLand" class="font-sans text-body-md text-on-surface-variant transition-all hover:underline decoration-1 underline-offset-4">Investment Land</NuxtLink>
          <NuxtLink to="/buyCommercial" class="font-sans text-body-md text-on-surface-variant transition-all hover:underline decoration-1 underline-offset-4">Commercial Assets</NuxtLink>
          <NuxtLink to="/exclusive" class="font-sans text-body-md text-on-surface-variant transition-all hover:underline decoration-1 underline-offset-4">Heritage Properties</NuxtLink>
        </div>

        <div class="flex flex-col gap-4">
          <h3 class="mb-2 font-sans text-label-caps font-bold uppercase text-primary">Company</h3>
          <NuxtLink to="/about" class="font-sans text-body-md text-on-surface-variant transition-all hover:underline decoration-1 underline-offset-4">About Us</NuxtLink>
          <NuxtLink to="/blogs" class="font-sans text-body-md text-on-surface-variant transition-all hover:underline decoration-1 underline-offset-4">Market Insights</NuxtLink>
          <NuxtLink to="/faqs" class="font-sans text-body-md text-on-surface-variant transition-all hover:underline decoration-1 underline-offset-4">FAQs</NuxtLink>
          <NuxtLink to="/tools/emi" class="font-sans text-body-md text-on-surface-variant transition-all hover:underline decoration-1 underline-offset-4">Tools</NuxtLink>
        </div>

        <div class="flex flex-col gap-4">
          <h3 class="mb-2 font-sans text-label-caps font-bold uppercase text-primary">Connect</h3>
          <span class="font-sans text-body-md text-on-surface-variant">Durbar Marg, Kathmandu / Sanepa, Lalitpur</span>
          <span class="font-sans text-body-md text-on-surface-variant">info@aakashrealtor.com</span>
          <span class="font-sans text-body-md text-on-surface-variant">+977 1 4002200</span>
          <div class="mt-2 flex gap-4">
            <span class="material-symbols-outlined cursor-pointer text-primary hover:text-secondary">share</span>
            <span class="material-symbols-outlined cursor-pointer text-primary hover:text-secondary">public</span>
          </div>
        </div>
      </div>

      <div class="flex flex-col items-center justify-between border-t border-outline-variant pt-8 md:flex-row">
        <p class="font-sans text-body-md text-on-surface-variant opacity-70">
          © {{ year }} Aakash Realtor. All Rights Reserved. Built for Trust.
        </p>
        <div class="mt-4 flex gap-6 md:mt-0">
          <span class="font-sans text-label-caps uppercase text-secondary">BS 2081 Calendar</span>
          <span class="font-sans text-label-caps uppercase text-outline">KTM | PKH | BWA</span>
        </div>
      </div>
    </div>
  </footer>
</template>
