<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moto_id')->nullable()->constrained('motos')->nullOnDelete();
            $table->date('date_depense');
            $table->enum('categorie', [
                'reparation', 'entretien', 'assurance', 'carburant', 'huile_moteur',
                'pneus', 'batterie', 'lavage', 'parking', 'carte_grise', 'taxes',
                'amendes', 'accessoires', 'divers','renfort_cadre'
            ]);
            $table->decimal('montant', 14, 2);
            $table->string('justificatif')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['moto_id', 'date_depense']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
