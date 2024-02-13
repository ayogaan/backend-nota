<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalAmount extends Model
{
    use HasFactory;
    protected $fillable = ['amount', 'note_id', 'note', 'created_at'];
}
