<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kreira tabelu za evidenciju već obrađenih eventa.
     *
     *  Ova tabela je osnova za idempotency mehanizam.
     *  Ako isti event stigne više puta, preko unique event_id možemo da
     *  sprečimo duplu obradu i duple side-effecte.
     */
    public function up(): void
    {
        Schema::create('consumed_events', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Jedinstveni identifikator incoming eventa.
            $table->uuid('event_id')->unique();

            // Naziv eventa je koristan za audit i troubleshooting.
            $table->string('event_name');

            // Vreme kada je event uspešno obrađen.
            $table->timestampTz('consumed_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumed_events');
    }
};
