<?php

namespace App\Models;

use App\Enums\ToolcallStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolCall extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bot_id',
        'chat_id',
        'message_id',
        'tool_call_id',
        'tool_id',
        'name',
        'input',
        'output',
        'status',
    ];



    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'input' => 'json',
        'output' => 'json',
        'status' => ToolcallStatus::class,
    ];

    /**
     * Get the chat that owns the tool call.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Get the message that owns the tool call.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get the bot that owns the tool call.
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Get the api tool that owns the tool call.
     */
    public function apiTool(): BelongsTo
    {
        return $this->belongsTo(ApiTool::class);
    }
}
