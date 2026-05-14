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
        Schema::create('outbox_messages', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Jedinstveni identifikator eventa, važan za idempotency downstream servisa.
            $table->uuid('event_id')->unique();

            // Naziv eventa koji opisuje šta se dogodilo u sistemu.
            $table->string('event_name');

            // Verzija event contract-a.
            $table->unsignedSmallInteger('event_version')->default(1);

            // Routing key kojim se event šalje na broker.
            $table->string('routing_key');

            // Stabilan key za latest-state outbox zapis.
            $table->string('deduplication_key')->unique();

            // Glavni payload poruke u JSON formatu.
            $table->jsonb('payload');

            // Dodatni headeri za broker poruku.
            $table->jsonb('headers')->nullable();

            // Status lifecycle-a outbox poruke: pending, published, failed.
            $table->string('status')->default('pending');

            // Broj pokušaja publish-a.
            $table->unsignedInteger('attempts')->default(0);

            // Vreme kada poruka ponovo postaje dostupna za publish attempt.
            $table->timestampTz('available_at')->nullable();

            // Vreme uspešnog publish-a.
            $table->timestampTz('published_at')->nullable();

            // Poslednja greška koja se dogodila tokom publish-a.
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(['status', 'available_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbox_messages');
    }
};
