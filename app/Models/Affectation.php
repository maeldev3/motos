<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affectation extends Model
{
    protected $fillable = ['moto_id', 'conducteur_id', 'date_debut', 'date_fin', 'motif_changement', 'active'];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'active' => 'boolean',
        ];
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
