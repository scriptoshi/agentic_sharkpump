<?php

namespace App\Models;

use App\Casts\AsJson;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TelegramUpdate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bot_id',
        'user_id',
        'chat_id',
        'command_id',
        'telegram_update_id',
        'message',
        'edited_message',
        'inline_query',
        'callback_query',
        'pre_checkout_query',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'message' => AsJson::class,
            'edited_message' => AsJson::class,
            'inline_query' => AsJson::class,
            'callback_query' => AsJson::class,
            'pre_checkout_query' => AsJson::class,
        ];
    }

    /**
     * Get the bot that owns the telegram update.
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Get the user associated with the telegram update.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the chat associated with the telegram update.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the command associated with the telegram update.
     */
    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }

    /**
     * Get the message content based on the update type.
     *
     * @return mixed Array or object depending on the update type
     */
    public function getMessage(): array | object
    {
        return $this->{$this->type};
    }
}
