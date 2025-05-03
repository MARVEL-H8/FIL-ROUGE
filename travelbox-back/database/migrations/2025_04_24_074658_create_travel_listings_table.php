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
        Schema::create('travel_listings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('departure_location_id')->constrained('locations');
            $table->foreignId('destination_location_id')->constrained('locations');
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->decimal('available_weight', 8, 2); // en kg
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
        Schema::dropIfExists('travel_listings');
    }
};

