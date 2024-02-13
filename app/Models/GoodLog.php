<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodLog extends Model
{
    use HasFactory;
    protected $fillable = ['good_id', 'price'];
}
