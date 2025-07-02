<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AulaUser extends Pivot
{
    protected $table = 'aula_user';

    protected $fillable = [
        'aula_id',
        'user_id',
    ];

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
