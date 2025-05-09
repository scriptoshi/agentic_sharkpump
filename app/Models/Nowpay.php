<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Nowpay extends Model
{
    use HasUuids;
    protected $table = 'nowpays';

    protected $fillable = [
        'uuid',
        'payment_id',
        'user_id',
        'payment_status',
        'pay_address',
        'pay_amount',
        'pay_currency',
        'price_amount',
        'price_currency',
        'ipn_callback_url',
        'order_id',
        'order_description',
        'purchase_id',
        'created_at',
        'updated_at',
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
     * Get the payable model that owns this payment.
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->payment_status === 'finished';
    }

    /**
     * Complete the payment and notify the payable model
     */
    public function complete(): void
    {
        $this->payable->completePayment($this);
    }
}
