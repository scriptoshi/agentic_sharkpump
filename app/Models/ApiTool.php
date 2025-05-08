<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use \Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use Hidehalo\Nanoid\Client;

class ApiTool extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'api_id',
        'name',
        'description',
        'shouldQueue',
        'version',
        'method',
        'add_user_to_request',
        'path',
        'query_params',
        'tool_config',
        'output_transformer',
        'strict',
        'is_public'
    ];

    /**
     * add slug on create
     */
    protected static function booted(): void
    {
        static::creating(function ($model) {
            $slug = Str::slug($model->name) . '_';
            $model->slug = static::generateId(prefix: $slug);
        });
    }

    /**
     * The attributes that should be cast.
     *
     */
    protected function casts(): array
    {
        return [
            'shouldQueue' => 'boolean',
            'strict' => 'boolean',
            'is_public' => 'boolean',
            'tool_config' => 'array',
        ];
    }

    /**
     * Get the API that owns the tool.
     */
    public function api(): BelongsTo
    {
        return $this->belongsTo(Api::class);
    }

    /**
     * Get the user that owns the tool.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the headers for the tool.
     */
    public function headers(): MorphMany
    {
        return $this->morphMany(ApiHeader::class, 'headerable');
    }

    /**
     * Get the logs for the tool.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ApiLog::class);
    }

    /**
     * Get the bots using this tool
     */
    public function bots(): MorphToMany
    {
        return $this->morphedByMany(Bot::class, 'toolable');
    }

    /**
     * get the commands using this tool
     */
    public function commands(): MorphToMany
    {
        return $this->morphedByMany(Command::class, 'toolable');
    }

    public function toolCalls(): HasMany
    {
        return $this->hasMany(ToolCall::class);
    }

    /**
     * Transform the output using the specified transformer class
     *
     * @param mixed $output
     * @return mixed
     */
    public function transformResponse(Response $response)
    {
        //chekc if class exists
        if (!class_exists($this->output_transformer)) return  $response->json() ?? $response->body();
        $transformer = app()->make($this->output_transformer);
        return $transformer->transformResponse($response);
    }

    protected static function generateId($length = 16, $prefix = '', $lowercase = false)
    {
        $client = new Client();
        $customAlphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $uid = $client->formattedId($customAlphabet,  $length);
        $generated = $prefix ? $prefix . $uid : $uid;
        $finalStr = $lowercase == true ? strtolower($generated) : $generated;
        return (string) $finalStr;
    }
}
