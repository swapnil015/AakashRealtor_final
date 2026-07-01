<script setup lang="ts">
/**
 * Nepal-only location picker. Renders an interactive Leaflet map locked to
 * Nepal's bounds. Clicking (or dragging the marker) drops a pin, then reverse-
 * geocodes the point via OpenStreetMap Nominatim and emits the resolved
 * address parts so the parent can auto-fill the location fields.
 *
 * Client-only (`.client.vue`) — Leaflet touches `window`/`document`.
 */
const props = defineProps<{ lat?: number | null; lng?: number | null }>()
const emit = defineEmits<{
  (e: 'picked', payload: {
    lat: number
    lng: number
    address: string
    city: string
    area: string
    district: string
    province: string
  }): void
}>()

// Nepal bounding box (SW → NE), padded a touch so the border isn't flush.
const NEPAL_BOUNDS: [[number, number], [number, number]] = [
  [26.30, 79.95],
  [30.50, 88.25],
]
const NEPAL_CENTER: [number, number] = [28.3949, 84.124]

const mapEl = ref<HTMLElement | null>(null)
const status = ref<'idle' | 'locating' | 'done' | 'error'>('idle')
const picked = ref<{ lat: number; lng: number } | null>(null)
let map: any = null
let marker: any = null
let L: any = null

/** Inject Leaflet's CSS + JS from CDN once. */
function loadLeaflet(): Promise<any> {
  if ((window as any).L) return Promise.resolve((window as any).L)

  if (!document.getElementById('leaflet-css')) {
    const link = document.createElement('link')
    link.id = 'leaflet-css'
    link.rel = 'stylesheet'
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(link)
  }

  return new Promise((resolve, reject) => {
    const s = document.createElement('script')
    s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    s.async = true
    s.onload = () => resolve((window as any).L)
    s.onerror = () => reject(new Error('Failed to load Leaflet'))
    document.head.appendChild(s)
  })
}

async function reverseGeocode(lat: number, lng: number) {
  status.value = 'locating'
  try {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2`
      + `&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1&accept-language=en`
    const res: any = await $fetch(url, { headers: { 'Accept': 'application/json' } })
    const a = res?.address ?? {}

    // Map OSM fields onto our domain shape (Nepal-aware fallbacks).
    const city = a.city || a.town || a.municipality || a.city_district
      || a.county || a.state_district || ''
    const area = a.suburb || a.neighbourhood || a.quarter || a.village
      || a.hamlet || a.city_district || ''
    const district = a.state_district || a.county || ''
    const province = a.state || a.region || ''
    const address = [a.road, a.neighbourhood || a.suburb, a.city || a.town || a.village]
      .filter(Boolean).join(', ') || res?.display_name?.split(',').slice(0, 3).join(', ') || ''

    emit('picked', { lat, lng, address, city, area, district, province })
    status.value = 'done'
  } catch {
    // Geocoding failed — still emit the coordinates so lat/lng auto-fill.
    emit('picked', { lat, lng, address: '', city: '', area: '', district: '', province: '' })
    status.value = 'error'
  }
}

function placeMarker(lat: number, lng: number, geocode = true) {
  picked.value = { lat, lng }
  if (!marker) {
    marker = L.marker([lat, lng], { draggable: true }).addTo(map)
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

  // Ensure marker icons resolve from the CDN (bundler-free setup).
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
    maxBoundsViscosity: 1.0, // hard-lock panning to Nepal
    scrollWheelZoom: true,
  })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    bounds: NEPAL_BOUNDS,
  }).addTo(map)

  map.fitBounds(NEPAL_BOUNDS)

  // Draw Nepal's outline (best-effort; ignored if the fetch fails).
  try {
    const geo: any = await $fetch(
      'https://raw.githubusercontent.com/johan/world.geo.json/master/countries/NPL.geo.json',
    )
    L.geoJSON(geo, {
      style: { color: '#C9A227', weight: 2, fill: false, opacity: 0.9 },
    }).addTo(map)
  } catch { /* outline optional */ }

  map.on('click', (e: any) => placeMarker(e.latlng.lat, e.latlng.lng))

  // Restore an existing pin (e.g. navigating back to this step).
  if (props.lat != null && props.lng != null) {
    placeMarker(props.lat, props.lng, false)
    map.setView([props.lat, props.lng], 13)
  }
})

onUnmounted(() => { if (map) map.remove() })
</script>

<template>
  <div>
    <div class="relative overflow-hidden rounded-xl border border-slate-200">
      <div ref="mapEl" class="h-[340px] w-full" />
      <!-- Status pill -->
      <div class="pointer-events-none absolute left-3 top-3 z-[500] rounded-lg bg-white/95 px-3 py-1.5 text-xs font-semibold shadow-card backdrop-blur">
        <span v-if="status === 'idle'">📍 Tap on the map to set your property location</span>
        <span v-else-if="status === 'locating'" class="text-gold-hover">Detecting address…</span>
        <span v-else-if="status === 'done'" class="text-green-600">✓ Location set — fields auto-filled</span>
        <span v-else class="text-amber-600">Pin dropped — enter address manually if needed</span>
      </div>
    </div>
    <p v-if="picked" class="mt-2 text-xs text-muted">
      Selected: {{ picked.lat.toFixed(5) }}, {{ picked.lng.toFixed(5) }} · drag the pin to fine-tune
    </p>
  </div>
</template>
