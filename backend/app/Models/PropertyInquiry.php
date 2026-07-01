<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyInquiry extends Model
{
    use HasFactory;

    protected $fillable = ['property_id', 'name', 'phone', 'email', 'message', 'status', 'ip_address'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
