<script setup lang="ts">
const year = new Date().getFullYear()
</script>

<template>
  <footer class="bg-ink px-5 pb-9 pt-20 text-slate-300 sm:px-8">
    <div class="mx-auto grid max-w-[1280px] gap-12 border-b border-ink-line pb-12 md:grid-cols-[1.6fr_1fr_1fr_1fr]">
      <div>
        <div class="mb-4 flex items-center gap-3">
          <span class="grid h-10 w-10 place-items-center rounded-[10px] bg-gold font-display text-xl font-bold text-ink">A</span>
          <span class="font-display text-lg font-bold tracking-wide text-white">AAKASH REALTOR</span>
        </div>
        <p class="max-w-xs text-sm leading-relaxed text-slate-400">
          Nepal's curated property marketplace — every listing verified against its lalpurja.
        </p>
        <div class="mt-5 flex gap-3">
          <a v-for="s in ['f','in','◎']" :key="s" href="#"
             class="grid h-9 w-9 place-items-center rounded-lg border border-ink-line text-white transition hover:-translate-y-0.5 hover:border-gold hover:bg-gold hover:text-ink">
            {{ s }}
          </a>
        </div>
      </div>

      <div>
        <div class="mb-4 text-sm font-bold text-white">Company</div>
        <ul class="space-y-3 text-sm">
          <li><NuxtLink to="/about" class="text-slate-400 transition hover:text-white">About Us</NuxtLink></li>
          <li><NuxtLink to="/team" class="text-slate-400 transition hover:text-white">Our Team</NuxtLink></li>
          <li><NuxtLink to="/branches" class="text-slate-400 transition hover:text-white">Branches</NuxtLink></li>
          <li><NuxtLink to="/blogs" class="text-slate-400 transition hover:text-white">Blog</NuxtLink></li>
        </ul>
      </div>

      <div>
        <div class="mb-4 text-sm font-bold text-white">Explore</div>
        <ul class="space-y-3 text-sm">
          <li><NuxtLink to="/buyHouse" class="text-slate-400 transition hover:text-white">Buy</NuxtLink></li>
          <li><NuxtLink to="/rentHouse" class="text-slate-400 transition hover:text-white">Rent</NuxtLink></li>
          <li><NuxtLink to="/exclusive" class="text-slate-400 transition hover:text-white">Exclusive</NuxtLink></li>
          <li><NuxtLink to="/requirements" class="text-slate-400 transition hover:text-white">Post a Requirement</NuxtLink></li>
        </ul>
      </div>

      <div>
        <div class="mb-4 text-sm font-bold text-white">Tools</div>
        <ul class="space-y-3 text-sm">
          <li><NuxtLink to="/tools/emi" class="text-slate-400 transition hover:text-white">EMI Calculator</NuxtLink></li>
          <li><NuxtLink to="/tools/land-converter" class="text-slate-400 transition hover:text-white">Land Converter</NuxtLink></li>
          <li><NuxtLink to="/tools/date-converter" class="text-slate-400 transition hover:text-white">Date Converter</NuxtLink></li>
          <li><NuxtLink to="/faqs" class="text-slate-400 transition hover:text-white">FAQs</NuxtLink></li>
        </ul>
      </div>
    </div>

    <div class="mx-auto mt-7 flex max-w-[1280px] flex-wrap items-center justify-between gap-4">
      <p class="max-w-2xl text-xs leading-relaxed text-slate-500">
        © {{ year }} Aakash Realtor Pvt. Ltd. Listing information is believed reliable but not guaranteed;
        verify all details and lalpurja independently before any transaction.
      </p>
      <div class="text-xs font-medium text-slate-500">Made in Kathmandu 🇳🇵</div>
    </div>
  </footer>
</template>
