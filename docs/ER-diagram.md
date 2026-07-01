# Aakash Realtor — Entity Relationship Diagram

```mermaid
erDiagram
    users ||--o{ properties : "owns (user_id)"
    users ||--o{ properties : "manages (agent_id)"
    users ||--o{ requirements : submits
    users ||--o{ favorites : saves
    users ||--o{ blogs : authors
    users }o--|| branches : "assigned to"

    cities ||--o{ areas : has
    cities ||--o{ properties : locates
    cities ||--o{ requirements : targets
    cities ||--o{ branches : hosts

    areas ||--o{ properties : "within"

    categories ||--o{ properties : classifies
    categories ||--o{ requirements : classifies

    properties ||--o{ property_images : has
    properties ||--o{ property_inquiries : receives
    properties ||--o{ favorites : "saved in"
    properties }o--o{ amenities : "amenity_property"

    branches ||--o{ teams : staffs

    users {
        bigint id PK
        string name
        string email UK
        string phone UK
        string password
        enum role "user|agent|admin"
        string avatar
        bool is_active
        bigint branch_id FK
    }

    cities {
        bigint id PK
        int public_id UK "URL id e.g. 53"
        string name
        string slug UK
        string district
    }

    areas {
        bigint id PK
        bigint city_id FK
        string name
        string slug
    }

    categories {
        bigint id PK
        string name
        string slug UK
        bool has_rooms
    }

    amenities {
        bigint id PK
        string name
        string slug UK
        string icon
    }

    properties {
        bigint id PK
        bigint user_id FK
        bigint category_id FK
        bigint city_id FK
        bigint area_id FK "nullable"
        bigint agent_id FK "nullable"
        string title
        string slug UK
        text description
        enum transaction_type "buy|rent"
        decimal price
        string price_unit
        decimal area_size
        string area_unit
        smallint bedrooms
        smallint bathrooms
        smallint floors
        smallint parking
        decimal road_width
        string facing
        enum status "pending|active|sold|rented|rejected"
        bool is_featured
        bool is_exclusive
        bool is_emerging
        bool is_open_house
        bool is_by_owner
        decimal latitude
        decimal longitude
        string address
        bigint views
        timestamp published_at
        timestamp deleted_at "soft delete"
    }

    property_images {
        bigint id PK
        bigint property_id FK
        string path
        json variants "small|medium|large|webp"
        bool is_primary
        int sort_order
    }

    amenity_property {
        bigint id PK
        bigint property_id FK
        bigint amenity_id FK
    }

    property_inquiries {
        bigint id PK
        bigint property_id FK
        string name
        string phone
        string email
        text message
        enum status "new|contacted|closed"
    }

    requirements {
        bigint id PK
        bigint user_id FK "nullable"
        string name
        string phone
        bigint category_id FK
        bigint city_id FK
        enum transaction_type "buy|rent"
        decimal min_budget
        decimal max_budget
        enum status "open|fulfilled"
    }

    favorites {
        bigint id PK
        bigint user_id FK
        bigint property_id FK
    }

    branches {
        bigint id PK
        string name
        string address
        bigint city_id FK
        string phone
        string map_url
    }

    teams {
        bigint id PK
        string name
        string position
        string photo
        bigint branch_id FK
        json socials
    }

    blogs {
        bigint id PK
        bigint user_id FK
        string title
        string slug UK
        string excerpt
        text body
        string cover_image
        timestamp published_at
    }

    faqs {
        bigint id PK
        string question
        text answer
        string group
        int sort_order
    }
```

## Lifecycle notes

- **Property moderation:** `pending → active` (admin approves) → `sold` / `rented`,
  or `rejected`. Only `active` listings are returned to the public API.
- **Homepage placement** is driven by the boolean flags
  (`is_featured`, `is_exclusive`, `is_emerging`, `is_open_house`, `is_by_owner`),
  toggled by admins in Filament.
- **Requirement matching:** when a property becomes `active`, a queued job
  (`MatchRequirementsToProperty`) pairs it against `open` requirements sharing
  `transaction_type` + `category_id` + `city_id` with price inside the budget band.
