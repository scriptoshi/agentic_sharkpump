<?php

namespace App\Models;

use App\Enums\ApiAuthType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'website',
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
            'auth_type' => ApiAuthType::class,
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

    /**
     * Get the connected authentications for the API.
     */
    public function auth(): HasMany
    {
        return $this->hasMany(ApiAuth::class);
    }

    /**
     * Get the connected headers for the API.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'api_auth', 'api_id', 'user_id');
    }
}
