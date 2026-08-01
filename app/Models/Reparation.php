<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Reparation extends Model
{
    protected $fillable = [
        'moto_id',
        'date_reparation',
        'type_reparation',
        'description',
        'garage',
        'mecanicien',
        'kilometrage',
        'pieces_remplacees',
        'montant',
        'photo_facture',
        'observations'
    ];

    protected $casts = [
        'date_reparation'=>'date:Y-m-d',
        'montant'=>'decimal:2'
    ];

    public function moto()
    {
        return $this->belongsTo(Moto::class)
            ->select([
                'id',
                'immatriculation',
                'marque',
                'modele',
                'statut'
            ]);
    }

    public function scopeFilter(Builder $query,$request)
    {
        return $query
            ->when($request->moto_id,function($q) use($request){
                $q->where('moto_id',$request->moto_id);
            })
            ->when($request->type_reparation,function($q) use($request){
                $q->where('type_reparation',$request->type_reparation);
            });
    }
}
