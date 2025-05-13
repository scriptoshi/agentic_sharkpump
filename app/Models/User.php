<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn(string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }

    /**
     * Get the APIs for the user.
     */
    public function apis()
    {
        return $this->hasMany(Api::class);
    }

    /**
     * Get the tools for the user.
     */
    public function tools()
    {
        return $this->hasMany(ApiTool::class);
    }

    /**
     * Get the balances for the user.
     */
    public function balances()
    {
        return $this->hasMany(Balance::class);
    }

    /**
     * Get the payments made by the user.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the refunds received by the user.
     */
    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get the bots owned by the user.
     */
    public function bots()
    {
        return $this->hasMany(Bot::class);
    }

    /**
     * Get the telegram updates for the user.
     */
    public function telegramUpdates()
    {
        return $this->hasMany(TelegramUpdate::class);
    }

    /**
     * Get the bot users for the user.
     */
    public function botUsers(): BelongsToMany
    {
        return $this->belongsToMany(Bot::class, 'bot_user', 'user_id', 'bot_id');
    }

    /**
     * Get the connected APIs for the user.
     */
    public function connectedApis(): BelongsToMany
    {
        return $this->belongsToMany(Api::class, 'api_user', 'user_id', 'api_id')
            ->withPivot([
                'auth_username',
                'auth_password',
                'auth_token',
                'auth_query_value',
            ]);
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function hasPermission($permission)
    {
        return false;
    }
}
