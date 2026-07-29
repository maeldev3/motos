<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Moto;
use App\Models\Conducteur;
use App\Models\Affectation;

class AffectationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // RÉCUPÉRATION DES DONNÉES
        // On récupère les 3 motos en circulation
        $motosEnCirculation = Moto::where('statut', 'en_circulation')->take(3)->get();
        
        // On récupère les 3 conducteurs actifs (dans l'ordre : Njaka, Jese, Fihorenana)
        // Important : Laranger par ID ou par nom pour qu'ils correspondent dans l'ordre du tableau
        $conducteursActifs = Conducteur::where('statut', 'actif')->orderBy('id')->take(3)->get();

        // On s'assure que nous avons exactement 3 motos et 3 conducteurs
        if ($motosEnCirculation->count() !== 3 || $conducteursActifs->count() !== 3) {
            return; // Si vous n'avez pas exactement 3 de chaque, on arrête le seeder
        }

        // ================================================================
        // 1. AFFECTATION DE NJAKA (Moto 1) - A commencé le 15 Février 2026
        // ================================================================
        Affectation::create([
            'moto_id' => $motosEnCirculation[0]->id,
            'conducteur_id' => $conducteursActifs[0]->id,
            'date_debut' => '2026-02-15', // Mi-février, justifiant le versement réduit
            'date_fin' => null,
            'motif_changement' => null,
            'active' => true,
        ]);
        // Mise à jour de la référence sur le conducteur
        $conducteursActifs[0]->update(['moto_id' => $motosEnCirculation[0]->id]);

        // ================================================================
        // 2. AFFECTATION DE JESE (Moto 2) - A commencé le 1er Juin 2026
        // ================================================================
        Affectation::create([
            'moto_id' => $motosEnCirculation[1]->id,
            'conducteur_id' => $conducteursActifs[1]->id,
            'date_debut' => '2026-06-01', // Début Juin, 2 mois de versements
            'date_fin' => null,
            'motif_changement' => null,
            'active' => true,
        ]);
        $conducteursActifs[1]->update(['moto_id' => $motosEnCirculation[1]->id]);

        // ================================================================
        // 3. AFFECTATION DE FIHORENANA (Moto 3) - A commencé le 1er Avril 2026
        // ================================================================
        Affectation::create([
            'moto_id' => $motosEnCirculation[2]->id,
            'conducteur_id' => $conducteursActifs[2]->id,
            'date_debut' => '2026-04-01', // Début Avril, 4 mois de versements
            'date_fin' => null,
            'motif_changement' => null,
            'active' => true,
        ]);
        $conducteursActifs[2]->update(['moto_id' => $motosEnCirculation[2]->id]);
    }
}