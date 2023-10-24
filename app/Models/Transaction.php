<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_notes',
        'id_good',
        'id_project',
        'quantity',
        'total_amount',
        'created_at'
        // Add other fields as needed
    ];
}
