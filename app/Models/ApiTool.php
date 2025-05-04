<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ApiTool extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'api_id',
        'name',
        'description',
        'shouldQueue',
        'version',
        'method',
        'path',
        'query_params',
        'tool_config',
    ];

    /**
     * The attributes that should be cast.
     *
     */
    protected function casts(): array
    {
        return [
            'shouldQueue' => 'boolean',
            'tool_config' => 'array',
        ];
    }

    /**
     * Get the API that owns the tool.
     */
    public function api(): BelongsTo
    {
        return $this->belongsTo(Api::class);
    }

    /**
     * Get the user that owns the tool.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the headers for the tool.
     */
    public function headers(): MorphMany
    {
        return $this->morphMany(ApiHeader::class, 'headerable');
    }

    /**
     * Get the logs for the tool.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }
}
