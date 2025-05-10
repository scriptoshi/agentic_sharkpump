<?php

namespace App\Models;

use App\Enums\LaunchpadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Launchpad extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'launchpads';

    /**
     * The database primary key value.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be cast to native types.
     *
     * @var string
     */
    protected function casts()
    {
        return [
            'status' => LaunchpadStatus::class,
            'featured' => 'boolean',
            'kingofthehill' => 'boolean',
            'active' => 'boolean'
        ];
    }

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'factory_id',
        'contract',
        'token',
        'pool',
        'graph',
        'name',
        'symbol',
        'description',
        'chainId',
        'twitter',
        'discord',
        'telegram',
        'website',
        'livestreamId',
        'status',
        'logo',
        'featured',
        'kingofthehill',
        'active'
    ];


    /**

     * Get the factory this model Belongs To.
     *
     */
    public function factory(): BelongsTo
    {
        return $this->belongsTo(Factory::class, 'factory_id', 'id');
    }

    /**

     * Get the user this model Belongs To.
     *
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the bots this model Belongs To.
     *
     */
    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class, 'launchpad_id', 'id');
    }

    /**
     * Get the commands this model Belongs To.
     *
     */
    public function commands(): HasManyThrough
    {
        return $this->hasManyThrough(Command::class, Bot::class, 'launchpad_id', 'bot_id');
    }
}
