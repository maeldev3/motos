<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moto_id')->constrained('motos')->cascadeOnDelete();
            $table->date('date_reparation');
            $table->enum('type_reparation', [
                'vidange', 'changement_pneus', 'chaine', 'batterie', 'embrayage',
                'moteur', 'carburateur', 'freins', 'suspension', 'peinture',
                'accident', 'revision_complete', 'autres','renfort_cadre'
            ]);
            $table->text('description')->nullable();
            $table->string('garage')->nullable();
            $table->string('mecanicien')->nullable();
            $table->unsignedInteger('kilometrage')->nullable();
            $table->text('pieces_remplacees')->nullable();
            $table->decimal('montant', 14, 2)->default(0);
            $table->string('photo_facture')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reparations');
    }
};
