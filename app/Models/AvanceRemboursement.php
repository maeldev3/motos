<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvanceRemboursement extends Model
{
    protected $fillable = ['avance_id', 'montant', 'date_remboursement', 'commentaire'];

    protected function casts(): array
    {
        return [
            'date_remboursement' => 'date',
            'montant' => 'decimal:2',
        ];
    }

    public function avance()
    {
        return $this->belongsTo(Avance::class);
    }
}
