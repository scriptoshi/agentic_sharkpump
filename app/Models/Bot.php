<?php

namespace App\Models;

use App\Enums\BotProvider;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bot extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'username',
        'bot_token',
        'bot_provider',
        'api_key',
        'system_prompt',
        'is_active',
        'is_cloneable',
        'settings',
        'credits_per_message',
        'credits_per_star',
        'last_active_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bot_provider' => BotProvider::class,
            'is_active' => 'boolean',
            'is_cloneable' => 'boolean',
            'settings' => 'array',
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'bot_token',
        'api_key',
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
     * Get the user that owns the bot.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the commands for the bot.
     */
    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }

    /**
     * Get all of the tools for the bot.
     */
    public function tools(): MorphToMany
    {
        return $this->morphToMany(ApiTool::class, 'toolable', 'toolables', 'toolable_id', 'api_tool_id');
    }

    /**
     * Get the balances for the bot.
     */
    public function balances(): HasMany
    {
        return $this->hasMany(Balance::class);
    }

    /**
     * Get the payments for the bot.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the refunds for the bot.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
