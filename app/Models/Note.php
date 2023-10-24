<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;
    protected $fillable = ['supplier_id', 'project_id', 'is_pay_later', 'created_at'];
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'id_notes')
        ->join('goods', 'transactions.id_good', '=', 'goods.id')
        ->select('transactions.*', 'goods.name as good_name', 'goods.id_supplier');
    }

    public function installments()
    {
        return $this->hasMany(InstallmentTransaction::class, 'note_id')->orderBy('created_at');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class,'supplier_id', 'id');
    }

    
}
