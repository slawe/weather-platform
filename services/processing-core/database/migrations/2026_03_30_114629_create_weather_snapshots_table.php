<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kreira read model tabelu za vremenske snapshote.
     *
     * Ova tabela predstavlja rezultat obrade incoming eventa
     * weather.snapshot.fetched.
     */
    public function up(): void
    {
        Schema::create('weather_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Event koji je proizveo ovaj read model zapis.
            $table->uuid('event_id')->unique();

            // Identitet lokacije iz producer sistema.
            $table->uuid('location_id')->index();

            $table->string('city');
            $table->string('country');

            $table->decimal('latitude', 10, 6);
            $table->decimal('longitude', 10, 6);

            $table->decimal('temperature_c', 8, 2);
            $table->decimal('wind_speed_kmh', 8, 2);
            $table->integer('weather_code');

            // Vreme na koje se meteorološko merenje odnosi.
            $table->timestampTz('observed_at');

            // Iz kog external source-a je podatak došao.
            $table->string('source');

            // Vreme kada je processing servis upisao ovaj snapshot.
            $table->timestampTz('received_at');

            $table->timestamps();

            $table->unique(['location_id', 'source']);
            $table->index(['location_id', 'observed_at']);
            $table->index(['source', 'observed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_snapshots');
    }
};
