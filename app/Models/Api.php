<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Api extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'content_type',
        'auth_type',
        'auth_username',
        'auth_password',
        'auth_token',
        'auth_query_key',
        'auth_query_value',
        'active',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the API.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the headers for the API.
     */
    public function headers(): MorphMany
    {
        return $this->morphMany(ApiHeader::class, 'headerable');
    }

    /**
     * Get the logs for the API.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    /**
     * Get the tools for the API.
     */
    public function tools(): HasMany
    {
        return $this->hasMany(ApiTool::class);
    }
}
