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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};
