<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Requirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'phone', 'email',
        'category_id', 'city_id', 'transaction_type',
        'min_budget', 'max_budget', 'message', 'status', 'last_matched_at',
    ];

    protected function casts(): array
    {
        return [
            'min_budget'      => 'decimal:2',
            'max_budget'      => 'decimal:2',
            'last_matched_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
