<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avance extends Model
{
    protected $fillable = ['conducteur_id', 'type', 'montant', 'montant_rembourse', 'solde', 'date_octroi', 'commentaire'];

    protected function casts(): array
    {
        return [
            'date_octroi' => 'date',
            'montant' => 'decimal:2',
            'montant_rembourse' => 'decimal:2',
            'solde' => 'decimal:2',
        ];
    }

    protected static function booted()
    {
        static::saving(function (Avance $avance) {
            $avance->solde = max(0, $avance->montant - $avance->montant_rembourse);
        });
    }

    public function conducteur()
    {
        return $this->belongsTo(Conducteur::class);
    }

    public function remboursements()
    {
        return $this->hasMany(AvanceRemboursement::class);
    }
}
