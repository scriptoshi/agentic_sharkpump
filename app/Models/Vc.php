<?php

// app/Models/Vc.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vc extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vcs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bot_id',
        'user_id',
        'vector_id',
        'vector_name',
        'status',
        'last_active_at',
        'expires_in_days',
        'max_num_results'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    /**
     * Get the bot that owns the vector store.
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Get the user that owns the vector store.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the files for the vector store.
     */
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'file_vc');
    }

    /**
     * Determine if the vector store is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired';
    }

    /**
     * Determine if the vector store is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Determine if the vector store is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }
}
