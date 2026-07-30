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

        if (!empty($filters['mois'])) {
            $query->whereMonth(
                'date_versement',
                $filters['mois']
            );
        }

        if (!empty($filters['annee'])) {
            $query->whereYear(
                'date_versement',
                $filters['annee']
            );
        }

        return $query
            ->latest('date_versement')
            ->paginate(
                min(
                    (int) ($filters['per_page'] ?? 20),
                    100
                )
            );
    }

    /**
     * Résumé financier
     */
    public function resume(?int $motoId = null): array
    {
        $cacheKey = "versement_resume_" . ($motoId ?? 'all');

        return Cache::remember(
            $cacheKey,
            now()->addMinutes(30),
            function () use ($motoId) {

                $query = Versement::query();

                if ($motoId) {
                    $query->where('moto_id', $motoId);
                }

                return [

                    'montant_attendu_total' =>
                        (float) (clone $query)->sum('montant_attendu'),

                    'montant_verse_total' =>
                        (float) (clone $query)->sum('montant_verse'),

                    'reste_a_payer_total' =>
                        (float) (clone $query)->sum('reste_a_payer'),

                    'nombre_versements' =>
                        (clone $query)->count(),

                    'nombre_en_retard' =>
                        (clone $query)
                            ->where('en_retard', true)
                            ->count(),

                    'pourcentage_paye' =>
                        $this->pourcentagePaye(clone $query),

                    'pourcentage_retard' =>
                        $this->pourcentageRetard(clone $query),

                    'dernier_versement' =>
                        (clone $query)
                            ->latest('date_versement')
                            ->with([
                                'moto:id,immatriculation,marque,modele',
                                'conducteur:id,nom,prenom'
                            ])
                            ->first()
                ];
            }
        );
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