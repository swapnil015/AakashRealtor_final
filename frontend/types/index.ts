// Mirrors the backend API Resources (PropertyResource etc.).

export interface City {
  id: number
  public_id: number
  name: string
  slug: string
  district: string | null
  is_popular: boolean
  url_token: string // "Kathmandu-53"
  properties_count?: number
  areas?: Area[]
}

export interface Area {
  id: number
  name: string
  slug: string
  city_id: number
}

export interface Category {
  id: number
  name: string
  slug: string
  icon: string | null
  has_rooms: boolean
  properties_count?: number
}

export interface Amenity {
  id: number
  name: string
  slug: string
  icon: string | null
}

export interface PropertyImage {
  id: number
  url: string | null
  is_primary: boolean
  sort_order: number
  sizes: { small?: string; medium?: string; large?: string; webp?: string }
}

export interface Agent {
  id: number
  name: string
  role: string
  avatar: string | null
  phone: string | null
  email?: string
}

export interface Property {
  id: number
  title: string
  slug: string
  description?: string
  transaction_type: 'buy' | 'rent'
  status: string
  price: { amount: number; unit: string; negotiable: boolean; formatted: string }
  area: { size: number | null; unit: string }
  specs: {
    bedrooms: number | null
    bathrooms: number | null
    floors: number | null
    parking: number | null
    road_width: number | null
    facing: string | null
  }
  flags: {
    is_featured: boolean
    is_exclusive: boolean
    is_emerging: boolean
    is_open_house: boolean
    is_by_owner: boolean
  }
  open_house_date: string | null
  location: {
    address: string | null
    latitude: number | null
    longitude: number | null
    city?: City
    area?: Area
  }
  category?: Category
  amenities?: Amenity[]
  images?: PropertyImage[]
  primary_image: string | null
  agent?: Agent
  views: number
  published_at: string | null
  created_at: string
  url: string
  similar?: Property[]
}
