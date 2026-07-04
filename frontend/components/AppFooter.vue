<script setup lang="ts">
const year = new Date().getFullYear()

const cols = [
  {
    title: 'Explore',
    links: [
      { label: 'Buy', to: '/buyHouse' },
      { label: 'Rent', to: '/rentHouse' },
      { label: 'Sell with us', to: '/post' },
      { label: 'Exclusive', to: '/exclusive' },
    ],
  },
  {
    title: 'Company',
    links: [
      { label: 'About', to: '/about' },
      { label: 'Our Team', to: '/team' },
      { label: 'Branches', to: '/branches' },
      { label: 'Insights', to: '/blogs' },
    ],
  },
]
</script>

<template>
  <footer class="w-full bg-ink text-surface">
    <div class="mx-auto max-w-shell px-margin-mobile pt-24 md:px-margin-desktop">
      <div class="grid grid-cols-1 gap-12 pb-16 md:grid-cols-[1.8fr_1fr_1fr_1fr] md:pb-18">
        <div>
          <div class="flex items-baseline gap-2.5">
            <span class="font-display text-[26px] font-semibold text-surface">Aakash</span>
            <span class="text-[11px] font-medium uppercase tracking-[0.32em] text-secondary">Realtor</span>
          </div>
          <p class="mt-5 max-w-[34ch] font-sans text-[14px] leading-[1.7] text-surface/50">
            Nepal's premium marketplace for land, homes and investment properties.
            Kathmandu · Lalitpur · Pokhara.
          </p>
        </div>

        <div v-for="col in cols" :key="col.title">
          <div class="mb-5 text-[11px] uppercase tracking-[0.22em] text-surface/40">{{ col.title }}</div>
          <div class="flex flex-col gap-3">
            <NuxtLink v-for="l in col.links" :key="l.to" :to="l.to"
              class="font-sans text-[14px] text-surface/75 transition-colors hover:text-secondary">
              {{ l.label }}
            </NuxtLink>
          </div>
        </div>

        <div>
          <div class="mb-5 text-[11px] uppercase tracking-[0.22em] text-surface/40">Contact</div>
          <div class="flex flex-col gap-3 font-sans text-[14px] text-surface/75">
            <span>Durbar Marg, Kathmandu</span>
            <a href="tel:+97714002200" class="transition-colors hover:text-secondary">+977 1 4002200</a>
            <a href="mailto:info@aakashrealtor.com" class="transition-colors hover:text-secondary">info@aakashrealtor.com</a>
            <div class="mt-1 flex gap-4">
              <NuxtLink to="/tools/emi" class="text-surface/50 transition-colors hover:text-secondary">EMI</NuxtLink>
              <NuxtLink to="/tools/land-converter" class="text-surface/50 transition-colors hover:text-secondary">Naptol</NuxtLink>
              <NuxtLink to="/tools/date-converter" class="text-surface/50 transition-colors hover:text-secondary">AD–BS</NuxtLink>
              <NuxtLink to="/faqs" class="text-surface/50 transition-colors hover:text-secondary">FAQs</NuxtLink>
            </div>
          </div>
        </div>
      </div>

      <!-- Giant ghost wordmark -->
      <div class="overflow-hidden pb-2">
        <div class="select-none whitespace-nowrap font-display text-[clamp(4rem,11vw,10rem)] leading-[0.95] text-surface/[0.06]">
          Aakash Realtor
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-4 border-t border-surface/10 py-7 md:pb-9">
        <div class="font-sans text-[12px] text-surface/40">
          © {{ year }} Aakash Realtor Pvt. Ltd. — made in Kathmandu, with patience.
        </div>
        <div class="flex gap-6">
          <NuxtLink to="/faqs" class="font-sans text-[12px] text-surface/50 transition-colors hover:text-secondary">Privacy</NuxtLink>
          <NuxtLink to="/faqs" class="font-sans text-[12px] text-surface/50 transition-colors hover:text-secondary">Terms</NuxtLink>
          <a href="https://instagram.com" target="_blank" rel="noopener" class="font-sans text-[12px] text-surface/50 transition-colors hover:text-secondary">Instagram</a>
          <a href="https://facebook.com" target="_blank" rel="noopener" class="font-sans text-[12px] text-surface/50 transition-colors hover:text-secondary">Facebook</a>
        </div>
      </div>
    </div>
  </footer>
</template>
