<script setup lang="ts">
// Scroll-reveal wrapper using IntersectionObserver. Supports directional
// entrances (up / left / right / scale), a stagger delay, and respects
// reduced-motion + SSR (reveals instantly when IO is unavailable).
const props = withDefaults(
  defineProps<{
    delay?: number
    as?: string
    dir?: 'up' | 'left' | 'right' | 'scale' | 'fade'
    once?: boolean
  }>(),
  { delay: 0, as: 'div', dir: 'up', once: true },
)

const el = ref<HTMLElement | null>(null)
const shown = ref(false)

// The hidden-state transform per direction.
const hiddenClass = computed(() => {
  switch (props.dir) {
    case 'left': return 'opacity-0 -translate-x-10'
    case 'right': return 'opacity-0 translate-x-10'
    case 'scale': return 'opacity-0 scale-95'
    case 'fade': return 'opacity-0'
    default: return 'opacity-0 translate-y-8'
  }
})

onMounted(() => {
  if (
    typeof IntersectionObserver === 'undefined'
    || window.matchMedia('(prefers-reduced-motion: reduce)').matches
  ) {
    shown.value = true
    return
  }
  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          shown.value = true
          if (props.once) io.unobserve(e.target)
        } else if (!props.once) {
          shown.value = false
        }
      })
    },
    { threshold: 0.12, rootMargin: '0px 0px -7% 0px' },
  )
  if (el.value) io.observe(el.value)
})
</script>

<template>
  <component
    :is="as"
    ref="el"
    class="transition-all duration-700 ease-smooth will-change-transform"
    :class="shown ? 'opacity-100 translate-x-0 translate-y-0 scale-100' : hiddenClass"
    :style="{ transitionDelay: `${delay}s` }"
  >
    <slot />
  </component>
</template>
