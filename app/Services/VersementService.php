<?php

namespace App\Services;

use App\Models\Versement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class VersementService
{
    /**
     * Liste paginée
     */
    public function liste(array $filters): LengthAwarePaginator
    {
        $query = Versement::query()
            ->with([
                'moto:id,immatriculation,marque,modele',
                'conducteur:id,nom,prenom,telephone'
            ]);

        if (!empty($filters['moto_id'])) {
            $query->where('moto_id', $filters['moto_id']);
        }

        if (!empty($filters['conducteur_id'])) {
            $query->where('conducteur_id', $filters['conducteur_id']);
        }

        if (!empty($filters['periodicite'])) {
            $query->where('periodicite', $filters['periodicite']);
        }

        if (isset($filters['en_retard'])) {
            $query->where('en_retard', filter_var($filters['en_retard'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['mois']) && !empty($filters['annee'])) {
            $debut = \Carbon\Carbon::create($filters['annee'], $filters['mois'], 1)->startOfMonth();
            $fin = $debut->copy()->endOfMonth();
            $query->whereBetween('date_versement', [$debut, $fin]);
        } elseif (!empty($filters['annee'])) {
            $query->whereBetween('date_versement', [
                \Carbon\Carbon::create($filters['annee'])->startOfYear(),
                \Carbon\Carbon::create($filters['annee'])->endOfYear(),
            ]);
        }

        return $query
            ->latest('date_versement')
            ->paginate(
                min(
                    (int) ($filters['per_page'] ?? 10),
                    100
                )
            );
    }

    /**
     * Résumé financier
     */
    // public function resume(?int $motoId = null): array
    // {
    //     $cacheKey = "versement_resume_" . ($motoId ?? 'all');

    //     return Cache::remember(
    //         $cacheKey,
    //         now()->addMinutes(30),
    //         function () use ($motoId) {

    //             $query = Versement::query();

    //             if ($motoId) {
    //                 $query->where('moto_id', $motoId);
    //             }

    //             return [

    //                 'montant_attendu_total' =>
    //                     (float) (clone $query)->sum('montant_attendu'),

    //                 'montant_verse_total' =>
    //                     (float) (clone $query)->sum('montant_verse'),

    //                 'reste_a_payer_total' =>
    //                     (float) (clone $query)->sum('reste_a_payer'),

    //                 'nombre_versements' =>
    //                     (clone $query)->count(),

    //                 'nombre_en_retard' =>
    //                     (clone $query)
    //                         ->where('en_retard', true)
    //                         ->count(),

    //                 'pourcentage_paye' =>
    //                     $this->pourcentagePaye(clone $query),

    //                 'pourcentage_retard' =>
    //                     $this->pourcentageRetard(clone $query),

    //                 'dernier_versement' =>
    //                     (clone $query)
    //                         ->latest('date_versement')
    //                         ->with([
    //                             'moto:id,immatriculation,marque,modele',
    //                             'conducteur:id,nom,prenom'
    //                         ])
    //                         ->first()
    //             ];
    //         }
    //     );
    // }
        public function resume(?int $motoId = null): array
    {
        $cacheKey = "versement_resume_" . ($motoId ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($motoId) {
            $query = Versement::query();
            if ($motoId) {
                $query->where('moto_id', $motoId);
            }

            $agg = $query->selectRaw('
                COALESCE(SUM(montant_attendu), 0) as montant_attendu_total,
                COALESCE(SUM(montant_verse), 0) as montant_verse_total,
                COALESCE(SUM(reste_a_payer), 0) as reste_a_payer_total,
                COUNT(*) as nombre_versements,
                SUM(CASE WHEN en_retard = 1 THEN 1 ELSE 0 END) as nombre_en_retard
            ')->first();

            $pourcentagePaye = $agg->montant_attendu_total > 0
                ? round(($agg->montant_verse_total / $agg->montant_attendu_total) * 100, 2)
                : 0;

            $pourcentageRetard = $agg->nombre_versements > 0
                ? round(($agg->nombre_en_retard / $agg->nombre_versements) * 100, 2)
                : 0;

            $dernierQuery = Versement::query();
            if ($motoId) {
                $dernierQuery->where('moto_id', $motoId);
            }

            return [
                'montant_attendu_total' => (float) $agg->montant_attendu_total,
                'montant_verse_total' => (float) $agg->montant_verse_total,
                'reste_a_payer_total' => (float) $agg->reste_a_payer_total,
                'nombre_versements' => (int) $agg->nombre_versements,
                'nombre_en_retard' => (int) $agg->nombre_en_retard,
                'pourcentage_paye' => $pourcentagePaye,
                'pourcentage_retard' => $pourcentageRetard,
                'dernier_versement' => $dernierQuery
                    ->latest('date_versement')
                    ->with(['moto:id,immatriculation,marque,modele', 'conducteur:id,nom,prenom'])
                    ->first(),
            ];
        });
    }

    /**
     * Pourcentage payé
     */
    private function pourcentagePaye($query): float
    {
        $attendu = (float) $query->sum('montant_attendu');

        if ($attendu == 0) {
            return 0;
        }

        $verse = (float) (clone $query)->sum('montant_verse');

        return round(
            ($verse / $attendu) * 100,
            2
        );
    }

    /**
     * Pourcentage des retards
     */
    private function pourcentageRetard($query): float
    {
        $total = (clone $query)->count();

        if ($total == 0) {
            return 0;
        }

        $retard = (clone $query)
            ->where('en_retard', true)
            ->count();

        return round(
            ($retard / $total) * 100,
            2
        );
    }
}