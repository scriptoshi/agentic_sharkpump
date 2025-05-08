<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Command extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bot_id',
        'vc_id',
        'command',
        'name',
        'description',
        'system_prompt_override',
        'is_active',
        'credits_per_message',
        'priority',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }

    /**
     * Get the user that owns the command.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bot that owns the command.
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function vc(): BelongsTo
    {
        return $this->belongsTo(Vc::class);
    }

    /**
     * Get all of the tools for the command.
     */
    public function tools(): MorphToMany
    {
        return $this->morphToMany(ApiTool::class, 'toolable', 'toolables', 'toolable_id', 'api_tool_id');
    }
}
