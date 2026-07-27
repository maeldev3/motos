<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerte extends Model
{
    protected $table = 'alertes';

    protected $fillable = ['type', 'alertable_id', 'alertable_type', 'message', 'lue'];

    protected function casts(): array
    {
        return ['lue' => 'boolean'];
    }

    public function alertable()
    {
        return $this->morphTo();
    }
}
