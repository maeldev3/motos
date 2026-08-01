<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    protected $fillable = [
        'conducteur_id',
        'type',
        'date_debut',
        'date_fin',
        'nombre_jours',
        'retenue',
        'motif',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'retenue'    => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Absence $absence) {

            $debut = Carbon::parse($absence->date_debut)->startOfDay();
            $fin   = Carbon::parse($absence->date_fin)->startOfDay();

            // Vérification
            if ($fin->lt($debut)) {
                throw new \InvalidArgumentException(
                    'La date de fin doit être supérieure ou égale à la date de début.'
                );
            }

            // Calcul du nombre de jours (inclusif)
            $absence->nombre_jours = $debut->diffInDays($fin) + 1;

            // Calcul automatique de la retenue
            if (empty($absence->retenue)) {

                $conducteur = $absence->conducteur()->with('moto')->first();

                $montantJournalier = $conducteur?->moto?->montant_versement_journalier;

                if (!$montantJournalier || $montantJournalier <= 0) {
                    $montantJournalier =
                        ($conducteur?->moto?->montant_versement_mensuel ?? 600000) / 30;
                }

                $absence->retenue = round(
                    $montantJournalier * $absence->nombre_jours,
                    2
                );
            }
        });
    }

    public function conducteur(): BelongsTo
    {
        return $this->belongsTo(Conducteur::class);
    }
}