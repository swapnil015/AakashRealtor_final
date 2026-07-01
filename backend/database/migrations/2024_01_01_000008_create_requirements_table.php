<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Buyer "Didn't find a property?" requests. The matcher job pairs new active
 * properties against open requirements by category / city / budget.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->enum('transaction_type', ['buy', 'rent'])->index();
            $table->decimal('min_budget', 14, 2)->nullable();
            $table->decimal('max_budget', 14, 2)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['open', 'fulfilled'])->default('open')->index();
            $table->timestamp('last_matched_at')->nullable();
            $table->timestamps();

            $table->index(['transaction_type', 'category_id', 'city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
