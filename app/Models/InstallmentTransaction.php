<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentTransaction extends Model
{
    use HasFactory;
    protected $table = "installments";
    protected $fillable = ['note_id', 'installment_number', 'amount', 'created_at'];
}
