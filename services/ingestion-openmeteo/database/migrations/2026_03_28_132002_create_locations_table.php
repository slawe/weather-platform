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
        Schema::create('locations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Naziv grada ili lokacije koju pratimo.
            $table->string('name');

            // Država je korisna za prikaz, audit i buduće grupisanje.
            $table->string('country');

            // Geografske koordinate koristimo za poziv eksternog weather API-ja.
            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);

            // Omogućava da lokaciju privremeno isključimo bez brisanja iz baze.
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
