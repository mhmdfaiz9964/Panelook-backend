<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['province_id', 'name', 'sort_order'];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class)->orderBy('name');
    }
}
