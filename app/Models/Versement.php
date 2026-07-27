<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Versement extends Model
{
    protected $fillable = [
        'moto_id', 'conducteur_id', 'date_versement', 'periodicite',
        'montant_attendu', 'montant_verse', 'reste_a_payer', 'en_retard', 'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'date_versement' => 'date',
            'montant_attendu' => 'decimal:2',
            'montant_verse' => 'decimal:2',
            'reste_a_payer' => 'decimal:2',
            'en_retard' => 'boolean',
        ];
    }

    protected static function booted()
    {
        static::saving(function (Versement $versement) {
            $versement->reste_a_payer = max(0, $versement->montant_attendu - $versement->montant_verse);
            $versement->en_retard = $versement->reste_a_payer > 0 && $versement->date_versement < now()->toDateString();
        });
    }

    public function moto()
    {
        return $this->belongsTo(Moto::class);
    }

    public function conducteur()
    {
        return $this->belongsTo(Conducteur::class);
    }
}
