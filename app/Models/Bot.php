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
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'launchpad_id',
        'name',
        'username',
        'description',
        'logo',
        'logo_path',
        'logo_disk',
        'bot_token',
        'bot_provider',
        'ai_model',
        'api_key',
        'system_prompt',
        'is_active',
        'is_cloneable',
        'settings',
        'credits_per_message',
        'credits_per_star',
        'last_active_at',
        'ai_temperature',
        'ai_max_tokens',
        'ai_store',
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
            'ai_store' => 'boolean',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'bot_token',
        'webhook_secret',
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
     * create webhook_secret ulids on boot
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->webhook_secret = Str::ulid();
        });
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

    /**
     * Get the chats for the bot.
     */
    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    /**
     * Get the messages for the bot.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the tool calls for the bot.
     */
    public function toolCalls(): HasMany
    {
        return $this->hasMany(ToolCall::class);
    }


    /**
     * Get the telegram updates for the bot.
     */
    public function telegramUpdates(): HasMany
    {
        return $this->hasMany(TelegramUpdate::class);
    }

    /**
     * Get the bot users for the bot.
     */
    public function botUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bot_user', 'bot_id', 'user_id');
    }

    /**
     * Get the commands for the bot.
     */
    public function launchpad(): BelongsTo
    {
        return $this->belongsTo(Launchpad::class);
    }
}
