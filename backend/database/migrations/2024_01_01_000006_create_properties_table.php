<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            // Ownership / taxonomy / location.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('city_id')->constrained('cities')->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            // Agent assigned by admin to manage this listing (separate from owner).
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();

            // Identity.
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Deal.
            $table->enum('transaction_type', ['buy', 'rent'])->index();
            $table->decimal('price', 14, 2)->default(0);
            $table->string('price_unit')->default('total'); // total | per month | per year
            $table->boolean('price_negotiable')->default(false);

            // Size.
            $table->decimal('area_size', 12, 2)->nullable();
            $table->string('area_unit')->default('aana');   // aana | ropani | sqft | sqm

            // Specs (nullable — land listings won't have rooms).
            $table->unsignedSmallInteger('bedrooms')->nullable();
            $table->unsignedSmallInteger('bathrooms')->nullable();
            $table->unsignedSmallInteger('floors')->nullable();
            $table->unsignedSmallInteger('parking')->nullable();
            $table->decimal('road_width', 6, 2)->nullable(); // in feet
            $table->string('facing')->nullable();            // east, west, ...

            // Moderation lifecycle: pending -> active -> sold/rented (or rejected).
            $table->enum('status', ['pending', 'active', 'sold', 'rented', 'rejected'])
                  ->default('pending')->index();
            $table->string('rejection_reason')->nullable();

            // Homepage placement flags.
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_exclusive')->default(false)->index();
            $table->boolean('is_emerging')->default(false)->index();
            $table->boolean('is_open_house')->default(false)->index();
            $table->boolean('is_by_owner')->default(false)->index();
            $table->date('open_house_date')->nullable();

            // Geo + address.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address')->nullable();

            // Metrics + publishing.
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamp('published_at')->nullable()->index();

            $table->softDeletes();
            $table->timestamps();

            // Composite indexes tuned for the public filter/sort matrix.
            $table->index(['transaction_type', 'category_id', 'city_id', 'status']);
            $table->index(['status', 'price']);
            $table->index(['status', 'published_at']);
        });

        // ── Full-text search (PostgreSQL tsvector) ──────────────────────
        // A generated column keeps the search vector in sync automatically;
        // a GIN index makes `q` keyword queries fast. Guarded so the schema
        // still migrates on sqlite (used by the test suite).
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE properties
                ADD COLUMN searchable tsvector
                GENERATED ALWAYS AS (
                    setweight(to_tsvector('simple', coalesce(title, '')), 'A') ||
                    setweight(to_tsvector('simple', coalesce(address, '')), 'B') ||
                    setweight(to_tsvector('simple', coalesce(description, '')), 'C')
                ) STORED
            SQL);

            DB::statement('CREATE INDEX properties_searchable_idx ON properties USING GIN (searchable)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
