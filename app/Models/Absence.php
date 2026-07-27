<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Absence extends Model
{
    protected $fillable = ['conducteur_id', 'type', 'date_debut', 'date_fin', 'nombre_jours', 'retenue', 'motif'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'retenue' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::saving(function (Absence $absence) {
            $debut = Carbon::parse($absence->date_debut);
            $fin = Carbon::parse($absence->date_fin);
            $absence->nombre_jours = $fin->diffInDays($debut) + 1;

            // Retenue automatique basée sur le versement journalier attendu de la moto affectée (par défaut)
            if (empty($absence->retenue)) {
                $conducteur = $absence->conducteur ?? Conducteur::find($absence->conducteur_id);
                $montantJournalier = $conducteur?->moto?->montant_versement_journalier
                    ?: (($conducteur?->moto?->montant_versement_mensuel ?? 600000) / 30);
                $absence->retenue = $montantJournalier * $absence->nombre_jours;
            }
        });
    }

    public function conducteur()
    {
        return $this->belongsTo(Conducteur::class);
    }
}
