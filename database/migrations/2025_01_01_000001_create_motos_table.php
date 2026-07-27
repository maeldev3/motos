<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('motos', function (Blueprint $table) {
            $table->id();
            $table->string('immatriculation')->unique();
            $table->string('marque');
            $table->string('modele');
            $table->string('couleur')->nullable();
            $table->unsignedSmallInteger('annee_fabrication')->nullable();
            $table->string('numero_chassis')->nullable()->unique();
            $table->string('numero_moteur')->nullable()->unique();
            $table->date('date_achat')->nullable();
            $table->decimal('prix_achat', 14, 2)->nullable();
            $table->string('photo')->nullable();

            // Type de véhicule : moto ou voiture (cahier des charges mentionne aussi les voitures)
            $table->enum('type_vehicule', ['moto', 'voiture'])->default('moto');

            // Montant de versement configurable selon le type de véhicule
            // Moto : 600 000 Ar / mois (par défaut) - Voiture : 100 000 Ar / jour (par défaut)
            $table->decimal('montant_versement_mensuel', 14, 2)->default(600000);
            $table->decimal('montant_versement_journalier', 14, 2)->default(0);

            $table->enum('statut', [
                'disponible',
                'en_circulation',
                'en_reparation',
                'en_entretien',
                'accidentee',
                'hors_service',
                'vendue',
            ])->default('disponible');

            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motos');
    }
};
