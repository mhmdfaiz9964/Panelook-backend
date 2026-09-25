<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'address', 'contact_person', 'phone', 'status'];

    protected $casts = ['status' => 'boolean'];
}
