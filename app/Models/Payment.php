<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Payment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bot_id',
        'telegram_payment_charge_id',
        'currency',
        'amount',
        'credits_earned',
        'payment_data',
        'paid_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'credits_earned' => 'decimal:2',
            'payment_data' => 'array',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['payload'];
    }


    /**
     * Get the user who made the payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bot associated with the payment.
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Get the refund associated with the payment.
     */
    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    /**
     * Determine if the payment has been completed.
     */
    public function isPaid(): bool
    {
        return !is_null($this->paid_at);
    }

    /**
     * Determine if the payment has been cancelled.
     */
    public function isCancelled(): bool
    {
        return !is_null($this->cancelled_at);
    }

    /**
     * Determine if the payment has been refunded.
     */
    public function isRefunded(): bool
    {
        return $this->refund()->exists();
    }

    /**
     * Get the transaction associated with the payment.
     */
    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactable');
    }
}
