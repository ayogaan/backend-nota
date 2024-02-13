<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingRefund extends Model
{
    use HasFactory;
    protected $fillable = ['amount', 'building_id'];
}
