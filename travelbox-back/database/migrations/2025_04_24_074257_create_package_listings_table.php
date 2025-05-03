<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('package_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('pickup_location_id')->constrained('locations');
            $table->foreignId('delivery_location_id')->constrained('locations');
            $table->date('needed_by');
            $table->decimal('weight', 8, 2); // en kg
            $table->decimal('width', 8, 2)->nullable(); // en cm
            $table->decimal('height', 8, 2)->nullable(); // en cm
            $table->decimal('length', 8, 2)->nullable(); // en cm
            $table->text('description');
            $table->boolean('active')->default(true); 
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_listings');
    }
};

