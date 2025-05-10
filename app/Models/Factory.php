<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Relations\HasMany;


class Factory extends Model
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'factories';

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
            'lock_abi' => 'array',
            'factory_abi' => 'array',
            'abi' => 'array',
            'active' => 'boolean'
        ];
    }

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'version',
        'chainId',
        'foundry',
        'contract',
        'lock',
        'lock_abi',
        'factory_abi',
        'abi',
        'active'
    ];


    /**

     * Get the launchpads this model Owns.
     *
     */
    public function launchpads(): HasMany
    {
        return $this->hasMany(Launchpad::class, 'factory_id', 'id');
    }

    /**
     * select only latest factories per chainId
     */
    public function scopeLatestByChain(Builder $query)
    {
        return $query->whereIn('id', function ($subquery) {
            $subquery->selectRaw('MAX(id)')
                ->groupBy('chainId');
        });
    }
}
