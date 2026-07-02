<script setup lang="ts">
/**
 * Nepal-only location picker. Interactive Leaflet map bounded to Nepal.
 * Click / drag-pin to drop a marker, then reverse-geocode via OSM Nominatim
 * and emit the resolved address parts for the parent to auto-fill.
 *
 * Client-only (`.client.vue`) — Leaflet needs `window`/`document`.
 */
const props = defineProps<{ lat?: number | null; lng?: number | null }>()
const emit = defineEmits<{
  (e: 'picked', payload: {
    lat: number; lng: number; address: string
    city: string; area: string; district: string; province: string
  }): void
}>()

// Nepal bounding box (SW → NE), padded so there's comfortable room to pan.
const NEPAL_BOUNDS: [[number, number], [number, number]] = [
  [26.0, 79.6],
  [30.8, 88.6],
]
const NEPAL_CENTER: [number, number] = [28.3949, 84.124]

const mapEl = ref<HTMLElement | null>(null)
const status = ref<'idle' | 'locating' | 'done' | 'error'>('idle')
const picked = ref<{ lat: number; lng: number } | null>(null)
const ready = ref(false)
let map: any = null
let marker: any = null
let L: any = null
let ro: ResizeObserver | null = null

/** Load a stylesheet and resolve once it has actually applied. */
function loadCss(id: string, href: string): Promise<void> {
  if (document.getElementById(id)) return Promise.resolve()
  return new Promise((resolve) => {
    const link = document.createElement('link')
    link.id = id
    link.rel = 'stylesheet'
    link.href = href
    link.onload = () => resolve()
    link.onerror = () => resolve() // don't block map on CSS error
    document.head.appendChild(link)
  })
}

function loadScript(src: string): Promise<any> {
  if ((window as any).L) return Promise.resolve((window as any).L)
  return new Promise((resolve, reject) => {
    const s = document.createElement('script')
    s.src = src
    s.async = true
    s.onload = () => resolve((window as any).L)
    s.onerror = () => reject(new Error('Failed to load Leaflet'))
    document.head.appendChild(s)
  })
}

// IMPORTANT: await the CSS before init, otherwise tiles render misaligned and
// dragging feels broken.
async function loadLeaflet(): Promise<any> {
  await loadCss('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css')
  return loadScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js')
}

async function reverseGeocode(lat: number, lng: number) {
  status.value = 'locating'
  try {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2`
      + `&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1&accept-language=en`
    const res: any = await $fetch(url, { headers: { Accept: 'application/json' } })
    const a = res?.address ?? {}
    const city = a.city || a.town || a.municipality || a.city_district
      || a.county || a.state_district || ''
    const area = a.suburb || a.neighbourhood || a.quarter || a.village
      || a.hamlet || a.city_district || ''
    const district = a.state_district || a.county || ''
    const province = a.state || a.region || ''
    const address = [a.road, a.neighbourhood || a.suburb, a.city || a.town || a.village]
      .filter(Boolean).join(', ')
      || res?.display_name?.split(',').slice(0, 3).join(', ') || ''
    emit('picked', { lat, lng, address, city, area, district, province })
    status.value = 'done'
  } catch {
    emit('picked', { lat, lng, address: '', city: '', area: '', district: '', province: '' })
    status.value = 'error'
  }
}

function placeMarker(lat: number, lng: number, geocode = true) {
  picked.value = { lat, lng }
  if (!marker) {
    marker = L.marker([lat, lng], { draggable: true, autoPan: true }).addTo(map)
    marker.on('dragend', () => {
      const p = marker.getLatLng()
      placeMarker(p.lat, p.lng)
    })
  } else {
    marker.setLatLng([lat, lng])
  }
  if (geocode) reverseGeocode(lat, lng)
}

onMounted(async () => {
  try {
    L = await loadLeaflet()
  } catch {
    status.value = 'error'
    return
  }

  L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  })

  map = L.map(mapEl.value, {
    center: NEPAL_CENTER,
    zoom: 7,
    minZoom: 6,
    maxZoom: 18,
    maxBounds: NEPAL_BOUNDS,
    maxBoundsViscosity: 0.4,   // soft edge — panning stays smooth, not sticky
    scrollWheelZoom: true,     // mouse wheel zooms
    doubleClickZoom: true,
    dragging: true,
    zoomControl: true,
    tap: true,
    inertia: true,
    worldCopyJump: false,
  })

  L.control.scale({ imperial: false }).addTo(map)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  map.fitBounds([[26.36, 80.06], [30.45, 88.20]]) // frame Nepal on load

  // Nepal outline (best-effort).
  try {
    const geo: any = await $fetch(
      'https://raw.githubusercontent.com/johan/world.geo.json/master/countries/NPL.geo.json',
    )
    L.geoJSON(geo, { style: { color: '#C9A227', weight: 2, fill: false, opacity: 0.9 } }).addTo(map)
  } catch { /* optional */ }

  map.on('click', (e: any) => placeMarker(e.latlng.lat, e.latlng.lng))

  // Restore an existing pin (navigating back to this step).
  if (props.lat != null && props.lng != null) {
    placeMarker(props.lat, props.lng, false)
    map.setView([props.lat, props.lng], 13)
  }

  // Fix sizing once visible + whenever the container resizes (responsive).
  const fix = () => map && map.invalidateSize()
  setTimeout(fix, 60)
  setTimeout(fix, 300)
  if ('ResizeObserver' in window && mapEl.value) {
    ro = new ResizeObserver(fix)
    ro.observe(mapEl.value)
  }
  window.addEventListener('resize', fix)
  ;(map as any).__fix = fix
  ready.value = true
})

onUnmounted(() => {
  if (ro) ro.disconnect()
  if (map?.__fix) window.removeEventListener('resize', map.__fix)
  if (map) map.remove()
})

// Recenter if the parent programmatically changes the coordinates.
watch(() => [props.lat, props.lng], ([la, ln]) => {
  if (map && la != null && ln != null && (!picked.value
      || picked.value.lat !== la || picked.value.lng !== ln)) {
    placeMarker(la as number, ln as number, false)
    map.setView([la, ln], 13)
  }
})
</script>

<template>
  <div>
    <div class="relative overflow-hidden rounded-xl border border-slate-200 shadow-card">
      <div ref="mapEl" class="h-[380px] w-full sm:h-[460px] cursor-crosshair" />

      <!-- Loading shim until Leaflet is ready -->
      <div v-if="!ready" class="absolute inset-0 z-[400] grid place-items-center bg-sand/80 text-sm font-semibold text-muted">
        Loading map of Nepal…
      </div>

      <!-- Status pill (doesn't block map interaction) -->
      <div class="pointer-events-none absolute left-3 top-3 z-[500] rounded-lg bg-white/95 px-3 py-1.5 text-xs font-semibold shadow-card backdrop-blur">
        <span v-if="status === 'idle'">📍 Tap the map to set your location</span>
        <span v-else-if="status === 'locating'" class="text-gold-hover">Detecting address…</span>
        <span v-else-if="status === 'done'" class="text-green-600">✓ Location set — fields auto-filled</span>
        <span v-else class="text-amber-600">Pin dropped — enter address manually if needed</span>
      </div>

      <!-- Gesture hint (bottom) -->
      <div class="pointer-events-none absolute bottom-2 right-2 z-[500] rounded-md bg-ink/70 px-2.5 py-1 text-[11px] font-medium text-white/90 backdrop-blur">
        Scroll to zoom · drag to pan · drag the pin to fine-tune
      </div>
    </div>
    <p v-if="picked" class="mt-2 text-xs text-muted">
      Selected: {{ picked.lat.toFixed(5) }}, {{ picked.lng.toFixed(5) }}
    </p>
  </div>
</template>

<style scoped>
/* Guarantee the Leaflet panes fill the container and stay below the header. */
:deep(.leaflet-container) {
  height: 100%;
  width: 100%;
  font: inherit;
  background: #dfe6e4;
  z-index: 0;
}
:deep(.leaflet-control-zoom a) {
  border-radius: 8px;
}
</style>
