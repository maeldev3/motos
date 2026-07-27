<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reparation extends Model
{
    protected $fillable = [
        'moto_id', 'date_reparation', 'type_reparation', 'description', 'garage',
        'mecanicien', 'kilometrage', 'pieces_remplacees', 'montant', 'photo_facture', 'observations',
    ];

    protected function casts(): array
    {
        return [
            'date_reparation' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    public function moto()
    {
        return $this->belongsTo(Moto::class);
    }
}
