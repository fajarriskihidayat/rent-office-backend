<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OfficeSpace extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'thumbnail',
        'is_open',
        'is_full_booked',
        'price',
        'duration',
        'address',
        'about',
        'slug',
        'city_id',
    ];

    // generate slug otomatis
    public function setNameAttribute($value)
    {
        // contoh: jika name nya Fajar Riski, maka slugnya fajar-riski
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    // 1 office bisa memiliki banyak photo
    public function photos(): HasMany
    {
        return $this->hasMany(OfficeSpacePhoto::class);
    }

    // 1 office bisa memiliki banyak benefit
    public function benefits(): HasMany
    {
        return $this->hasMany(OfficeSpaceBenefit::class);
    }


    // 1 office dimiliki oleh 1 city
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
