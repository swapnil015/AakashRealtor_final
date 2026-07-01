-- ════════════════════════════════════════════════════════════════════
--  Aakash Realtor — PostgreSQL schema (raw SQL equivalent of the Laravel
--  migrations, for developers not using the framework).
--  Target: PostgreSQL 14+
-- ════════════════════════════════════════════════════════════════════

BEGIN;

-- ── Users ────────────────────────────────────────────────────────────
CREATE TYPE user_role AS ENUM ('user', 'agent', 'admin');

CREATE TABLE users (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name              VARCHAR(255) NOT NULL,
    email             VARCHAR(255) NOT NULL UNIQUE,
    phone             VARCHAR(255) UNIQUE,
    email_verified_at TIMESTAMP,
    password          VARCHAR(255) NOT NULL,
    role              user_role NOT NULL DEFAULT 'user',
    avatar            VARCHAR(255),
    is_active         BOOLEAN NOT NULL DEFAULT TRUE,
    branch_id         BIGINT,                 -- FK added after branches
    remember_token    VARCHAR(100),
    created_at        TIMESTAMP,
    updated_at        TIMESTAMP
);
CREATE INDEX users_role_idx      ON users (role);
CREATE INDEX users_is_active_idx ON users (is_active);
CREATE INDEX users_branch_id_idx ON users (branch_id);

-- ── Cities & Areas ───────────────────────────────────────────────────
CREATE TABLE cities (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    public_id  INTEGER NOT NULL UNIQUE,        -- URL id, e.g. Kathmandu-53
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(255) NOT NULL UNIQUE,
    district   VARCHAR(255),
    latitude   NUMERIC(10,7),
    longitude  NUMERIC(10,7),
    is_popular BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE areas (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    city_id    BIGINT NOT NULL REFERENCES cities(id) ON DELETE CASCADE,
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(255) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (city_id, slug)
);

-- ── Taxonomy ─────────────────────────────────────────────────────────
CREATE TABLE categories (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) NOT NULL UNIQUE,
    icon        VARCHAR(255),
    description TEXT,
    has_rooms   BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP
);

CREATE TABLE amenities (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    slug       VARCHAR(255) NOT NULL UNIQUE,
    icon       VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- ── Branches & Teams ─────────────────────────────────────────────────
CREATE TABLE branches (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name           VARCHAR(255) NOT NULL,
    address        VARCHAR(255),
    city_id        BIGINT REFERENCES cities(id) ON DELETE SET NULL,
    phone          VARCHAR(255),
    email          VARCHAR(255),
    map_url        VARCHAR(255),
    is_head_office BOOLEAN NOT NULL DEFAULT FALSE,
    created_at     TIMESTAMP,
    updated_at     TIMESTAMP
);

CREATE TABLE teams (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    position   VARCHAR(255),
    photo      VARCHAR(255),
    branch_id  BIGINT REFERENCES branches(id) ON DELETE SET NULL,
    socials    JSONB,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Deferred FK: users.branch_id -> branches.id
ALTER TABLE users
    ADD CONSTRAINT users_branch_id_fk
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL;

-- ── Properties ───────────────────────────────────────────────────────
CREATE TYPE transaction_type AS ENUM ('buy', 'rent');
CREATE TYPE property_status  AS ENUM ('pending', 'active', 'sold', 'rented', 'rejected');

CREATE TABLE properties (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id          BIGINT NOT NULL REFERENCES users(id)      ON DELETE CASCADE,
    category_id      BIGINT NOT NULL REFERENCES categories(id) ON DELETE RESTRICT,
    city_id          BIGINT NOT NULL REFERENCES cities(id)     ON DELETE RESTRICT,
    area_id          BIGINT REFERENCES areas(id)               ON DELETE SET NULL,
    agent_id         BIGINT REFERENCES users(id)               ON DELETE SET NULL,
    title            VARCHAR(255) NOT NULL,
    slug             VARCHAR(255) NOT NULL UNIQUE,
    description      TEXT,
    transaction_type transaction_type NOT NULL,
    price            NUMERIC(14,2) NOT NULL DEFAULT 0,
    price_unit       VARCHAR(255) NOT NULL DEFAULT 'total',
    price_negotiable BOOLEAN NOT NULL DEFAULT FALSE,
    area_size        NUMERIC(12,2),
    area_unit        VARCHAR(255) NOT NULL DEFAULT 'aana',
    bedrooms         SMALLINT,
    bathrooms        SMALLINT,
    floors           SMALLINT,
    parking          SMALLINT,
    road_width       NUMERIC(6,2),
    facing           VARCHAR(255),
    status           property_status NOT NULL DEFAULT 'pending',
    rejection_reason VARCHAR(255),
    is_featured      BOOLEAN NOT NULL DEFAULT FALSE,
    is_exclusive     BOOLEAN NOT NULL DEFAULT FALSE,
    is_emerging      BOOLEAN NOT NULL DEFAULT FALSE,
    is_open_house    BOOLEAN NOT NULL DEFAULT FALSE,
    is_by_owner      BOOLEAN NOT NULL DEFAULT FALSE,
    open_house_date  DATE,
    latitude         NUMERIC(10,7),
    longitude        NUMERIC(10,7),
    address          VARCHAR(255),
    views            BIGINT NOT NULL DEFAULT 0,
    published_at     TIMESTAMP,
    deleted_at       TIMESTAMP,
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP,
    -- Full-text search vector (auto-maintained).
    searchable       TSVECTOR GENERATED ALWAYS AS (
        setweight(to_tsvector('simple', coalesce(title, '')),       'A') ||
        setweight(to_tsvector('simple', coalesce(address, '')),     'B') ||
        setweight(to_tsvector('simple', coalesce(description, '')), 'C')
    ) STORED
);

CREATE INDEX properties_txn_cat_city_status_idx ON properties (transaction_type, category_id, city_id, status);
CREATE INDEX properties_status_price_idx        ON properties (status, price);
CREATE INDEX properties_status_published_idx    ON properties (status, published_at);
CREATE INDEX properties_searchable_idx          ON properties USING GIN (searchable);

-- ── Property images / amenities / inquiries / favorites ──────────────
CREATE TABLE property_images (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    property_id BIGINT NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    path        VARCHAR(255) NOT NULL,
    url         VARCHAR(255),
    variants    JSONB,
    is_primary  BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order  INTEGER NOT NULL DEFAULT 0,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP
);
CREATE INDEX property_images_prop_sort_idx ON property_images (property_id, sort_order);

CREATE TABLE amenity_property (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    property_id BIGINT NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    amenity_id  BIGINT NOT NULL REFERENCES amenities(id)  ON DELETE CASCADE,
    UNIQUE (property_id, amenity_id)
);

CREATE TYPE inquiry_status AS ENUM ('new', 'contacted', 'closed');
CREATE TABLE property_inquiries (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    property_id BIGINT NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    name        VARCHAR(255) NOT NULL,
    phone       VARCHAR(255) NOT NULL,
    email       VARCHAR(255),
    message     TEXT,
    status      inquiry_status NOT NULL DEFAULT 'new',
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP
);

CREATE TABLE favorites (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id     BIGINT NOT NULL REFERENCES users(id)      ON DELETE CASCADE,
    property_id BIGINT NOT NULL REFERENCES properties(id) ON DELETE CASCADE,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    UNIQUE (user_id, property_id)
);

-- ── Requirements (buyer leads) ───────────────────────────────────────
CREATE TYPE requirement_status AS ENUM ('open', 'fulfilled');
CREATE TABLE requirements (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id          BIGINT REFERENCES users(id) ON DELETE SET NULL,
    name             VARCHAR(255) NOT NULL,
    phone            VARCHAR(255) NOT NULL,
    email            VARCHAR(255),
    category_id      BIGINT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    city_id          BIGINT NOT NULL REFERENCES cities(id)     ON DELETE CASCADE,
    transaction_type transaction_type NOT NULL,
    min_budget       NUMERIC(14,2),
    max_budget       NUMERIC(14,2),
    message          TEXT,
    status           requirement_status NOT NULL DEFAULT 'open',
    last_matched_at  TIMESTAMP,
    created_at       TIMESTAMP,
    updated_at       TIMESTAMP
);
CREATE INDEX requirements_match_idx ON requirements (transaction_type, category_id, city_id, status);

-- ── Content ──────────────────────────────────────────────────────────
CREATE TABLE blogs (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id      BIGINT REFERENCES users(id) ON DELETE SET NULL,
    title        VARCHAR(255) NOT NULL,
    slug         VARCHAR(255) NOT NULL UNIQUE,
    excerpt      VARCHAR(255),
    body         TEXT,
    cover_image  VARCHAR(255),
    published_at TIMESTAMP,
    created_at   TIMESTAMP,
    updated_at   TIMESTAMP
);

CREATE TABLE faqs (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    question   VARCHAR(255) NOT NULL,
    answer     TEXT NOT NULL,
    "group"    VARCHAR(255),
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active  BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

COMMIT;
