<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'icon_url',
        'tier',
        'condition_type',
        'condition_value',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('earned_at');
    }
}
