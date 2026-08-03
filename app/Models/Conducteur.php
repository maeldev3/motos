<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conducteur extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom', 'prenom', 'sexe', 'date_naissance', 'adresse', 'telephone', 'cin',
        'numero_permis', 'date_embauche', 'photo', 'contact_urgence_nom',
        'contact_urgence_telephone', 'statut', 'moto_id',
    ];

    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'date_embauche' => 'date',
        ];
    }

    public function moto()
    {
        return $this->belongsTo(Moto::class)
            ->select([
                'id',
                'immatriculation',
                'modele'
            ]);
    }

    public function affectations()
    {
        return $this->hasMany(Affectation::class);
    }

    public function versements()
    {
        return $this->hasMany(Versement::class);
    }

    public function absences()
    {
        return $this->hasMany(Absence::class);
    }

    public function avances()
    {
        return $this->hasMany(Avance::class);
    }

    public function soldeAvances(): float
    {
        return (float) $this->avances()->sum('solde');
    }
}
