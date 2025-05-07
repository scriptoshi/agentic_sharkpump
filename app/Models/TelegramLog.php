<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'bot_id',
        'tool_call_id',
        'message_id',
        'method',
        'tool_id',
        'tool_call_id',
        'parameters',
        'triggered_at',
        'response',
        'execution_time',
        'success',
        'error_message',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'parameters' => 'array',
        'response' => 'array',
        'triggered_at' => 'datetime',
        'execution_time' => 'float',
        'success' => 'boolean',
    ];

    /**
     * Get the bot that owns the log.
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Get the tool call that owns the log.
     */
    public function toolCall(): BelongsTo
    {
        return $this->belongsTo(ToolCall::class);
    }

    /**
     * Get the message the of the Log
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
