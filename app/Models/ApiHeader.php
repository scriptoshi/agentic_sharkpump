<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApiHeader extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'header_name',
        'header_value',
    ];

    /**
     * Get the parent headerable model.
     */
    public function headerable(): MorphTo
    {
        return $this->morphTo();
    }
}
