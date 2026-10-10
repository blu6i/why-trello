<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Demo extends Model
{
    protected $table = 'demo';

    const UPDATED_AT = null;

    protected $fillable = [
        'note',
    ];
}
