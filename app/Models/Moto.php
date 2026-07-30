<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Moto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'immatriculation', 'marque', 'modele', 'couleur', 'annee_fabrication',
        'numero_chassis', 'numero_moteur', 'date_achat', 'prix_achat', 'photo',
        'type_vehicule', 'montant_versement_mensuel', 'montant_versement_journalier',
        'statut', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'date_achat' => 'date',
            'prix_achat' => 'decimal:2',
            'montant_versement_mensuel' => 'decimal:2',
            'montant_versement_journalier' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }

    public function conducteurs()
    {
        return $this->hasMany(Conducteur::class);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }

    // Utilisé pour l'Eager Loading
    public function affectationActive()
    {
        return $this->hasOne(Affectation::class)->where('active', true);
    }

    public function versements()
    {
        return $this->hasMany(Versement::class);
    }

    public function reparations()
    {
        return $this->hasMany(Reparation::class);
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class);
    }

    // ---- Calculs financiers ----

    public function revenusEntre($debut, $fin): float
    {
        return (float) $this->versements()
            ->whereBetween('date_versement', [$debut, $fin])
            ->sum('montant_verse');
    }

    public function depensesEntre($debut, $fin): float
    {
        $depenses = (float) $this->depenses()->whereBetween('date_depense', [$debut, $fin])->sum('montant');
        $reparations = (float) $this->reparations()->whereBetween('date_reparation', [$debut, $fin])->sum('montant');

        return $depenses + $reparations;
    }

    public function beneficeEntre($debut, $fin): float
    {
        return $this->revenusEntre($debut, $fin) - $this->depensesEntre($debut, $fin);
    }
}