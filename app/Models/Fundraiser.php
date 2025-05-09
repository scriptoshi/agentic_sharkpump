<?php

namespace App\Models;

use App\Enums\FundAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Fundraiser extends Model
{
    use HasUuids;
    protected $table = 'fundraisers';
    protected $fillable = [
        'uuid',
        'name',
        'description',
        'amount',
        'access',
        'currency',
        'image',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access' => FundAccess::class,
        ];
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }

    public function payables(): MorphMany
    {
        return $this->morphMany(Nowpay::class, 'payable');
    }

    /**
     * for compatibility with Nowpay Model
     */
    public function completePayment(Nowpay $nowpay): void
    {
        return;
    }
}
