<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table) {
          
            $table->id();

            $table->enum('type', [
                'entretien_moto',
                'assurance_expiration',
                'vidange_necessaire',
                'versement_retard',
                'moto_en_panne',
                'conducteur_absent',
                'dette_non_remboursee',
            ]);

            // Laravel crée automatiquement :
            // alertable_type
            // alertable_id
            // INDEX(alertable_type, alertable_id)
            $table->morphs('alertable');

            $table->string('message');

            $table->boolean('lue')->default(false);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEX SIMPLES
            |--------------------------------------------------------------------------
            */

            $table->index('type');
            $table->index('lue');
            $table->index('created_at');

            /*
            |--------------------------------------------------------------------------
            | INDEX COMPOSITES
            |--------------------------------------------------------------------------
            */

            // Notifications non lues
            $table->index([
                'lue',
                'created_at'
            ]);

            // Recherche par type
            $table->index([
                'type',
                'created_at'
            ]);

            // Dashboard
            $table->index([
                'alertable_type',
                'alertable_id',
                'lue'
            ]);

            // Historique
            $table->index([
                'alertable_type',
                'alertable_id',
                'created_at'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};
