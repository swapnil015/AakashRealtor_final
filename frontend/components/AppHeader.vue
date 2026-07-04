<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const route = useRoute()

const mobileOpen = ref(false)
const scrolled = ref(false)

function onScroll() { scrolled.value = window.scrollY > 40 }
onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})
onUnmounted(() => window.removeEventListener('scroll', onScroll))

watch(() => route.fullPath, () => { mobileOpen.value = false })

const links = [
  { label: 'Home', to: '/' },
  { label: 'Properties', to: '/buyHouse' },
  { label: 'Insights', to: '/blogs' },
  { label: 'About', to: '/about' },
  { label: 'Contact', to: '/contact' },
]

function isActive(to: string) {
  return to === '/' ? route.path === '/' : route.path.startsWith(to)
}

async function doLogout() {
  await auth.logout()
  navigateTo('/')
}
</script>

<template>
  <header
    class="fixed top-0 z-50 w-full backdrop-blur-2xl transition-all duration-500"
    :class="scrolled || route.path !== '/' ? 'bg-navy/90 shadow-[0_8px_32px_rgba(11,19,43,0.35)]' : 'bg-navy/40'"
  >
    <div
      class="mx-auto flex w-full max-w-shell items-center justify-between px-margin-mobile transition-all duration-500 ease-smooth md:px-margin-desktop"
      :class="scrolled ? 'py-3' : 'py-3 md:py-[22px]'"
    >
      <NuxtLink to="/" class="flex items-baseline gap-2.5">
        <span class="font-display text-[22px] font-semibold tracking-[0.02em] text-surface">Aakash</span>
        <span class="text-[11px] font-medium uppercase tracking-[0.32em] text-secondary">Realtor</span>
      </NuxtLink>

      <nav class="hidden items-center gap-9 md:flex">
        <NuxtLink v-for="l in links" :key="l.to" :to="l.to"
          class="nav-link pb-1 font-sans text-[12px] font-medium uppercase tracking-[0.1em] transition-colors"
          :class="isActive(l.to) ? 'nav-link--active text-surface' : 'text-surface/85 hover:text-surface'">
          {{ l.label }}
        </NuxtLink>

        <span class="hidden cursor-pointer font-sans text-[12px] font-medium uppercase tracking-[0.1em] text-surface/60 transition-colors hover:text-secondary lg:block">EN/नेपाली</span>

        <template v-if="auth.isLoggedIn">
          <NuxtLink to="/dashboard" class="font-sans text-[12px] font-medium uppercase tracking-[0.1em] text-surface/85 hover:text-secondary">
            {{ auth.user?.name?.split(' ')[0] || 'Account' }}
          </NuxtLink>
          <button class="font-sans text-[12px] font-medium uppercase tracking-[0.1em] text-surface/60 hover:text-secondary" @click="doLogout">Logout</button>
        </template>
        <template v-else>
          <NuxtLink to="/login" class="font-sans text-[12px] font-medium uppercase tracking-[0.1em] text-surface/85 transition-colors hover:text-secondary">
            Login
          </NuxtLink>
        </template>

        <NuxtLink to="/post"
          class="rounded-full bg-secondary px-6 py-3 font-sans text-[12px] font-semibold uppercase tracking-[0.1em] text-navy transition-all duration-300 ease-smooth hover:bg-secondary-fixed-dim hover:-translate-y-0.5 hover:shadow-gold">
          List Property
        </NuxtLink>
      </nav>

      <button class="text-surface md:hidden" aria-label="Menu" @click="mobileOpen = !mobileOpen">
        <span class="material-symbols-outlined">{{ mobileOpen ? 'close' : 'menu' }}</span>
      </button>
    </div>

    <!-- Mobile drawer -->
    <Transition name="page">
      <div v-if="mobileOpen" class="border-t border-surface/10 bg-navy/95 backdrop-blur-2xl md:hidden">
        <div class="px-margin-mobile py-5">
          <NuxtLink v-for="l in links" :key="l.to" :to="l.to"
            class="block py-3 font-sans text-[13px] font-medium uppercase tracking-[0.12em] text-surface/90">{{ l.label }}</NuxtLink>
          <hr class="my-3 border-surface/10" />
          <NuxtLink to="/post" class="mt-1 block rounded-full bg-secondary py-3.5 text-center font-sans text-[12px] font-semibold uppercase tracking-[0.15em] text-navy">List Property</NuxtLink>
          <NuxtLink v-if="!auth.isLoggedIn" to="/login" class="mt-3 block rounded-full border border-surface/30 py-3.5 text-center font-sans text-[12px] font-semibold uppercase tracking-[0.15em] text-surface">Login / Register</NuxtLink>
          <template v-else>
            <NuxtLink to="/dashboard" class="mt-3 block rounded-full border border-surface/30 py-3.5 text-center font-sans text-[12px] font-semibold uppercase tracking-[0.15em] text-surface">My Account</NuxtLink>
          </template>
        </div>
      </div>
    </Transition>
  </header>
</template>

<style scoped>
/* Gold underline that grows from the left on hover (comp navbar treatment). */
.nav-link {
  background-image: linear-gradient(#C7A76C, #C7A76C);
  background-repeat: no-repeat;
  background-position: left bottom;
  background-size: 0% 1.5px;
  transition: background-size 0.4s cubic-bezier(0.22, 1, 0.36, 1), color 0.3s;
}
.nav-link:hover,
.nav-link--active {
  background-size: 100% 1.5px;
}
</style>
