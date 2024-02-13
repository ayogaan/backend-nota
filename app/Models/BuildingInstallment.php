<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingInstallment extends Model
{
    use HasFactory;
    protected $fillable = ['amount', 'note_id', 'type', 'created_at'];


}
