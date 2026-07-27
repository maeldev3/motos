<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $conducteurs = Conducteur::where('statut', '!=', 'inactif')->get();

        $motifs = [
            'maladie' => 3,
            'conge' => 5,
            'autorisation' => 1,
        ];

        // Un conducteur sur trois a une absence récente, avec retenue calculée
        foreach ($conducteurs as $index => $conducteur) {
            if ($index % 3 !== 0) {
                continue;
            }

            $type = array_rand($motifs);
            $jours = $motifs[$type];
            $tauxJournalier = 20000; // base de calcul de la retenue

            Absence::create([
                'conducteur_id' => $conducteur->id,
                'type' => $type,
                'date_debut' => now()->subDays(10),
                'date_fin' => now()->subDays(10 - $jours + 1),
                'nombre_jours' => $jours,
                // Le congé n'entraîne pas de retenue, contrairement aux absences non justifiées
                'retenue' => $type === 'conge' ? 0 : $jours * $tauxJournalier,
                'motif' => match ($type) {
                    'maladie' => 'Certificat médical fourni',
                    'conge' => 'Congé annuel',
                    default => 'Absence autorisée par le gestionnaire',
                },
            ]);
        }
    }
}
