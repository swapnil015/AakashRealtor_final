// Client-only motion directives used across the site. Both are no-ops when
// the user prefers reduced motion.
//
//   v-magnetic          — element subtly follows the cursor on hover
//   v-count="2800"      — animates 0 → value when it scrolls into view
//                         modifiers/args: v-count:suffix, or pass an object
//                         { to, suffix, prefix, duration }

function prefersReduced(): boolean {
  return typeof window !== 'undefined'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches
}

export default defineNuxtPlugin((nuxtApp) => {
  /* ── Magnetic hover ─────────────────────────────────────────────── */
  nuxtApp.vueApp.directive('magnetic', {
    mounted(el: HTMLElement, binding) {
      if (prefersReduced()) return
      const strength = Number(binding.value) || 0.3
      el.style.transition = 'transform .25s cubic-bezier(.16,1,.3,1)'
      el.style.willChange = 'transform'

      const onMove = (e: MouseEvent) => {
        const r = el.getBoundingClientRect()
        const dx = e.clientX - (r.left + r.width / 2)
        const dy = e.clientY - (r.top + r.height / 2)
        el.style.transform = `translate(${dx * strength}px, ${dy * strength}px)`
      }
      const reset = () => { el.style.transform = '' }

      el.addEventListener('mousemove', onMove)
      el.addEventListener('mouseleave', reset)
      ;(el as any).__magnetic = { onMove, reset }
    },
    unmounted(el: HTMLElement) {
      const h = (el as any).__magnetic
      if (h) {
        el.removeEventListener('mousemove', h.onMove)
        el.removeEventListener('mouseleave', h.reset)
      }
    },
  })

  /* ── Count-up on scroll ─────────────────────────────────────────── */
  nuxtApp.vueApp.directive('count', {
    mounted(el: HTMLElement, binding) {
      const opts = typeof binding.value === 'object' && binding.value !== null
        ? binding.value
        : { to: Number(binding.value) || 0 }
      const to = Number(opts.to) || 0
      const suffix = opts.suffix ?? ''
      const prefix = opts.prefix ?? ''
      const duration = opts.duration ?? 1600

      const fmt = (n: number) => prefix + Math.round(n).toLocaleString('en-IN') + suffix

      if (prefersReduced() || typeof IntersectionObserver === 'undefined') {
        el.textContent = fmt(to)
        return
      }

      el.textContent = fmt(0)
      let started = false
      const run = () => {
        const start = performance.now()
        const tick = (now: number) => {
          const p = Math.min(1, (now - start) / duration)
          const eased = 1 - Math.pow(1 - p, 3) // easeOutCubic
          el.textContent = fmt(to * eased)
          if (p < 1) requestAnimationFrame(tick)
        }
        requestAnimationFrame(tick)
      }

      const io = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting && !started) {
            started = true
            run()
            io.unobserve(e.target)
          }
        })
      }, { threshold: 0.4 })
      io.observe(el)
    },
  })
})
