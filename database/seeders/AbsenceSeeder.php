<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conducteur;
use App\Models\Absence;

class AbsenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupération des conducteurs par leur nom (comme dans VersementSeeder)
        $njaka = Conducteur::where('nom', 'like', '%Njaka%')->first();
        $jese = Conducteur::where('nom', 'like', '%Jese%')->first();
        $fihorenana = Conducteur::where('nom', 'like', '%Fihorenana%')->first();

        // ==========================================
        // 1. NJAKA : 3 Absences (Maladie, Moustique, Accident) -> Cela cause les versements réduits
        // ==========================================
        if ($njaka) {
            // Absence 1 : Maladie (Début Mars) - Impact direct sur le versement de Mars (360000 au lieu de 600000)
            Absence::create([
                'conducteur_id' => $njaka->id,
                'type' => 'maladie',
                'date_debut' => '2026-03-01',
                'date_fin' => '2026-03-07', // 7 jours
                'nombre_jours' => 7,
                'retenue' => 240000, // Calculé sur une base de 600000 Ar / 30 jours
                'motif' => 'Paludisme (certificat médical fourni)',
            ]);

            // Absence 2 : Accident (Début Mai) - Impact sur le versement de Mai (500000 au lieu de 600000)
            Absence::create([
                'conducteur_id' => $njaka->id,
                'type' => 'accident',
                'date_debut' => '2026-05-10',
                'date_fin' => '2026-05-15', // 6 jours
                'nombre_jours' => 6,
                'retenue' => 100000, // Proratisé
                'motif' => 'Accident de trajet (arrêt de travail)',
            ]);

            // Absence 3 : Maladie (Début Juin) - Impact sur le versement de Juin (300000 au lieu de 600000)
            Absence::create([
                'conducteur_id' => $njaka->id,
                'type' => 'maladie',
                'date_debut' => '2026-06-20',
                'date_fin' => '2026-06-30', // 11 jours
                'nombre_jours' => 11,
                'retenue' => 300000,
                'motif' => 'Moustique (Dengue)',
            ]);
        }

        // ==========================================
        // 2. FIHORENANA : 1 Absence (Maladie) -> Versement d'Avril réduit (400000 au lieu de 600000)
        // ==========================================
        if ($fihorenana) {
            Absence::create([
                'conducteur_id' => $fihorenana->id,
                'type' => 'maladie',
                'date_debut' => '2026-04-20',
                'date_fin' => '2026-04-26', // 7 jours
                'nombre_jours' => 7,
                'retenue' => 200000,
                'motif' => 'Grippe sévère',
            ]);
        }

        // ==========================================
        // 3. JESE : Aucune absence -> Versements complets
        // ==========================================
        // Aucune création pour Jese, car il a 600000 Ar par mois sur l'image !
    }
}