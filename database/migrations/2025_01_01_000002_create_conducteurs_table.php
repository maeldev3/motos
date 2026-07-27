<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conducteurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['homme', 'femme'])->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('adresse')->nullable();
            $table->string('telephone')->unique();
            $table->string('cin')->unique()->nullable();
            $table->string('numero_permis')->nullable();
            $table->date('date_embauche')->nullable();
            $table->string('photo')->nullable();
            $table->string('contact_urgence_nom')->nullable();
            $table->string('contact_urgence_telephone')->nullable();

            $table->enum('statut', ['actif', 'suspendu', 'inactif'])->default('actif');

            // Moto actuellement affectée (référence rapide, en plus de la table affectations)
            $table->foreignId('moto_id')->nullable()->constrained('motos')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conducteurs');
    }
};
