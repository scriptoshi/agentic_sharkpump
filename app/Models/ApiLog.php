<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'api_id',
        'api_tool_id',
        'triggered_at',
        'response_code',
        'response_body',
        'execution_time',
        'success',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     */
    protected function casts(): array
    {
        return [
            'triggered_at' => 'datetime',
            'execution_time' => 'float',
            'success' => 'boolean',
        ];
    }

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the API that owns the log.
     */
    public function api(): BelongsTo
    {
        return $this->belongsTo(Api::class);
    }

    /**
     * Get the tool that owns the log.
     */
    public function apiTool(): BelongsTo
    {
        return $this->belongsTo(ApiTool::class);
    }
}
