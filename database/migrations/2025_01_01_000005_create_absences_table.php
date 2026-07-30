<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conducteur_id')->constrained('conducteurs')->cascadeOnDelete();
            $table->enum('type', ['absence', 'maladie', 'conge', 'accident', 'autorisation']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedInteger('nombre_jours')->default(0);
            $table->decimal('retenue', 14, 2)->default(0);
            $table->text('motif')->nullable();
            $table->timestamps();
              /*
                |--------------------------------------------------------------------------
                | INDEX SQL
                |--------------------------------------------------------------------------
                */

                // Recherche par conducteur
                $table->index('conducteur_id');

                // Filtre par type
                $table->index('type');

                // Recherche par date de début
                $table->index('date_debut');

                // Recherche par date de fin
                $table->index('date_fin');

                // Tri chronologique
                $table->index('created_at');

                /*
                |--------------------------------------------------------------------------
                | INDEX COMPOSITES
                |--------------------------------------------------------------------------
                */

                // Historique d'un conducteur
                $table->index([
                    'conducteur_id',
                    'date_debut'
                ]);

                // Historique sur une période
                $table->index([
                    'conducteur_id',
                    'date_debut',
                    'date_fin'
                ]);

                // Statistiques par type
                $table->index([
                    'type',
                    'date_debut'
                ]);

                // Dashboard mensuel
                $table->index([
                    'date_debut',
                    'date_fin'
                ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
