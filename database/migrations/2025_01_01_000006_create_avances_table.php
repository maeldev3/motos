<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('avances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conducteur_id')->constrained('conducteurs')->cascadeOnDelete();
            $table->enum('type', ['avance', 'provision'])->default('avance');
            $table->decimal('montant', 14, 2);
            $table->decimal('montant_rembourse', 14, 2)->default(0);
            $table->decimal('solde', 14, 2)->default(0);
            $table->date('date_octroi');
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });

        Schema::create('avance_remboursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avance_id')->constrained('avances')->cascadeOnDelete();
            $table->decimal('montant', 14, 2);
            $table->date('date_remboursement');
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avance_remboursements');
        Schema::dropIfExists('avances');
    }
};
