<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('versements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moto_id')->constrained('motos')->cascadeOnDelete();
            $table->foreignId('conducteur_id')->nullable()->constrained('conducteurs')->nullOnDelete();
            $table->date('date_versement');
            $table->enum('periodicite', ['journalier', 'hebdomadaire', 'mensuel', 'annuel'])->default('journalier');
            $table->decimal('montant_attendu', 14, 2);
            $table->decimal('montant_verse', 14, 2)->default(0);
            $table->decimal('reste_a_payer', 14, 2)->default(0);
            $table->boolean('en_retard')->default(false);
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index('moto_id');

            $table->index('conducteur_id');

            $table->index('date_versement');

            $table->index('periodicite');

            $table->index('en_retard');

            $table->index('created_at');

            // Historique des versements d'une moto
            $table->index([
                'moto_id',
                'date_versement'
            ]);

            // Historique des versements d'un conducteur
            $table->index([
                'conducteur_id',
                'date_versement'
            ]);

            // Dashboard par période
            $table->index([
                'date_versement',
                'periodicite'
            ]);

            // Paiements en retard
            $table->index([
                'en_retard',
                'date_versement'
            ]);

            // Historique d'une moto par période
            $table->index([
                'moto_id',
                'periodicite',
                'date_versement'
            ]);

            // Historique d'un conducteur par période
            $table->index([
                'conducteur_id',
                'periodicite',
                'date_versement'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versements');
    }
};
