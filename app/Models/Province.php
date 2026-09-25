<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'sort_order'];

    public function districts()
    {
        return $this->hasMany(District::class)->orderBy('name');
    }
}
