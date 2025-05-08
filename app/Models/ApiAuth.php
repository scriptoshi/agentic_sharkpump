<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiAuth extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_auth';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'api_id',
        'auth_username',
        'auth_password',
        'auth_token',
        'auth_query_value', // stores the value for  case API_KEY = 'api_key'; OR case QUERY_PARAM = 'query_param';
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'auth_password',
        'auth_token',
    ];

    /**
     * Get the user that owns the API authentication.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the API that this authentication belongs to.
     */
    public function api()
    {
        return $this->belongsTo(Api::class, 'api_id');
    }
}
