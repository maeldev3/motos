<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{

    protected $fillable=[
        'moto_id',
        'date_depense',
        'categorie',
        'montant',
        'justificatif',
        'commentaire'
    ];


    protected function casts():array
    {
        return [

            'date_depense'=>'date',

            'montant'=>'decimal:2'

        ];
    }



    public function moto()
    {
        return $this->belongsTo(
            Moto::class
        );
    }

}