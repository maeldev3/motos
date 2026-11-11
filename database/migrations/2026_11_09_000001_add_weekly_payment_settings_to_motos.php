<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('motos', function (Blueprint $table) {
            $table->string('periodicite_versement', 20)->default('mensuel')->after('montant_versement_journalier');
            $table->decimal('montant_versement_hebdomadaire', 14, 2)->default(0)->after('periodicite_versement');
        });
    }

    public function down(): void
    {
        Schema::table('motos', function (Blueprint $table) {
            $table->dropColumn(['periodicite_versement', 'montant_versement_hebdomadaire']);
        });
    }
};
